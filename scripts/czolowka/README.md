# Czołówka Prima-Auto — animowane logo

Render deterministyczny: canvas 2D → zrzuty klatek (headless Chromium) → ffmpeg.
Żadnych usług płatnych, żadnej biblioteki 3D — logo jest bitmapą, obrót medalu
liczy sam skrypt.

```bash
LD_LIBRARY_PATH=~/opt/chrome-libs python3.12 render.py A light -o wynik.mp4
LD_LIBRARY_PATH=~/opt/chrome-libs python3.12 render.py B dark --klatka 2.4 -o qa.png
```

- `A` — „Medal”: krążek się rozpędza, pokazuje rewers, hamuje na awersie.
- `B` — „Orbita”: pierścienie krążą wokół logo i osiadają jako obwódka.
- `light` / `dark` — białe albo czarne tło.
- `--sekundy` (domyślnie 5), `--klatka <s>` — zrzut jednej klatki do QA zamiast filmu.

Kadr ustawia się w `scena.html`: `W`, `H` (teraz 1080×1920 = 9:16) oraz `CY`, `R`.
Oś czasu wariantów: stałe `A_SPIN`, `A_OBROTY` i fazy w `wariantB()`.

**Dwie pułapki (LVE):** zrzuty większe niż viewport wieszają Chromium na limicie
PMEM, a ffmpeg odpalony przy żywej przeglądarce przebija NPROC — dlatego
kodowanie idzie dopiero po `br.close()`.
