<?php
/**
 * Google Indexing API integration.
 *
 * Hooks transition_post_status dla CPT `listings`:
 *   - publish (z draft/pending/auto-draft/future) → URL_UPDATED
 *   (sprzedaż publish→trash NIE wysyła URL_DELETED — obsługuje ją 301-na-hub w redirects)
 *
 * Zabezpieczenia:
 *   - option `asiaauto_indexing_enabled` (default off)
 *   - option `asiaauto_indexing_armed_after_utc` — datestamp UTC ISO8601; przed tą chwilą nic nie wystrzeli
 *   - transient access_token cache (50min)
 *   - retry queue dla 429/5xx/sieciowych (cron godzinny, max 50 retry/run, max 5 attempts)
 *   - logi przez AsiaAuto_Logger
 *   - sekrety czytane z /home/host476470/secrets/google/ (nie z opcji DB)
 */

defined('ABSPATH') || exit;

class AsiaAuto_Indexing_API {

    const OPTION_ENABLED         = 'asiaauto_indexing_enabled';
    const OPTION_ARMED_AFTER     = 'asiaauto_indexing_armed_after_utc';
    const OPTION_RETRY_QUEUE     = 'asiaauto_indexing_retry_queue';
    const TRANSIENT_TOKEN        = 'asiaauto_indexing_access_token';
    const META_LAST_PUSH         = '_asiaauto_indexing_pushed_at';
    const META_LAST_STATUS       = '_asiaauto_indexing_status';
    const META_LAST_TYPE         = '_asiaauto_indexing_type';
    // Sekrety OAuth: kopia w katalogu W open_basedir web (LiteSpeed/LSPHP). Oryginał
    // ~/secrets/google jest POZA open_basedir → is_readable()=false w kontekście wp-cron
    // (web), więc hook nigdy nie startował (1611× "OAuth files missing" 2026-05-20..06-08).
    // Katalog poza public_html (HTTP 404). refresh_token stały (plugin go nie nadpisuje).
    // Przy reauth (~/secrets/google/reauth-*.py) odśwież kopię: cp tokens.json tutaj. (fix 2026-06-08)
    const SECRETS_DIR            = '/home/host476470/domains/primaauto.com.pl/private-google';
    const POST_TYPE              = 'listings';
    const RETRY_CRON_HOOK        = 'asiaauto_indexing_retry_cron';
    const RETRY_MAX_ATTEMPTS     = 5;
    const RETRY_MAX_PER_RUN      = 50;
    const RETRY_QUEUE_CAP        = 500;
    const TYPE_UPDATED           = 'URL_UPDATED';
    const TYPE_DELETED           = 'URL_DELETED';

    public function __construct() {
        add_action('transition_post_status', [$this, 'onTransition'], 20, 3);
        add_action(self::RETRY_CRON_HOOK, [$this, 'processRetryQueue']);

        if (!wp_next_scheduled(self::RETRY_CRON_HOOK)) {
            wp_schedule_event(time() + 600, 'hourly', self::RETRY_CRON_HOOK);
        }
    }

    public function onTransition($new_status, $old_status, $post) {
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return;
        }
        if (!$this->isEnabled()) {
            return;
        }
        if (!$this->isArmed()) {
            AsiaAuto_Logger::info(sprintf(
                '[Indexing] Skipped (armed-after gate) post=%d new=%s old=%s',
                $post->ID, $new_status, $old_status
            ));
            return;
        }

        $type = $this->resolveNotificationType($new_status, $old_status);
        if (!$type) {
            return;
        }

        $url = get_permalink($post);
        if (!$url) {
            return;
        }

        $this->pushOrQueue((int) $post->ID, $url, $type);
    }

    /**
     * @return array{ok:bool,code:int,error?:string,data?:array}
     */
    public function pushOrQueue($post_id, $url, $type) {
        $token = $this->getAccessToken();
        if (!$token) {
            $this->queueRetry($post_id, $url, $type, 'no_token');
            return ['ok' => false, 'code' => 0, 'error' => 'oauth_refresh_failed'];
        }

        $res = $this->callApi($token, $url, $type);

        if ($res['ok']) {
            update_post_meta($post_id, self::META_LAST_PUSH, time());
            update_post_meta($post_id, self::META_LAST_STATUS, 'ok');
            update_post_meta($post_id, self::META_LAST_TYPE, $type);
            AsiaAuto_Logger::info(sprintf('[Indexing] OK %s %s (post=%d)', $type, $url, $post_id));
            return $res;
        }

        update_post_meta($post_id, self::META_LAST_PUSH, time());
        update_post_meta($post_id, self::META_LAST_STATUS, 'error_' . $res['code']);
        update_post_meta($post_id, self::META_LAST_TYPE, $type);

        if ($this->isRetryable($res['code'])) {
            $this->queueRetry($post_id, $url, $type, 'http_' . $res['code']);
        }
        AsiaAuto_Logger::error(sprintf(
            '[Indexing] FAIL %s %s HTTP %d: %s',
            $type, $url, $res['code'], substr($res['error'] ?? '', 0, 200)
        ));
        return $res;
    }

    public function processRetryQueue() {
        if (!$this->isEnabled() || !$this->isArmed()) {
            return;
        }
        $queue = get_option(self::OPTION_RETRY_QUEUE, []);
        if (!is_array($queue) || !$queue) {
            return;
        }

        $kept = [];
        $processed = 0;
        $stop = false;

        foreach ($queue as $idx => $item) {
            if ($stop || $processed >= self::RETRY_MAX_PER_RUN) {
                $kept[] = $item;
                continue;
            }
            $token = $this->getAccessToken();
            if (!$token) {
                $kept[] = $item;
                $stop = true;
                continue;
            }
            $res = $this->callApi($token, $item['url'], $item['type']);
            $processed++;

            if ($res['ok']) {
                update_post_meta((int) $item['post_id'], self::META_LAST_STATUS, 'ok_retry');
                AsiaAuto_Logger::info(sprintf(
                    '[Indexing] Retry OK %s %s (attempts=%d)',
                    $item['type'], $item['url'], $item['attempts'] + 1
                ));
            } else {
                $item['attempts'] = (int) $item['attempts'] + 1;
                $item['last_error'] = $res['code'];

                if ($res['code'] === 429) {
                    // Quota exhausted — stop processing, keep this + remaining
                    $kept[] = $item;
                    AsiaAuto_Logger::warning('[Indexing] Retry stopped — 429 quota');
                    $stop = true;
                    continue;
                }

                if ($item['attempts'] < self::RETRY_MAX_ATTEMPTS && $this->isRetryable($res['code'])) {
                    $kept[] = $item;
                } else {
                    AsiaAuto_Logger::error(sprintf(
                        '[Indexing] Retry given up after %d attempts: %s (HTTP %d)',
                        $item['attempts'], $item['url'], $res['code']
                    ));
                }
            }
            usleep(1200000); // 1.2s rate-limit cushion
        }

        update_option(self::OPTION_RETRY_QUEUE, $kept, false);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isEnabled(): bool {
        return (bool) get_option(self::OPTION_ENABLED, false);
    }

    public function isArmed(): bool {
        $after = get_option(self::OPTION_ARMED_AFTER, '');
        if (!$after) {
            return true;
        }
        $ts = strtotime($after);
        return $ts !== false && time() >= $ts;
    }

    public function resolveNotificationType(string $new, string $old): ?string {
        $publishable_from = ['draft', 'pending', 'auto-draft', 'new', 'future'];
        if ($new === 'publish' && in_array($old, $publishable_from, true)) {
            return self::TYPE_UPDATED;
        }
        // URL_DELETED świadomie nieużywany (v0.32.51): sprzedaż (publish→trash) obsługuje
        // 301-na-hub w class-asiaauto-redirects.php (equity transfer). URL_DELETED kłóciłby
        // się z 301 (Google idzie za realnym statusem HTTP) + bug: get_permalink() trashowanego
        // posta zwraca URL z sufiksem __trashed, którego Google nigdy nie indeksował.
        return null;
    }

    public function getAccessToken(): ?string {
        $cached = get_transient(self::TRANSIENT_TOKEN);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $oauth_file  = self::SECRETS_DIR . '/oauth-desktop-client.json';
        $tokens_file = self::SECRETS_DIR . '/tokens.json';
        if (!is_readable($oauth_file) || !is_readable($tokens_file)) {
            AsiaAuto_Logger::error('[Indexing] OAuth files missing: ' . self::SECRETS_DIR);
            return null;
        }

        $oauth_raw = json_decode(file_get_contents($oauth_file), true);
        $tokens    = json_decode(file_get_contents($tokens_file), true);
        $oauth     = $oauth_raw['installed'] ?? null;
        if (!$oauth || !$tokens || empty($tokens['refresh_token'])) {
            AsiaAuto_Logger::error('[Indexing] OAuth shape invalid (missing refresh_token)');
            return null;
        }

        $payload = http_build_query([
            'client_id'     => $oauth['client_id'],
            'client_secret' => $oauth['client_secret'],
            'refresh_token' => $tokens['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'AsiaAuto-Indexing/1.0',
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code !== 200) {
            AsiaAuto_Logger::error(sprintf('[Indexing] OAuth refresh fail HTTP %d: %s', $code, $err ?: substr((string) $resp, 0, 200)));
            return null;
        }
        $data = json_decode($resp, true);
        if (empty($data['access_token'])) {
            AsiaAuto_Logger::error('[Indexing] OAuth refresh: no access_token in response');
            return null;
        }

        $ttl = (int) ($data['expires_in'] ?? 3600) - 300;
        set_transient(self::TRANSIENT_TOKEN, $data['access_token'], max(60, $ttl));
        return $data['access_token'];
    }

    /**
     * @return array{ok:bool,code:int,error?:string,data?:array}
     */
    private function callApi(string $token, string $url, string $type): array {
        $body = json_encode(['url' => $url, 'type' => $type]);

        $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'AsiaAuto-Indexing/1.0',
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['ok' => false, 'code' => 0, 'error' => 'curl: ' . $err];
        }
        if ($code === 200) {
            return ['ok' => true, 'code' => 200, 'data' => json_decode($resp, true) ?: []];
        }
        return ['ok' => false, 'code' => (int) $code, 'error' => (string) $resp];
    }

    private function isRetryable(int $code): bool {
        return $code === 0 || $code === 429 || ($code >= 500 && $code < 600);
    }

    private function queueRetry(int $post_id, string $url, string $type, string $reason): void {
        $queue = get_option(self::OPTION_RETRY_QUEUE, []);
        if (!is_array($queue)) {
            $queue = [];
        }
        $queue[] = [
            'post_id'   => $post_id,
            'url'       => $url,
            'type'      => $type,
            'reason'    => $reason,
            'queued_at' => time(),
            'attempts'  => 0,
        ];
        if (count($queue) > self::RETRY_QUEUE_CAP) {
            $queue = array_slice($queue, -self::RETRY_QUEUE_CAP);
        }
        update_option(self::OPTION_RETRY_QUEUE, $queue, false);
    }
}
