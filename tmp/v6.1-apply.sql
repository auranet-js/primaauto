-- DRY_RUN apply v6.1 taksonomii (2026-04-23 09:35:36)
-- Wygenerowane przez tmp/apply-taxonomy.php
-- Backup DB: ~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-*.sql

UPDATE wp7j_terms SET name='M9', slug='m9' WHERE term_id=5304; -- #1 AITO/Aito M9 → AITO/M9
UPDATE wp7j_term_taxonomy SET parent=5300 WHERE term_id=5304; -- #1 parent → AITO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5304, '_serie_full_title', 'AITO M9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #1
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5304, '_serie_api_value', 'Aito M9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #1
UPDATE wp7j_terms SET name='M7', slug='m7' WHERE term_id=5301; -- #3 AITO/Aito M7 → AITO/M7
UPDATE wp7j_term_taxonomy SET parent=5300 WHERE term_id=5301; -- #3 parent → AITO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5301, '_serie_full_title', 'AITO M7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #3
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5301, '_serie_api_value', 'Aito M7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #3
UPDATE wp7j_terms SET name='M8', slug='m8' WHERE term_id=5302; -- #4 AITO/Aito M8 → AITO/M8
UPDATE wp7j_term_taxonomy SET parent=5300 WHERE term_id=5302; -- #4 parent → AITO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5302, '_serie_full_title', 'AITO M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #4
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5302, '_serie_api_value', 'Aito M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #4
UPDATE wp7j_terms SET name='M5', slug='m5' WHERE term_id=5303; -- #10 AITO/Aito M5 → AITO/M5
UPDATE wp7j_term_taxonomy SET parent=5300 WHERE term_id=5303; -- #10 parent → AITO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5303, '_serie_full_title', 'AITO M5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #10
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5303, '_serie_api_value', 'Aito M5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #10
UPDATE wp7j_terms SET name='E5 Sportback', slug='e5-sportback' WHERE term_id=6520; -- #204 Audi/Audi E5 Sportback → Audi/E5 Sportback
UPDATE wp7j_term_taxonomy SET parent=4087 WHERE term_id=6520; -- #204 parent → Audi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6520, '_serie_full_title', 'Audi E5 Sportback') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #204
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6520, '_serie_api_value', 'Audi E5 Sportback') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #204
UPDATE wp7j_terms SET name='06', slug='06' WHERE term_id=4809; -- #13 Avatr/Avatr 06 → Avatr/06
UPDATE wp7j_term_taxonomy SET parent=4808 WHERE term_id=4809; -- #13 parent → Avatr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4809, '_serie_full_title', 'Avatr 06') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #13
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4809, '_serie_api_value', 'Avatr 06') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #13
UPDATE wp7j_terms SET name='12', slug='12' WHERE term_id=4811; -- #18 Avatr/Avatr 12 → Avatr/12
UPDATE wp7j_term_taxonomy SET parent=4808 WHERE term_id=4811; -- #18 parent → Avatr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4811, '_serie_full_title', 'Avatr 12') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #18
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4811, '_serie_api_value', 'Avatr 12') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #18
UPDATE wp7j_terms SET name='07', slug='07' WHERE term_id=4812; -- #32 Avatr/Avatr 07 → Avatr/07
UPDATE wp7j_term_taxonomy SET parent=4808 WHERE term_id=4812; -- #32 parent → Avatr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4812, '_serie_full_title', 'Avatr 07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #32
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4812, '_serie_api_value', 'Avatr 07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #32
UPDATE wp7j_terms SET name='11', slug='11' WHERE term_id=4810; -- #59 Avatr/Avatr 11 → Avatr/11
UPDATE wp7j_term_taxonomy SET parent=4808 WHERE term_id=4810; -- #59 parent → Avatr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4810, '_serie_full_title', 'Avatr 11') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #59
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4810, '_serie_api_value', 'Avatr 11') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #59
UPDATE wp7j_terms SET name='BJ40', slug='bj40' WHERE term_id=4780; -- #155 Beijing Off-Road/Beijing Off-road BJ40 → BAIC/BJ40
UPDATE wp7j_term_taxonomy SET parent=6521 WHERE term_id=4780; -- #155 parent → BAIC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4780, '_serie_full_title', 'BAIC BJ40') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #155
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4780, '_serie_api_value', 'Beijing Off-road BJ40') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #155
UPDATE wp7j_terms SET name='BJ40 EREV', slug='bj40-erev' WHERE term_id=4783; -- #208 Beijing Off-Road/Beijing Off-road BJ40 EREV → BAIC/BJ40 EREV
UPDATE wp7j_term_taxonomy SET parent=6521 WHERE term_id=4783; -- #208 parent → BAIC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4783, '_serie_full_title', 'BAIC BJ40 EREV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #208
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4783, '_serie_api_value', 'Beijing Off-road BJ40 EREV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #208
UPDATE wp7j_terms SET name='BJ60', slug='bj60' WHERE term_id=4784; -- #209 Beijing Off-Road/Beijing Off-road BJ60 → BAIC/BJ60
UPDATE wp7j_term_taxonomy SET parent=6521 WHERE term_id=4784; -- #209 parent → BAIC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4784, '_serie_full_title', 'BAIC BJ60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #209
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4784, '_serie_api_value', 'Beijing Off-road BJ60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #209
UPDATE wp7j_terms SET name='T01', slug='t01' WHERE term_id=5688; -- #103 212/212 T01 → Beijing 212/T01
UPDATE wp7j_term_taxonomy SET parent=6522 WHERE term_id=5688; -- #103 parent → Beijing 212
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5688, '_serie_full_title', 'Beijing 212 T01') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #103
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5688, '_serie_api_value', '212 T01') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #103
UPDATE wp7j_terms SET name='Tang DM-i', slug='tang-dm-i' WHERE term_id=3700; -- #6 BYD/Tang DM → BYD/Tang DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3700; -- #6 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3700, '_serie_full_title', 'BYD Tang') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #6
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3700, '_serie_api_value', 'Tang DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #6
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3720; -- #17 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3720, '_serie_full_title', 'BYD Song L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #17
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3720, '_serie_api_value', 'Song L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #17
UPDATE wp7j_terms SET name='Han DM-i', slug='han-dm-i' WHERE term_id=3705; -- #19 BYD/Han DM → BYD/Han DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3705; -- #19 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3705, '_serie_full_title', 'BYD Han') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #19
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3705, '_serie_api_value', 'Han DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #19
UPDATE wp7j_terms SET name='Seal 6 DM-i', slug='seal-6-dm-i' WHERE term_id=3740; -- #22 BYD/Seal 06 DM → BYD/Seal 6 DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3740; -- #22 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3740, '_serie_full_title', 'BYD Seal 6 DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #22
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3740, '_serie_api_value', 'Seal 06 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #22
UPDATE wp7j_terms SET name='Song L DM-i', slug='song-l-dm-i' WHERE term_id=3728; -- #26 BYD/Song L DM → BYD/Song L DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3728; -- #26 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3728, '_serie_full_title', 'BYD Song L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #26
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3728, '_serie_api_value', 'Song L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #26
UPDATE wp7j_terms SET name='Song Pro DM-i', slug='song-pro-dm-i' WHERE term_id=3709; -- #29 BYD/Song Pro DM → BYD/Song Pro DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3709; -- #29 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3709, '_serie_full_title', 'BYD Song Pro') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #29
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3709, '_serie_api_value', 'Song Pro DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #29
UPDATE wp7j_terms SET name='Leopard 3 (Tai 3) FCB', slug='leopard-3' WHERE term_id=5522; -- #35 Fangchengbao/Tai 3 → BYD/Leopard 3 (Tai 3) FCB
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=5522; -- #35 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5522, '_serie_full_title', 'BYD Leopard 3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #35
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5522, '_serie_api_value', 'Tai 3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #35
UPDATE wp7j_terms SET name='Sealion 7', slug='sealion-7' WHERE term_id=3760; -- #37 BYD/Haishi 07 EV → BYD/Sealion 7
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3760; -- #37 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3760, '_serie_full_title', 'BYD Sealion 7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #37
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3760, '_serie_api_value', 'Haishi 07 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #37
UPDATE wp7j_terms SET name='Atto 2', slug='atto-2' WHERE term_id=3758; -- #38 BYD/Yuan UP → BYD/Atto 2
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3758; -- #38 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3758, '_serie_full_title', 'BYD Atto 2') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #38
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3758, '_serie_api_value', 'Yuan UP') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #38
UPDATE wp7j_terms SET name='Qin L DM-i', slug='qin-l-dm-i' WHERE term_id=3713; -- #44 BYD/Qin L DM → BYD/Qin L DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3713; -- #44 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3713, '_serie_full_title', 'BYD Qin L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #44
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3713, '_serie_api_value', 'Qin L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #44
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3707; -- #50 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3707, '_serie_full_title', 'BYD Han EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #50
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3707, '_serie_api_value', 'Han EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #50
UPDATE wp7j_terms SET name='Seal U DM-I (Song Plus)', slug='seal-u-dm-i' WHERE term_id=3702; -- #51 BYD/Song PLUS DM → BYD/Seal U DM-I (Song Plus)
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3702; -- #51 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3702, '_serie_full_title', 'BYD Seal U DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #51
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3702, '_serie_api_value', 'Song PLUS DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #51
UPDATE wp7j_terms SET name='Sealion 8 DM-I (Tang L)', slug='sealion-8-dm-i' WHERE term_id=3746; -- #52 BYD/Tang L DM → BYD/Sealion 8 DM-I (Tang L)
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3746; -- #52 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3746, '_serie_full_title', 'BYD Sealion 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #52
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3746, '_serie_api_value', 'Tang L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #52
UPDATE wp7j_terms SET name='Leopard 7 (Tai 7) FCB, PHEV', slug='leopard-7' WHERE term_id=6066; -- #53 Fangchengbao/Tai 7 PHEV → BYD/Leopard 7 (Tai 7) FCB, PHEV
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=6066; -- #53 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6066, '_serie_full_title', 'BYD Leopard 7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #53
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6066, '_serie_api_value', 'Tai 7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #53
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3761; -- #74 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3761, '_serie_full_title', 'BYD Han L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #74
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3761, '_serie_api_value', 'Han L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #74
UPDATE wp7j_terms SET name='Sealion 7 DM', slug='sealion-7-dm' WHERE term_id=6499; -- #75 BYD/Sea Lion 07DM → BYD/Sealion 7 DM
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=6499; -- #75 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6499, '_serie_full_title', 'BYD Sealion 7 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #75
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6499, '_serie_api_value', 'Sea Lion 07DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #75
UPDATE wp7j_terms SET name='Sealion 6 EV', slug='sealion-6-ev' WHERE term_id=6501; -- #87 BYD/Sea Lion 06EV → BYD/Sealion 6 EV
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=6501; -- #87 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6501, '_serie_full_title', 'BYD Sealion 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #87
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6501, '_serie_api_value', 'Sea Lion 06EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #87
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3741; -- #88 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3741, '_serie_full_title', 'BYD Song PLUS EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #88
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3741, '_serie_api_value', 'Song PLUS EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #88
UPDATE wp7j_terms SET name='Xia Summer', slug='xia-summer' WHERE term_id=3742; -- #89 BYD/Xia → BYD/Xia Summer
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3742; -- #89 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3742, '_serie_full_title', 'BYD Xia') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #89
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3742, '_serie_api_value', 'Xia') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #89
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3743; -- #104 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3743, '_serie_full_title', 'BYD Qin L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #104
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3743, '_serie_api_value', 'Qin L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #104
UPDATE wp7j_terms SET name='Seal 7 DM', slug='seal-7-dm' WHERE term_id=3749; -- #105 BYD/Seal 07 DM → BYD/Seal 7 DM
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3749; -- #105 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3749, '_serie_full_title', 'BYD Seal 7 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #105
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3749, '_serie_api_value', 'Seal 07 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #105
UPDATE wp7j_terms SET name='Sealion 5 EV', slug='sealion-5-ev' WHERE term_id=3733; -- #122 BYD/Haishi 05 EV → BYD/Sealion 5 EV
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3733; -- #122 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3733, '_serie_full_title', 'BYD Sealion 5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #122
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3733, '_serie_api_value', 'Haishi 05 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #122
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3752; -- #123 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3752, '_serie_full_title', 'BYD Han L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #123
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3752, '_serie_api_value', 'Han L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #123
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3699; -- #124 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3699, '_serie_full_title', 'BYD Qin PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #124
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3699, '_serie_api_value', 'Qin PLUS EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #124
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3738; -- #125 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3738, '_serie_full_title', 'BYD Seal') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #125
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3738, '_serie_api_value', 'Seal') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #125
UPDATE wp7j_terms SET name='Seal 5 DM', slug='seal-5-dm' WHERE term_id=3759; -- #126 BYD/Seal 05 DM → BYD/Seal 5 DM
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3759; -- #126 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3759, '_serie_full_title', 'BYD Seal 5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #126
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3759, '_serie_api_value', 'Seal 05 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #126
UPDATE wp7j_terms SET name='ATTO 3 (Yuan PLUS)', slug='atto-3' WHERE term_id=3706; -- #127 BYD/Yuan PLUS → BYD/ATTO 3 (Yuan PLUS)
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3706; -- #127 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3706, '_serie_full_title', 'BYD ATTO 3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #127
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3706, '_serie_api_value', 'Yuan PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #127
UPDATE wp7j_terms SET name='Leopard 5 (Denza B5)' WHERE term_id=5523; -- #132 Fangchengbao/Leopard 5 → BYD/Leopard 5 (Denza B5)
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=5523; -- #132 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5523, '_serie_full_title', 'BYD Leopard 5 (Denza B5)') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #132
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5523, '_serie_api_value', 'Leopard 5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #132
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3716; -- #149 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3716, '_serie_full_title', 'BYD Dolphin') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #149
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3716, '_serie_api_value', 'Dolphin') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #149
UPDATE wp7j_terms SET name='Sealion 5 DM', slug='sealion-5-dm' WHERE term_id=3724; -- #150 BYD/Haishi 05 DM → BYD/Sealion 5 DM
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3724; -- #150 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3724, '_serie_full_title', 'BYD Sealion 5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #150
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3724, '_serie_api_value', 'Haishi 05 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #150
UPDATE wp7j_terms SET name='Sealion 6 DM', slug='sealion-6-dm' WHERE term_id=3764; -- #151 BYD/Haishi 06 DM → BYD/Sealion 6 DM
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3764; -- #151 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3764, '_serie_full_title', 'BYD Sealion 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #151
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3764, '_serie_api_value', 'Haishi 06 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #151
UPDATE wp7j_terms SET name='Qin Plus DM-i', slug='qin-plus-dm-i' WHERE term_id=3750; -- #152 BYD/Qin PLUS DM → BYD/Qin Plus DM-i
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3750; -- #152 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3750, '_serie_full_title', 'BYD Qin Plus DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #152
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3750, '_serie_api_value', 'Qin PLUS DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #152
UPDATE wp7j_terms SET name='Seal 6 EV', slug='seal-6-ev' WHERE term_id=3744; -- #153 BYD/Seal 06 EV → BYD/Seal 6 EV
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3744; -- #153 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3744, '_serie_full_title', 'BYD Seal 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #153
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3744, '_serie_api_value', 'Seal 06 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #153
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3701; -- #154 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3701, '_serie_full_title', 'BYD Seal 06 GT') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #154
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3701, '_serie_api_value', 'Seal 06 GT') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #154
UPDATE wp7j_terms SET name='Leopard 8  (Denza B8)' WHERE term_id=5521; -- #170 Fangchengbao/Leopard 8 → BYD/Leopard 8  (Denza B8)
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=5521; -- #170 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5521, '_serie_full_title', 'BYD Leopard 8 (Denza B8)') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #170
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5521, '_serie_api_value', 'Leopard 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #170
UPDATE wp7j_terms SET name='Seal 6 DM Wagon', slug='seal-6-dm-wagon' WHERE term_id=3762; -- #205 BYD/Seal 06 DM Wagon → BYD/Seal 6 DM Wagon
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3762; -- #205 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3762, '_serie_full_title', 'BYD Seal 6 Kombi') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #205
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3762, '_serie_api_value', 'Seal 06 DM Wagon') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #205
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3726; -- #206 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3726, '_serie_full_title', 'BYD Seal DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #206
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3726, '_serie_api_value', 'Seal DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #206
UPDATE wp7j_terms SET name='Sealion 8 (Tang L) EV', slug='sealion-8-ev' WHERE term_id=3703; -- #207 BYD/Tang L EV → BYD/Sealion 8 (Tang L) EV
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=3703; -- #207 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3703, '_serie_full_title', 'BYD Sealion 8 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #207
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3703, '_serie_api_value', 'Tang L EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #207
UPDATE wp7j_terms SET name='Yangwang U7', slug='yangwang-u7' WHERE term_id=6509; -- #265 Yangwang/Yangwang U7 PHEV → BYD/Yangwang U7
UPDATE wp7j_term_taxonomy SET parent=3697 WHERE term_id=6509; -- #265 parent → BYD
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6509, '_serie_full_title', 'BYD Yangwang U7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #265
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6509, '_serie_api_value', 'Yangwang U7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #265
UPDATE wp7j_terms SET name='UNI-V', slug='uni-v' WHERE term_id=4022; -- #14 Changan/Changan UNI-V → Changan/UNI-V
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4022; -- #14 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4022, '_serie_full_title', 'Changan UNI-V') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #14
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4022, '_serie_api_value', 'Changan UNI-V') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #14
UPDATE wp7j_terms SET name='CS75 Plus', slug='cs75-plus' WHERE term_id=4028; -- #33 Changan/Changan CS75 PLUS → Changan/CS75 Plus
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4028; -- #33 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4028, '_serie_full_title', 'Changan CS75 Plus') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #33
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4028, '_serie_api_value', 'Changan CS75 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #33
UPDATE wp7j_terms SET name='UNI-Z', slug='uni-z' WHERE term_id=4046; -- #106 Changan/Changan UNI-Z → Changan/UNI-Z
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4046; -- #106 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4046, '_serie_full_title', 'Changan UNI-Z') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #106
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4046, '_serie_api_value', 'Changan UNI-Z') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #106
UPDATE wp7j_terms SET name='UNI-Z PHEV', slug='uni-z-phev' WHERE term_id=4026; -- #128 Changan/Changan UNI-Z PHEV → Changan/UNI-Z PHEV
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4026; -- #128 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4026, '_serie_full_title', 'Changan UNI-Z PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #128
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4026, '_serie_api_value', 'Changan UNI-Z PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #128
UPDATE wp7j_terms SET name='X7 PLUS', slug='x7-plus' WHERE term_id=4043; -- #129 Changan/Changan X7 PLUS → Changan/X7 PLUS
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4043; -- #129 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4043, '_serie_full_title', 'Changan X7 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #129
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4043, '_serie_api_value', 'Changan X7 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #129
UPDATE wp7j_terms SET name='CS55 Plus', slug='cs55-plus' WHERE term_id=4040; -- #156 Changan/Changan CS55 PLUS → Changan/CS55 Plus
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4040; -- #156 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4040, '_serie_full_title', 'Changan CS55 Plus') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #156
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4040, '_serie_api_value', 'Changan CS55 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #156
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4027; -- #157 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4027, '_serie_full_title', 'Changan Eado') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #157
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4027, '_serie_api_value', 'Eado') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #157
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4065; -- #158 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4065, '_serie_full_title', 'Changan Eado PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #158
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4065, '_serie_api_value', 'Eado PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #158
UPDATE wp7j_terms SET name='UNI-V iDD', slug='uni-v-idd' WHERE term_id=4058; -- #210 Changan/Changan UNI-V iDD → Changan/UNI-V iDD
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=4058; -- #210 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4058, '_serie_full_title', 'Changan UNI-V iDD') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #210
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4058, '_serie_api_value', 'Changan UNI-V iDD') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #210
UPDATE wp7j_terms SET name='CS55 Plus PHEV' WHERE term_id=6122; -- #211 Changan/长安CS55 PLUS PHEV → Changan/CS55 Plus PHEV
UPDATE wp7j_term_taxonomy SET parent=4021 WHERE term_id=6122; -- #211 parent → Changan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6122, '_serie_full_title', 'Changan CS55 Plus PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #211
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6122, '_serie_api_value', '长安CS55 PLUS PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #211
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3581; -- #11 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3581, '_serie_full_title', 'Chery Arrizo 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #11
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3581, '_serie_api_value', 'Arrizo 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #11
UPDATE wp7j_terms SET name='Tiggo 8 Pro' WHERE term_id=3584; -- #60 Chery/Tiggo 8 PRO → Chery/Tiggo 8 Pro
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3584; -- #60 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3584, '_serie_full_title', 'Chery Tiggo 8 Pro') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #60
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3584, '_serie_api_value', 'Tiggo 8 PRO') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #60
UPDATE wp7j_terms SET name='Arrizo 8 Pro' WHERE term_id=3603; -- #76 Chery/Arrizo 8 PRO → Chery/Arrizo 8 Pro
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3603; -- #76 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3603, '_serie_full_title', 'Chery Arrizo 8 Pro') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #76
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3603, '_serie_api_value', 'Arrizo 8 PRO') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #76
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3582; -- #90 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3582, '_serie_full_title', 'Chery Tiggo 9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #90
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3582, '_serie_api_value', 'Tiggo 9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #90
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3591; -- #130 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3591, '_serie_full_title', 'Chery Tiggo 8 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #130
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3591, '_serie_api_value', 'Tiggo 8 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #130
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3604; -- #160 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3604, '_serie_full_title', 'Chery Tiggo 8 PLUS C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #160
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3604, '_serie_api_value', 'Tiggo 8 PLUS C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #160
UPDATE wp7j_terms SET name='Tiggo 9 (Tiggo 8L)', slug='tiggo-9' WHERE term_id=3586; -- #161 Chery/Tiggo 8L → Chery/Tiggo 9 (Tiggo 8L)
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3586; -- #161 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3586, '_serie_full_title', 'Chery Tiggo 9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #161
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3586, '_serie_api_value', 'Tiggo 8L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #161
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3585; -- #162 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3585, '_serie_full_title', 'Chery Tiggo 9 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #162
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3585, '_serie_api_value', 'Tiggo 9 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #162
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3579; -- #212 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3579, '_serie_full_title', 'Chery Tiggo 7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #212
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3579, '_serie_api_value', 'Tiggo 7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #212
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3600; -- #213 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3600, '_serie_full_title', 'Chery Tiggo 7 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #213
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3600, '_serie_api_value', 'Tiggo 7 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #213
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=3588; -- #214 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3588, '_serie_full_title', 'Chery Tiggo 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #214
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3588, '_serie_api_value', 'Tiggo 8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #214
UPDATE wp7j_terms SET name='A9L', slug='a9l' WHERE term_id=5185; -- #61 Chery Fengyun/Fengyun A9L → Chery Fulwin/A9L
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=5185; -- #61 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5185, '_serie_full_title', 'Chery Fulwin A9L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #61
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5185, '_serie_api_value', 'Fengyun A9L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #61
UPDATE wp7j_terms SET name='T8', slug='t8' WHERE term_id=5187; -- #163 Chery Fengyun/Fengyun T8 → Chery Fulwin/T8
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=5187; -- #163 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5187, '_serie_full_title', 'Chery Fulwin T8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #163
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5187, '_serie_api_value', 'Fengyun T8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #163
UPDATE wp7j_terms SET name='T9', slug='t9' WHERE term_id=5184; -- #164 Chery Fengyun/Fengyun T9 → Chery Fulwin/T9
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=5184; -- #164 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5184, '_serie_full_title', 'Chery Fulwin T9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #164
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5184, '_serie_api_value', 'Fengyun T9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #164
UPDATE wp7j_terms SET name='A8', slug='a8' WHERE term_id=5183; -- #215 Chery Fengyun/Fengyun A8 → Chery Fulwin/A8
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=5183; -- #215 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5183, '_serie_full_title', 'Chery Fulwin A8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #215
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5183, '_serie_api_value', 'Fengyun A8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #215
UPDATE wp7j_terms SET name='A8L', slug='a8l' WHERE term_id=5188; -- #216 Chery Fengyun/Fengyun A8L → Chery Fulwin/A8L
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=5188; -- #216 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5188, '_serie_full_title', 'Chery Fulwin A8L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #216
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5188, '_serie_api_value', 'Fengyun A8L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #216
UPDATE wp7j_terms SET name='X3 PLUS', slug='x3-plus' WHERE term_id=6519; -- #217 Chery Fengyun/Fengyun X3 PLUS → Chery Fulwin/X3 PLUS
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=6519; -- #217 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6519, '_serie_full_title', 'Chery Fulwin X3 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #217
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6519, '_serie_api_value', 'Fengyun X3 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #217
UPDATE wp7j_terms SET name='X3L' WHERE term_id=6234; -- #218 Chery Fengyun/风云X3L → Chery Fulwin/X3L
UPDATE wp7j_term_taxonomy SET parent=6523 WHERE term_id=6234; -- #218 parent → Chery Fulwin
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6234, '_serie_full_title', 'Chery Fulwin X3L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #218
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6234, '_serie_api_value', '风云X3L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #218
UPDATE wp7j_terms SET name='L07', slug='l07' WHERE term_id=4214; -- #91 Deepal/Deepal L07 → Deepal/L07
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4214; -- #91 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4214, '_serie_full_title', 'Deepal L07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #91
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4214, '_serie_api_value', 'Deepal L07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #91
UPDATE wp7j_terms SET name='S07', slug='s07' WHERE term_id=4212; -- #92 Deepal/Deepal S07 → Deepal/S07
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4212; -- #92 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4212, '_serie_full_title', 'Deepal S07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #92
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4212, '_serie_api_value', 'Deepal S07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #92
UPDATE wp7j_terms SET name='SL03', slug='sl03' WHERE term_id=4213; -- #108 Deepal/Deepal SL03 → Deepal/SL03
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4213; -- #108 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4213, '_serie_full_title', 'Deepal SL03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #108
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4213, '_serie_api_value', 'Deepal SL03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #108
UPDATE wp7j_terms SET name='S05', slug='s05' WHERE term_id=4211; -- #131 Deepal/Deepal S05 → Deepal/S05
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4211; -- #131 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4211, '_serie_full_title', 'Deepal S05') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #131
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4211, '_serie_api_value', 'Deepal S05') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #131
UPDATE wp7j_terms SET name='G318', slug='g318' WHERE term_id=4216; -- #166 Deepal/Deepal G318 → Deepal/G318
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4216; -- #166 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4216, '_serie_full_title', 'Deepal G318') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #166
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4216, '_serie_api_value', 'Deepal G318') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #166
UPDATE wp7j_terms SET name='S09', slug='s09' WHERE term_id=4215; -- #167 Deepal/Deepal S09 → Deepal/S09
UPDATE wp7j_term_taxonomy SET parent=4209 WHERE term_id=4215; -- #167 parent → Deepal
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4215, '_serie_full_title', 'Deepal S09') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #167
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4215, '_serie_api_value', 'Deepal S09') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #167
UPDATE wp7j_terms SET name='D9 DM-i', slug='d9-dm-i' WHERE term_id=4653; -- #15 Denza/Denza D9 DM → Denza/D9 DM-i
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4653; -- #15 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4653, '_serie_full_title', 'Denza D9 DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #15
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4653, '_serie_api_value', 'Denza D9 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #15
UPDATE wp7j_terms SET name='Z9 DM-i', slug='z9-dm-i' WHERE term_id=4654; -- #34 Denza/Denza Z9 DM → Denza/Z9 DM-i
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4654; -- #34 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4654, '_serie_full_title', 'Denza Z9 DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #34
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4654, '_serie_api_value', 'Denza Z9 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #34
UPDATE wp7j_terms SET name='N9 DM-i', slug='n9-dm-i' WHERE term_id=4656; -- #62 Denza/Denza N9 DM → Denza/N9 DM-i
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4656; -- #62 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4656, '_serie_full_title', 'Denza N9 DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #62
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4656, '_serie_api_value', 'Denza N9 DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #62
UPDATE wp7j_terms SET name='Z9 GT DM-i', slug='z9-gt-dm-i' WHERE term_id=4660; -- #63 Denza/Denza Z9 GT DM → Denza/Z9 GT DM-i
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4660; -- #63 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4660, '_serie_full_title', 'Denza Z9 GT DM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #63
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4660, '_serie_api_value', 'Denza Z9 GT DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #63
UPDATE wp7j_terms SET name='N7', slug='n7' WHERE term_id=4659; -- #168 Denza/Denza N7 → Denza/N7
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4659; -- #168 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4659, '_serie_full_title', 'Denza N7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #168
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4659, '_serie_api_value', 'Denza N7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #168
UPDATE wp7j_terms SET name='D9 EV', slug='d9-ev' WHERE term_id=4655; -- #219 Denza/Denza D9 EV → Denza/D9 EV
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4655; -- #219 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4655, '_serie_full_title', 'Denza D9 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #219
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4655, '_serie_api_value', 'Denza D9 EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #219
UPDATE wp7j_terms SET name='N8L DM', slug='n8l-dm' WHERE term_id=4652; -- #220 Denza/Denza N8L DM → Denza/N8L DM
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4652; -- #220 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4652, '_serie_full_title', 'Denza N8L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #220
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4652, '_serie_api_value', 'Denza N8L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #220
UPDATE wp7j_terms SET name='Z9 GT EV', slug='z9-gt-ev' WHERE term_id=4661; -- #221 Denza/Denza Z9 GT EV → Denza/Z9 GT EV
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=4661; -- #221 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4661, '_serie_full_title', 'Denza Z9 GT EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #221
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4661, '_serie_api_value', 'Denza Z9 GT EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #221
UPDATE wp7j_terms SET name='N8L', slug='n8l' WHERE term_id=6176; -- #222 Denza/N8L DM → Denza/N8L
UPDATE wp7j_term_taxonomy SET parent=4651 WHERE term_id=6176; -- #222 parent → Denza
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6176, '_serie_full_title', 'Denza N8L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #222
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6176, '_serie_api_value', 'N8L DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #222
UPDATE wp7j_terms SET name='Forting U-Tour V9', slug='forting-u-tour-v9' WHERE term_id=4698; -- #223 Dongfeng Fengxing/Xinghai V9 → Dongfeng/Forting U-Tour V9
UPDATE wp7j_term_taxonomy SET parent=5416 WHERE term_id=4698; -- #223 parent → Dongfeng
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4698, '_serie_full_title', 'Dongfeng Forting U-Tour V9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #223
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4698, '_serie_api_value', 'Xinghai V9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #223
UPDATE wp7j_terms SET slug='e-008' WHERE term_id=6258; -- #169 Dongfeng Yipai/eπ008 → Dongfeng/eπ008
UPDATE wp7j_term_taxonomy SET parent=5416 WHERE term_id=6258; -- #169 parent → Dongfeng
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6258, '_serie_full_title', 'Dongfeng eπ008') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #169
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6258, '_serie_api_value', 'eπ008') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #169
UPDATE wp7j_terms SET name='ET', slug='et' WHERE term_id=5196; -- #70 Exeed/Exlantix ET → Exlantix/ET
UPDATE wp7j_term_taxonomy SET parent=6524 WHERE term_id=5196; -- #70 parent → Exlantix
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5196, '_serie_full_title', 'Exeed Exlantix ET') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #70
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5196, '_serie_api_value', 'Exlantix ET') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #70
UPDATE wp7j_terms SET name='TXL', slug='txl' WHERE term_id=5198; -- #224 Exeed/Exeed Lingyun → Exeed/TXL
UPDATE wp7j_term_taxonomy SET parent=5192 WHERE term_id=5198; -- #224 parent → Exeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5198, '_serie_full_title', 'Exeed TXL Lingyun') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #224
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5198, '_serie_api_value', 'Exeed Lingyun') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #224
UPDATE wp7j_terms SET name='RX', slug='rx' WHERE term_id=5193; -- #225 Exeed/Exeed Yaoguang C-DM → Exeed/RX
UPDATE wp7j_term_taxonomy SET parent=5192 WHERE term_id=5193; -- #225 parent → Exeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5193, '_serie_full_title', 'Exeed RX (Omoda 9 SHS)') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #225
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5193, '_serie_api_value', 'Exeed Yaoguang C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #225
UPDATE wp7j_terms SET name='ET5' WHERE term_id=6238; -- #226 Exeed/星途ET5 → Exeed/ET5
UPDATE wp7j_term_taxonomy SET parent=5192 WHERE term_id=6238; -- #226 parent → Exeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6238, '_serie_full_title', 'Exeed ET5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #226
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6238, '_serie_api_value', '星途ET5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #226
UPDATE wp7j_terms SET name='M8', slug='m8' WHERE term_id=3372; -- #16 GAC Trumpchi/Trumpchi M8 → GAC/M8
UPDATE wp7j_term_taxonomy SET parent=6525 WHERE term_id=3372; -- #16 parent → GAC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3372, '_serie_full_title', 'GAC M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #16
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3372, '_serie_api_value', 'Trumpchi M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #16
UPDATE wp7j_terms SET name='GS8', slug='gs8' WHERE term_id=3375; -- #93 GAC Trumpchi/Trumpchi GS8 → GAC/GS8
UPDATE wp7j_term_taxonomy SET parent=6525 WHERE term_id=3375; -- #93 parent → GAC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3375, '_serie_full_title', 'GAC GS8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #93
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3375, '_serie_api_value', 'Trumpchi GS8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #93
UPDATE wp7j_terms SET name='GS4', slug='gs4' WHERE term_id=3374; -- #133 GAC Trumpchi/Trumpchi GS4 → GAC/GS4
UPDATE wp7j_term_taxonomy SET parent=6525 WHERE term_id=3374; -- #133 parent → GAC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3374, '_serie_full_title', 'GAC GS4') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #133
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3374, '_serie_api_value', 'Trumpchi GS4') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #133
UPDATE wp7j_terms SET name='Trumpchi M8', slug='trumpchi-m8' WHERE term_id=3381; -- #173 GAC Trumpchi/Trumpchi Xiangwang M8 → GAC/Trumpchi M8
UPDATE wp7j_term_taxonomy SET parent=6525 WHERE term_id=3381; -- #173 parent → GAC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3381, '_serie_full_title', 'GAC Trumpchi Xiangwang M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #173
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3381, '_serie_api_value', 'Trumpchi Xiangwang M8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #173
UPDATE wp7j_terms SET name='Trumpchi S7', slug='trumpchi-s7' WHERE term_id=3373; -- #174 GAC Trumpchi/Trumpchi Xiangwang S7 → GAC/Trumpchi S7
UPDATE wp7j_term_taxonomy SET parent=6525 WHERE term_id=3373; -- #174 parent → GAC
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3373, '_serie_full_title', 'GAC Trumpchi Xiangwang S7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #174
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3373, '_serie_api_value', 'Trumpchi Xiangwang S7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #174
-- #171 UNMAPPED: create serie 'Hyptec HT' pod make_id=6525
INSERT INTO wp7j_terms (name, slug, term_group) VALUES ('Hyptec HT', 'hyptec-ht', 0);
INSERT INTO wp7j_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (LAST_INSERT_ID(), 'serie', '', 6525, 0);
UPDATE wp7j_terms SET name='Monjaro', slug='monjaro' WHERE term_id=3644; -- #5 Geely/Xingyue L → Geely/Monjaro
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3644; -- #5 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3644, '_serie_full_title', 'Geely Monjaro') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #5
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3644, '_serie_api_value', 'Xingyue L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #5
UPDATE wp7j_terms SET name='Preface', slug='preface' WHERE term_id=3628; -- #7 Geely/Xingrui → Geely/Preface
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3628; -- #7 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3628, '_serie_full_title', 'Geely Preface') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #7
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3628, '_serie_api_value', 'Xingrui') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #7
UPDATE wp7j_terms SET name='Galaxy Starship 8 PHEV', slug='galaxy-starship-8-phev' WHERE term_id=3406; -- #30 Galaxy/Galaxy Xingyao 8 PHEV → Geely/Galaxy Starship 8 PHEV
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3406; -- #30 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3406, '_serie_full_title', 'Geely Galaxy Starship 8 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #30
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3406, '_serie_api_value', 'Galaxy Xingyao 8 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #30
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3401; -- #45 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3401, '_serie_full_title', 'Geely Galaxy L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #45
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3401, '_serie_api_value', 'Galaxy L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #45
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3407; -- #64 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3407, '_serie_full_title', 'Geely Galaxy Starship 7 EM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #64
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3407, '_serie_api_value', 'Galaxy Starship 7 EM-i') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #64
UPDATE wp7j_terms SET name='Galaxy A7 PHEV', slug='galaxy-a7-phev' WHERE term_id=6079; -- #77 Galaxy/银河A7 PHEV → Geely/Galaxy A7 PHEV
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=6079; -- #77 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6079, '_serie_full_title', 'Geely Galaxy A7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #77
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6079, '_serie_api_value', '银河A7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #77
UPDATE wp7j_terms SET name='Atlas Pro', slug='atlas-pro' WHERE term_id=3646; -- #78 Geely/Boyue L → Geely/Atlas Pro
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3646; -- #78 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3646, '_serie_full_title', 'Geely Atlas Pro') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #78
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3646, '_serie_api_value', 'Boyue L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #78
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3400; -- #109 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3400, '_serie_full_title', 'Geely Galaxy E8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #109
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3400, '_serie_api_value', 'Galaxy E8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #109
UPDATE wp7j_terms SET name='EX2', slug='ex2' WHERE term_id=3405; -- #110 Galaxy/Xingyuan → Geely/EX2
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3405; -- #110 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3405, '_serie_full_title', 'Geely Galaxy EX2') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #110
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3405, '_serie_api_value', 'Xingyuan') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #110
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3399; -- #134 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3399, '_serie_full_title', 'Geely Galaxy L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #134
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3399, '_serie_api_value', 'Galaxy L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #134
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3654; -- #135 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3654, '_serie_full_title', 'Geely Haoyue L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #135
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3654, '_serie_api_value', 'Haoyue L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #135
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=6517; -- #175 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6517, '_serie_full_title', 'Geely Galaxy A7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #175
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6517, '_serie_api_value', 'Galaxy A7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #175
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3397; -- #176 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3397, '_serie_full_title', 'Geely Galaxy E5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #176
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3397, '_serie_api_value', 'Galaxy E5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #176
UPDATE wp7j_terms SET name='Galaxy Starship 6', slug='galaxy-starship-6' WHERE term_id=6516; -- #177 Galaxy/Galaxy Xingyao 6 → Geely/Galaxy Starship 6
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=6516; -- #177 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6516, '_serie_full_title', 'Geely Galaxy Starship 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #177
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6516, '_serie_api_value', 'Galaxy Xingyao 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #177
UPDATE wp7j_terms SET name='LEVC L380', slug='levc-l380' WHERE term_id=3404; -- #229 Galaxy/Yizhen L380 → Geely/LEVC L380
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3404; -- #229 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3404, '_serie_full_title', 'Geely LEVC Yizhen L380') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #229
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3404, '_serie_api_value', 'Yizhen L380') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #229
UPDATE wp7j_terms SET name='Galaxy Starship 6', slug='galaxy-starship-6' WHERE term_id=6078; -- #230 Galaxy/银河星耀6 → Geely/Galaxy Starship 6
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=6078; -- #230 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6078, '_serie_full_title', 'Geely Galaxy Starship 6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #230
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6078, '_serie_api_value', '银河星耀6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #230
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3637; -- #231 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3637, '_serie_full_title', 'Geely Binrui') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #231
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3637, '_serie_api_value', 'Binrui') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #231
UPDATE wp7j_terms SET name='Coolray', slug='coolray' WHERE term_id=3630; -- #232 Geely/Binyue → Geely/Coolray
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3630; -- #232 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3630, '_serie_full_title', 'Geely Coolray') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #232
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3630, '_serie_api_value', 'Binyue') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #232
UPDATE wp7j_terms SET name='Atlas Pro', slug='atlas-pro' WHERE term_id=3632; -- #233 Geely/Boyue → Geely/Atlas Pro
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3632; -- #233 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3632, '_serie_full_title', 'Geely Atlas Pro Boyue') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #233
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3632, '_serie_api_value', 'Boyue') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #233
UPDATE wp7j_terms SET name='ICON', slug='icon' WHERE term_id=3658; -- #234 Geely/Geely ICON → Geely/ICON
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3658; -- #234 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3658, '_serie_full_title', 'Geely ICON') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #234
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3658, '_serie_api_value', 'Geely ICON') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #234
UPDATE wp7j_terms SET name='Galaxy E5', slug='galaxy-e5' WHERE term_id=3667; -- #235 Geely/Niuzai → Geely/Galaxy E5
UPDATE wp7j_term_taxonomy SET parent=3626 WHERE term_id=3667; -- #235 parent → Geely
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3667, '_serie_full_title', 'Geely Galaxy E5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #235
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3667, '_serie_api_value', 'Niuzai') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #235
UPDATE wp7j_terms SET name='Cannon', slug='cannon' WHERE term_id=4429; -- #111 Great Wall/Pao → GWM/Cannon
UPDATE wp7j_term_taxonomy SET parent=6526 WHERE term_id=4429; -- #111 parent → GWM
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4429, '_serie_full_title', 'GWM Cannon Great Wall Pao') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #111
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4429, '_serie_api_value', 'Pao') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #111
UPDATE wp7j_terms SET name='Cannon King Kong', slug='cannon-king-kong' WHERE term_id=4434; -- #236 Great Wall/King Kong Cannon → GWM/Cannon King Kong
UPDATE wp7j_term_taxonomy SET parent=6526 WHERE term_id=4434; -- #236 parent → GWM
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4434, '_serie_full_title', 'GWM Cannon King Kong') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #236
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4434, '_serie_api_value', 'King Kong Cannon') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #236
UPDATE wp7j_terms SET name='Big Dog', slug='big-dog' WHERE term_id=4419; -- #79 Haval/Haval Big Dog → Haval/Big Dog
UPDATE wp7j_term_taxonomy SET parent=4397 WHERE term_id=4419; -- #79 parent → Haval
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4419, '_serie_full_title', 'Haval Big Dog Dargo') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #79
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4419, '_serie_api_value', 'Haval Big Dog') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #79
UPDATE wp7j_terms SET name='H6', slug='h6' WHERE term_id=4398; -- #178 Haval/Haval H6 → Haval/H6
UPDATE wp7j_term_taxonomy SET parent=4397 WHERE term_id=4398; -- #178 parent → Haval
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4398, '_serie_full_title', 'Haval H6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #178
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4398, '_serie_api_value', 'Haval H6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #178
UPDATE wp7j_terms SET name='Xiaolong MAX', slug='xiaolong-max' WHERE term_id=6515; -- #179 Haval/Haval Xiaolong MAX → Haval/Xiaolong MAX
UPDATE wp7j_term_taxonomy SET parent=4397 WHERE term_id=6515; -- #179 parent → Haval
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6515, '_serie_full_title', 'Haval Xiaolong MAX H6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #179
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6515, '_serie_api_value', 'Haval Xiaolong MAX') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #179
UPDATE wp7j_terms SET name='H5', slug='h5' WHERE term_id=4409; -- #237 Haval/Haval H5 → Haval/H5
UPDATE wp7j_term_taxonomy SET parent=4397 WHERE term_id=4409; -- #237 parent → Haval
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4409, '_serie_full_title', 'Haval H5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #237
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4409, '_serie_api_value', 'Haval H5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #237
UPDATE wp7j_terms SET name='Raptor (benzyna)', slug='raptor' WHERE term_id=6397; -- #238 Haval/哈弗猛龙燃油版 → Haval/Raptor (benzyna)
UPDATE wp7j_term_taxonomy SET parent=4397 WHERE term_id=6397; -- #238 parent → Haval
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6397, '_serie_full_title', 'Haval Raptor (benzyna)') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #238
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6397, '_serie_api_value', '哈弗猛龙燃油版') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #238
UPDATE wp7j_terms SET name='H5', slug='h5' WHERE term_id=5002; -- #8 Hongqi/Hongqi H5 → Hongqi/H5
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5002; -- #8 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5002, '_serie_full_title', 'Hongqi H5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #8
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5002, '_serie_api_value', 'Hongqi H5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #8
UPDATE wp7j_terms SET name='EH7', slug='eh7' WHERE term_id=5012; -- #112 Hongqi/Hongqi EH7 → Hongqi/EH7
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5012; -- #112 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5012, '_serie_full_title', 'Hongqi EH7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #112
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5012, '_serie_api_value', 'Hongqi EH7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #112
UPDATE wp7j_terms SET name='HQ9 PHEV', slug='hq9-phev' WHERE term_id=5022; -- #113 Hongqi/Hongqi HQ9 PHEV → Hongqi/HQ9 PHEV
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5022; -- #113 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5022, '_serie_full_title', 'Hongqi HQ9 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #113
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5022, '_serie_api_value', 'Hongqi HQ9 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #113
UPDATE wp7j_terms SET name='HS7 PHEV', slug='hs7-phev' WHERE term_id=5014; -- #114 Hongqi/Hongqi HS7 PHEV → Hongqi/HS7 PHEV
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5014; -- #114 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5014, '_serie_full_title', 'Hongqi HS7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #114
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5014, '_serie_api_value', 'Hongqi HS7 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #114
UPDATE wp7j_terms SET name='Tiangong 08', slug='tiangong-08' WHERE term_id=5005; -- #115 Hongqi/Hongqi Tiangong 08 → Hongqi/Tiangong 08
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5005; -- #115 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5005, '_serie_full_title', 'Hongqi Tiangong 08') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #115
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5005, '_serie_api_value', 'Hongqi Tiangong 08') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #115
UPDATE wp7j_terms SET name='H9', slug='h9' WHERE term_id=5011; -- #136 Hongqi/Hongqi H9 → Hongqi/H9
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5011; -- #136 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5011, '_serie_full_title', 'Hongqi H9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #136
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5011, '_serie_api_value', 'Hongqi H9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #136
UPDATE wp7j_terms SET name='HS3', slug='hs3' WHERE term_id=5009; -- #137 Hongqi/Hongqi HS3 → Hongqi/HS3
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5009; -- #137 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5009, '_serie_full_title', 'Hongqi HS3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #137
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5009, '_serie_api_value', 'Hongqi HS3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #137
UPDATE wp7j_terms SET name='HS3 PHEV', slug='hs3-phev' WHERE term_id=5023; -- #180 Hongqi/Hongqi HS3 PHEV → Hongqi/HS3 PHEV
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5023; -- #180 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5023, '_serie_full_title', 'Hongqi HS3 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #180
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5023, '_serie_api_value', 'Hongqi HS3 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #180
UPDATE wp7j_terms SET name='HS5', slug='hs5' WHERE term_id=5000; -- #181 Hongqi/Hongqi HS5 → Hongqi/HS5
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5000; -- #181 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5000, '_serie_full_title', 'Hongqi HS5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #181
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5000, '_serie_api_value', 'Hongqi HS5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #181
UPDATE wp7j_terms SET name='Tiangong 05', slug='tiangong-05' WHERE term_id=5017; -- #182 Hongqi/Hongqi Tiangong 05 → Hongqi/Tiangong 05
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5017; -- #182 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5017, '_serie_full_title', 'Hongqi Tiangong 05') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #182
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5017, '_serie_api_value', 'Hongqi Tiangong 05') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #182
UPDATE wp7j_terms SET name='Tiangong 06', slug='tiangong-06' WHERE term_id=5018; -- #183 Hongqi/Hongqi Tiangong 06 → Hongqi/Tiangong 06
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5018; -- #183 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5018, '_serie_full_title', 'Hongqi Tiangong 06') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #183
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5018, '_serie_api_value', 'Hongqi Tiangong 06') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #183
UPDATE wp7j_terms SET name='H5 PHEV', slug='h5-phev' WHERE term_id=5016; -- #239 Hongqi/Hongqi H5 PHEV → Hongqi/H5 PHEV
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5016; -- #239 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5016, '_serie_full_title', 'Hongqi H5 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #239
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5016, '_serie_api_value', 'Hongqi H5 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #239
UPDATE wp7j_terms SET name='H6', slug='h6' WHERE term_id=5015; -- #240 Hongqi/Hongqi H6 → Hongqi/H6
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5015; -- #240 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5015, '_serie_full_title', 'Hongqi H6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #240
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5015, '_serie_api_value', 'Hongqi H6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #240
UPDATE wp7j_terms SET name='HQ9', slug='hq9' WHERE term_id=5020; -- #241 Hongqi/Hongqi HQ9 → Hongqi/HQ9
UPDATE wp7j_term_taxonomy SET parent=4998 WHERE term_id=5020; -- #241 parent → Hongqi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5020, '_serie_full_title', 'Hongqi HQ9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #241
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5020, '_serie_api_value', 'Hongqi HQ9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #241
UPDATE wp7j_terms SET name='Super V23', slug='super-v23' WHERE term_id=5517; -- #102 iCAR/iCAR Super V23 → iCAR/Super V23
UPDATE wp7j_term_taxonomy SET parent=5516 WHERE term_id=5517; -- #102 parent → iCAR
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5517, '_serie_full_title', 'iCAR Super V23') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #102
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5517, '_serie_api_value', 'iCAR Super V23') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #102
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=5518; -- #148 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5518, '_serie_full_title', 'Chery iCAR 03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #148
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5518, '_serie_api_value', 'iCAR 03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #148
UPDATE wp7j_term_taxonomy SET parent=3578 WHERE term_id=6508; -- #266 parent → Chery
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6508, '_serie_full_title', 'Chery iCAR V27') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #266
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6508, '_serie_api_value', 'iCAR V27') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #266
UPDATE wp7j_term_taxonomy SET parent=5204 WHERE term_id=5205; -- #80 parent → IM Motors
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5205, '_serie_full_title', 'IM Motors IM L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #80
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5205, '_serie_api_value', 'IM L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #80
UPDATE wp7j_term_taxonomy SET parent=5204 WHERE term_id=5209; -- #138 parent → IM Motors
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5209, '_serie_full_title', 'IM Motors IM LS6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #138
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5209, '_serie_api_value', 'IM LS6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #138
UPDATE wp7j_term_taxonomy SET parent=5204 WHERE term_id=6507; -- #184 parent → IM Motors
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6507, '_serie_full_title', 'IM Motors LS9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #184
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6507, '_serie_api_value', 'LS9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #184
UPDATE wp7j_terms SET name='X70 PLUS', slug='x70-plus' WHERE term_id=4528; -- #81 Jetour/Jetour X70 PLUS → Jetour/X70 PLUS
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4528; -- #81 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4528, '_serie_full_title', 'Jetour X70 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #81
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4528, '_serie_api_value', 'Jetour X70 PLUS') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #81
UPDATE wp7j_terms SET name='Dashing', slug='dashing' WHERE term_id=4530; -- #116 Jetour/Jetour Dasheng → Jetour/Dashing
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4530; -- #116 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4530, '_serie_full_title', 'Jetour Dashing') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #116
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4530, '_serie_api_value', 'Jetour Dasheng') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #116
UPDATE wp7j_terms SET name='T2', slug='t2' WHERE term_id=4534; -- #117 Jetour/Jetour Traveller → Jetour/T2
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4534; -- #117 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4534, '_serie_full_title', 'Jetour T2 Traveller') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #117
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4534, '_serie_api_value', 'Jetour Traveller') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #117
UPDATE wp7j_terms SET name='T2 C-DM', slug='t2-c-dm' WHERE term_id=6518; -- #185 Jetour Shanhai/Jetour Traveller C-DM → Jetour/T2 C-DM
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=6518; -- #185 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6518, '_serie_full_title', 'Jetour T2 C-DM Traveller') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #185
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6518, '_serie_api_value', 'Jetour Traveller C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #185
UPDATE wp7j_terms SET name='X70', slug='x70' WHERE term_id=4529; -- #242 Jetour/Jetour X70 → Jetour/X70
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4529; -- #242 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4529, '_serie_full_title', 'Jetour X70') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #242
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4529, '_serie_api_value', 'Jetour X70') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #242
UPDATE wp7j_terms SET name='X70 C-DM', slug='x70-c-dm' WHERE term_id=4532; -- #243 Jetour/Jetour X70 C-DM → Jetour/X70 C-DM
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4532; -- #243 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4532, '_serie_full_title', 'Jetour X70 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #243
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4532, '_serie_api_value', 'Jetour X70 C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #243
UPDATE wp7j_terms SET name='X90 PRO', slug='x90-pro' WHERE term_id=4537; -- #244 Jetour/Jetour X90 PRO → Jetour/X90 PRO
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=4537; -- #244 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4537, '_serie_full_title', 'Jetour X90 PRO') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #244
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4537, '_serie_api_value', 'Jetour X90 PRO') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #244
UPDATE wp7j_terms SET name='Shanhai L7', slug='shanhai-l7' WHERE term_id=5628; -- #245 Jetour Shanhai/Jetour Shanhai L7 → Jetour/Shanhai L7
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=5628; -- #245 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5628, '_serie_full_title', 'Jetour Shanhai L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #245
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5628, '_serie_api_value', 'Jetour Shanhai L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #245
UPDATE wp7j_terms SET name='Shanhai L9', slug='shanhai-l9' WHERE term_id=5624; -- #246 Jetour Shanhai/Jetour Shanhai L9 → Jetour/Shanhai L9
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=5624; -- #246 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5624, '_serie_full_title', 'Jetour Shanhai L9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #246
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5624, '_serie_api_value', 'Jetour Shanhai L9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #246
UPDATE wp7j_terms SET name='T2 C-DM', slug='t2-c-dm' WHERE term_id=6510; -- #247 Jetour Shanhai/捷途旅行者C-DM → Jetour/T2 C-DM
UPDATE wp7j_term_taxonomy SET parent=4525 WHERE term_id=6510; -- #247 parent → Jetour
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6510, '_serie_full_title', 'Jetour T2 Traveler C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #247
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6510, '_serie_api_value', '捷途旅行者C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #247
UPDATE wp7j_terms SET name='C11', slug='c11' WHERE term_id=5155; -- #55 Leapmotor/Leapmotor C11 → Leapmotor/C11
UPDATE wp7j_term_taxonomy SET parent=5152 WHERE term_id=5155; -- #55 parent → Leapmotor
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5155, '_serie_full_title', 'Leapmotor C11') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #55
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5155, '_serie_api_value', 'Leapmotor C11') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #55
UPDATE wp7j_terms SET name='C16', slug='c16' WHERE term_id=5154; -- #56 Leapmotor/Leapmotor C16 → Leapmotor/C16
UPDATE wp7j_term_taxonomy SET parent=5152 WHERE term_id=5154; -- #56 parent → Leapmotor
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5154, '_serie_full_title', 'Leapmotor C16') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #56
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5154, '_serie_api_value', 'Leapmotor C16') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #56
UPDATE wp7j_terms SET name='C10', slug='c10' WHERE term_id=5156; -- #65 Leapmotor/Leapmotor C10 → Leapmotor/C10
UPDATE wp7j_term_taxonomy SET parent=5152 WHERE term_id=5156; -- #65 parent → Leapmotor
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5156, '_serie_full_title', 'Leapmotor C10') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #65
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5156, '_serie_api_value', 'Leapmotor C10') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #65
UPDATE wp7j_terms SET name='B10', slug='b10' WHERE term_id=5153; -- #139 Leapmotor/Leapmotor B10 → Leapmotor/B10
UPDATE wp7j_term_taxonomy SET parent=5152 WHERE term_id=5153; -- #139 parent → Leapmotor
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5153, '_serie_full_title', 'Leapmotor B10') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #139
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5153, '_serie_api_value', 'Leapmotor B10') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #139
UPDATE wp7j_terms SET name='B05', slug='b05' WHERE term_id=6227; -- #186 Leapmotor/零跑Lafa5 → Leapmotor/B05
UPDATE wp7j_term_taxonomy SET parent=5152 WHERE term_id=6227; -- #186 parent → Leapmotor
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6227, '_serie_full_title', 'Leapmotor B05') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #186
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6227, '_serie_api_value', '零跑Lafa5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #186
UPDATE wp7j_terms SET name='L6', slug='l6' WHERE term_id=5735; -- #31 Li Auto/Li Auto L6 → Li Auto/L6
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5735; -- #31 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5735, '_serie_full_title', 'Li Auto L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #31
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5735, '_serie_api_value', 'Li Auto L6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #31
UPDATE wp7j_terms SET name='L7', slug='l7' WHERE term_id=5739; -- #39 Li Auto/Li Auto L7 → Li Auto/L7
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5739; -- #39 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5739, '_serie_full_title', 'Li Auto L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #39
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5739, '_serie_api_value', 'Li Auto L7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #39
UPDATE wp7j_terms SET name='i6', slug='i6' WHERE term_id=5740; -- #57 Li Auto/Li Auto i6 → Li Auto/i6
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5740; -- #57 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5740, '_serie_full_title', 'Li Auto i6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #57
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5740, '_serie_api_value', 'Li Auto i6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #57
UPDATE wp7j_terms SET name='L9', slug='l9' WHERE term_id=5737; -- #71 Li Auto/Li Auto L9 → Li Auto/L9
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5737; -- #71 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5737, '_serie_full_title', 'Li Auto L9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #71
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5737, '_serie_api_value', 'Li Auto L9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #71
UPDATE wp7j_terms SET name='i8', slug='i8' WHERE term_id=5741; -- #187 Li Auto/Li Auto i8 → Li Auto/i8
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5741; -- #187 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5741, '_serie_full_title', 'Li Auto i8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #187
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5741, '_serie_api_value', 'Li Auto i8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #187
UPDATE wp7j_terms SET name='i6', slug='i6' WHERE term_id=5740; -- #188 Li Auto/Li Auto i6 → Li Auto/i6
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5740; -- #188 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5740, '_serie_full_title', 'Li Auto i6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #188
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5740, '_serie_api_value', 'Li Auto i6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #188
UPDATE wp7j_terms SET name='L8', slug='l8' WHERE term_id=5736; -- #248 Li Auto/Li Auto L8 → Li Auto/L8
UPDATE wp7j_term_taxonomy SET parent=5733 WHERE term_id=5736; -- #248 parent → Li Auto
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5736, '_serie_full_title', 'Li Auto L8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #248
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5736, '_serie_api_value', 'Li Auto L8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #248
UPDATE wp7j_term_taxonomy SET parent=5638 WHERE term_id=5667; -- #140 parent → Lotus
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5667, '_serie_full_title', 'Lotus Emeya') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #140
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5667, '_serie_api_value', 'Emeya') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #140
UPDATE wp7j_terms SET name='R7', slug='r7' WHERE term_id=5675; -- #36 Maextro/Luxeed R7 → Luxeed/R7
UPDATE wp7j_term_taxonomy SET parent=6527 WHERE term_id=5675; -- #36 parent → Luxeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5675, '_serie_full_title', 'Luxeed R7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #36
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5675, '_serie_api_value', 'Luxeed R7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #36
UPDATE wp7j_terms SET name='S7', slug='s7' WHERE term_id=5674; -- #118 Maextro/Luxeed S7 → Luxeed/S7
UPDATE wp7j_term_taxonomy SET parent=6527 WHERE term_id=5674; -- #118 parent → Luxeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5674, '_serie_full_title', 'Luxeed S7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #118
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5674, '_serie_api_value', 'Luxeed S7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #118
-- #189 UNMAPPED: create serie '900' pod make_id=4597
INSERT INTO wp7j_terms (name, slug, term_group) VALUES ('900', '900', 0);
INSERT INTO wp7j_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (LAST_INSERT_ID(), 'serie', '', 4597, 0);
UPDATE wp7j_terms SET name='CX-5', slug='cx-5' WHERE term_id=5276; -- #58 Mazda/Mazda CX-5 → Mazda/CX-5
UPDATE wp7j_term_taxonomy SET parent=5273 WHERE term_id=5276; -- #58 parent → Mazda
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5276, '_serie_full_title', 'Mazda CX-5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #58
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5276, '_serie_api_value', 'Mazda CX-5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #58
UPDATE wp7j_term_taxonomy SET parent=5273 WHERE term_id=6249; -- #192 parent → Mazda
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6249, '_serie_full_title', 'Mazda EZ-60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #192
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6249, '_serie_api_value', 'EZ-60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #192
UPDATE wp7j_terms SET name='EZ-6', slug='ez-6' WHERE term_id=5291; -- #193 Mazda/Mazda EZ-6 → Mazda/EZ-6
UPDATE wp7j_term_taxonomy SET parent=5273 WHERE term_id=5291; -- #193 parent → Mazda
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5291, '_serie_full_title', 'Mazda EZ-6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #193
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5291, '_serie_api_value', 'Mazda EZ-6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #193
UPDATE wp7j_terms SET name='CX-50', slug='cx-50' WHERE term_id=5285; -- #249 Mazda/Mazda CX-50 → Mazda/CX-50
UPDATE wp7j_term_taxonomy SET parent=5273 WHERE term_id=5285; -- #249 parent → Mazda
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5285, '_serie_full_title', 'Mazda CX-50') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #249
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5285, '_serie_api_value', 'Mazda CX-50') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #249
UPDATE wp7j_terms SET name='7', slug='7' WHERE term_id=3492; -- #141 MG/MG 7 → MG/7
UPDATE wp7j_term_taxonomy SET parent=3491 WHERE term_id=3492; -- #141 parent → MG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3492, '_serie_full_title', 'MG 7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #141
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3492, '_serie_api_value', 'MG 7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #141
UPDATE wp7j_terms SET name='ES6', slug='es6' WHERE term_id=4322; -- #27 NIO/NIO ES6 → NIO/ES6
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4322; -- #27 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4322, '_serie_full_title', 'NIO ES6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #27
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4322, '_serie_api_value', 'NIO ES6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #27
UPDATE wp7j_terms SET name='ES8', slug='es8' WHERE term_id=4324; -- #28 NIO/NIO ES8 → NIO/ES8
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4324; -- #28 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4324, '_serie_full_title', 'NIO ES8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #28
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4324, '_serie_api_value', 'NIO ES8') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #28
UPDATE wp7j_terms SET name='ET5 Touring', slug='et5-touring' WHERE term_id=4325; -- #40 NIO/NIO ET5T → NIO/ET5 Touring
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4325; -- #40 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4325, '_serie_full_title', 'NIO ET5 Touring') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #40
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4325, '_serie_api_value', 'NIO ET5T') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #40
UPDATE wp7j_terms SET name='ET5', slug='et5' WHERE term_id=4328; -- #66 NIO/NIO ET5 → NIO/ET5
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4328; -- #66 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4328, '_serie_full_title', 'NIO ET5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #66
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4328, '_serie_api_value', 'NIO ET5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #66
UPDATE wp7j_terms SET name='EC6', slug='ec6' WHERE term_id=4323; -- #119 NIO/NIO EC6 → NIO/EC6
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4323; -- #119 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4323, '_serie_full_title', 'NIO EC6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #119
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4323, '_serie_api_value', 'NIO EC6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #119
UPDATE wp7j_terms SET name='ET7', slug='et7' WHERE term_id=4321; -- #194 NIO/NIO ET7 → NIO/ET7
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4321; -- #194 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4321, '_serie_full_title', 'NIO ET7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #194
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4321, '_serie_api_value', 'NIO ET7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #194
UPDATE wp7j_terms SET name='ET9', slug='et9' WHERE term_id=4326; -- #195 NIO/NIO ET9 → NIO/ET9
UPDATE wp7j_term_taxonomy SET parent=4320 WHERE term_id=4326; -- #195 parent → NIO
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4326, '_serie_full_title', 'NIO ET9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #195
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4326, '_serie_api_value', 'NIO ET9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #195
UPDATE wp7j_terms SET name='N7', slug='n7' WHERE term_id=3949; -- #82 Nissan/Nissan N7 → Nissan/N7
UPDATE wp7j_term_taxonomy SET parent=3935 WHERE term_id=3949; -- #82 parent → Nissan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3949, '_serie_full_title', 'Nissan N7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #82
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3949, '_serie_api_value', 'Nissan N7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #82
UPDATE wp7j_terms SET name='N6', slug='n6' WHERE term_id=6514; -- #83 Nissan/Nissan N6 → Nissan/N6
UPDATE wp7j_term_taxonomy SET parent=3935 WHERE term_id=6514; -- #83 parent → Nissan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6514, '_serie_full_title', 'Nissan N6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #83
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6514, '_serie_api_value', 'Nissan N6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #83
UPDATE wp7j_terms SET name='N6', slug='n6' WHERE term_id=6514; -- #142 Nissan/Nissan N6 → Nissan/N6
UPDATE wp7j_term_taxonomy SET parent=3935 WHERE term_id=6514; -- #142 parent → Nissan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6514, '_serie_full_title', 'Nissan N6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #142
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6514, '_serie_api_value', 'Nissan N6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #142
UPDATE wp7j_term_taxonomy SET parent=3935 WHERE term_id=3936; -- #250 parent → Nissan
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3936, '_serie_full_title', 'Nissan Sylphy') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #250
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3936, '_serie_api_value', 'Sylphy') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #250
UPDATE wp7j_terms SET name='A07', slug='a07' WHERE term_id=4772; -- #107 Changan Qiyuan/Changan Qiyuan A07 → Nevo/A07
UPDATE wp7j_term_taxonomy SET parent=6528 WHERE term_id=4772; -- #107 parent → Nevo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4772, '_serie_full_title', 'Qiyuan Nevo A07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #107
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4772, '_serie_api_value', 'Changan Qiyuan A07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #107
UPDATE wp7j_terms SET name='Q07', slug='q07' WHERE term_id=4770; -- #159 Changan Qiyuan/Changan Qiyuan Q07 → Nevo/Q07
UPDATE wp7j_term_taxonomy SET parent=6528 WHERE term_id=4770; -- #159 parent → Nevo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4770, '_serie_full_title', 'Qiyuan Nevo Q07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #159
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4770, '_serie_api_value', 'Changan Qiyuan Q07') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #159
UPDATE wp7j_terms SET name='#5', slug='5' WHERE term_id=3975; -- #197 Smart/smart #5 → Smart/#5
UPDATE wp7j_term_taxonomy SET parent=3970 WHERE term_id=3975; -- #197 parent → Smart
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3975, '_serie_full_title', 'Smart #5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #197
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3975, '_serie_api_value', 'smart #5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #197
UPDATE wp7j_terms SET name='300', slug='300' WHERE term_id=3978; -- #67 Tank/Tank 300 → Tank/300
UPDATE wp7j_term_taxonomy SET parent=3976 WHERE term_id=3978; -- #67 parent → Tank
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3978, '_serie_full_title', 'Tank 300') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #67
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3978, '_serie_api_value', 'Tank 300') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #67
UPDATE wp7j_terms SET name='400 Hi4-T', slug='400-hi4-t' WHERE term_id=3984; -- #252 Tank/Tank 400 Hi4-T → Tank/400 Hi4-T
UPDATE wp7j_term_taxonomy SET parent=3976 WHERE term_id=3984; -- #252 parent → Tank
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3984, '_serie_full_title', 'Tank 400 Hi4-T') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #252
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3984, '_serie_api_value', 'Tank 400 Hi4-T') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #252
UPDATE wp7j_terms SET name='700 Hi4-T', slug='700-hi4-t' WHERE term_id=3981; -- #253 Tank/Tank 700 Hi4-T → Tank/700 Hi4-T
UPDATE wp7j_term_taxonomy SET parent=3976 WHERE term_id=3981; -- #253 parent → Tank
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3981, '_serie_full_title', 'Tank 700 Hi4-T') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #253
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3981, '_serie_api_value', 'Tank 700 Hi4-T') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #253
UPDATE wp7j_terms SET name='Jetta', slug='jetta' WHERE term_id=4259; -- #41 Volkswagen/Lavida → Volkswagen/Jetta
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4259; -- #41 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4259, '_serie_full_title', 'Volkswagen Lavida Jetta') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #41
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4259, '_serie_api_value', 'Lavida') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #41
UPDATE wp7j_terms SET name='Passat CN', slug='passat-cn' WHERE term_id=4271; -- #46 Volkswagen/Passat → Volkswagen/Passat CN
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4271; -- #46 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4271, '_serie_full_title', 'Volkswagen Passat CN') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #46
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4271, '_serie_api_value', 'Passat') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #46
UPDATE wp7j_terms SET name='Tiguan L LWB', slug='tiguan-l-lwb' WHERE term_id=4274; -- #47 Volkswagen/Tiguan L → Volkswagen/Tiguan L LWB
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4274; -- #47 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4274, '_serie_full_title', 'Volkswagen Tiguan L LWB') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #47
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4274, '_serie_api_value', 'Tiguan L') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #47
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4260; -- #72 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4260, '_serie_full_title', 'Volkswagen Sagitar') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #72
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4260, '_serie_api_value', 'Sagitar') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #72
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4265; -- #84 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4265, '_serie_full_title', 'Volkswagen Lamando') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #84
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4265, '_serie_api_value', 'Lamando') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #84
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4280; -- #85 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4280, '_serie_full_title', 'Volkswagen Teramont') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #85
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4280, '_serie_api_value', 'Teramont') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #85
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4252; -- #94 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4252, '_serie_full_title', 'Volkswagen Tayron') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #94
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4252, '_serie_api_value', 'Tayron') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #94
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4261; -- #95 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4261, '_serie_full_title', 'Volkswagen Tharu') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #95
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4261, '_serie_api_value', 'Tharu') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #95
UPDATE wp7j_terms SET name='ID.3', slug='id-3' WHERE term_id=4301; -- #96 Volkswagen/Volkswagen ID.3 → Volkswagen/ID.3
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4301; -- #96 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4301, '_serie_full_title', 'Volkswagen ID.3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #96
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4301, '_serie_api_value', 'Volkswagen ID.3') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #96
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4256; -- #144 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4256, '_serie_full_title', 'Volkswagen Bora') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #144
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4256, '_serie_api_value', 'Bora') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #144
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4281; -- #145 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4281, '_serie_full_title', 'Volkswagen Magotan') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #145
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4281, '_serie_api_value', 'Magotan') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #145
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4254; -- #198 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4254, '_serie_full_title', 'Volkswagen Golf') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #198
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4254, '_serie_api_value', 'Golf') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #198
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4297; -- #199 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4297, '_serie_full_title', 'Volkswagen ID.4 CROZZ') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #199
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4297, '_serie_api_value', 'ID.4 CROZZ') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #199
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4258; -- #200 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4258, '_serie_full_title', 'Volkswagen ID.4 X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #200
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4258, '_serie_api_value', 'ID.4 X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #200
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4289; -- #201 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4289, '_serie_full_title', 'Volkswagen T-Roc') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #201
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4289, '_serie_api_value', 'T-Roc') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #201
UPDATE wp7j_terms SET name='CC', slug='cc' WHERE term_id=4276; -- #202 Volkswagen/Volkswagen CC → Volkswagen/CC
UPDATE wp7j_term_taxonomy SET parent=4251 WHERE term_id=4276; -- #202 parent → Volkswagen
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4276, '_serie_full_title', 'Volkswagen CC') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #202
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4276, '_serie_api_value', 'Volkswagen CC') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #202
UPDATE wp7j_terms SET name='S90', slug='s90' WHERE term_id=3997; -- #48 Volvo/Volvo S90 → Volvo/S90
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3997; -- #48 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3997, '_serie_full_title', 'Volvo S90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #48
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3997, '_serie_api_value', 'Volvo S90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #48
UPDATE wp7j_terms SET name='XC60', slug='xc60' WHERE term_id=3994; -- #68 Volvo/Volvo XC60 → Volvo/XC60
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3994; -- #68 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3994, '_serie_full_title', 'Volvo XC60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #68
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3994, '_serie_api_value', 'Volvo XC60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #68
UPDATE wp7j_terms SET name='S60', slug='s60' WHERE term_id=3998; -- #97 Volvo/Volvo S60 → Volvo/S60
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3998; -- #97 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3998, '_serie_full_title', 'Volvo S60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #97
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3998, '_serie_api_value', 'Volvo S60') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #97
UPDATE wp7j_terms SET name='EM90', slug='em90' WHERE term_id=3990; -- #146 Volvo/Volvo EM90 → Volvo/EM90
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3990; -- #146 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3990, '_serie_full_title', 'Volvo EM90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #146
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3990, '_serie_api_value', 'Volvo EM90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #146
UPDATE wp7j_terms SET name='XC90', slug='xc90' WHERE term_id=3987; -- #147 Volvo/Volvo XC90 → Volvo/XC90
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3987; -- #147 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3987, '_serie_full_title', 'Volvo XC90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #147
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3987, '_serie_api_value', 'Volvo XC90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #147
UPDATE wp7j_terms SET name='S90 T8 PHEV', slug='s90-t8-phev' WHERE term_id=3989; -- #254 Volvo/Volvo S90 PHEV → Volvo/S90 T8 PHEV
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3989; -- #254 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3989, '_serie_full_title', 'Volvo S90 T8 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #254
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3989, '_serie_api_value', 'Volvo S90 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #254
UPDATE wp7j_terms SET name='V90', slug='v90' WHERE term_id=4000; -- #255 Volvo/Volvo V90 → Volvo/V90
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=4000; -- #255 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4000, '_serie_full_title', 'Volvo V90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #255
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4000, '_serie_api_value', 'Volvo V90') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #255
UPDATE wp7j_terms SET name='XC40', slug='xc40' WHERE term_id=3999; -- #256 Volvo/Volvo XC40 → Volvo/XC40
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3999; -- #256 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3999, '_serie_full_title', 'Volvo XC40') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #256
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3999, '_serie_api_value', 'Volvo XC40') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #256
UPDATE wp7j_terms SET name='XC60 T8 PHEV', slug='xc60-t8-phev' WHERE term_id=3991; -- #257 Volvo/Volvo XC60 PHEV → Volvo/XC60 T8 PHEV
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=3991; -- #257 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3991, '_serie_full_title', 'Volvo XC60 T8 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #257
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (3991, '_serie_api_value', 'Volvo XC60 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #257
UPDATE wp7j_terms SET name='XC90 T8 PHEV', slug='xc90-t8-phev' WHERE term_id=4002; -- #258 Volvo/Volvo XC90 PHEV → Volvo/XC90 T8 PHEV
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=4002; -- #258 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4002, '_serie_full_title', 'Volvo XC90 T8 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #258
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4002, '_serie_api_value', 'Volvo XC90 PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #258
UPDATE wp7j_term_taxonomy SET parent=3986 WHERE term_id=6511; -- #259 parent → Volvo
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6511, '_serie_full_title', 'Volvo XC70') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #259
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6511, '_serie_api_value', 'XC70') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #259
UPDATE wp7j_terms SET name='Dream PHEV', slug='dream-phev' WHERE term_id=5074; -- #120 Voyah/Voyah Dreamer PHEV → Voyah/Dream PHEV
UPDATE wp7j_term_taxonomy SET parent=5073 WHERE term_id=5074; -- #120 parent → Voyah
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5074, '_serie_full_title', 'Voyah Dream PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #120
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5074, '_serie_api_value', 'Voyah Dreamer PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #120
UPDATE wp7j_terms SET name='FREE', slug='free' WHERE term_id=5075; -- #121 Voyah/Voyah FREE → Voyah/FREE
UPDATE wp7j_term_taxonomy SET parent=5073 WHERE term_id=5075; -- #121 parent → Voyah
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5075, '_serie_full_title', 'Voyah FREE') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #121
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5075, '_serie_api_value', 'Voyah FREE') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #121
UPDATE wp7j_term_taxonomy SET parent=5073 WHERE term_id=6494; -- #203 parent → Voyah
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6494, '_serie_full_title', 'Voyah Taishan') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #203
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6494, '_serie_api_value', 'Taishan') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #203
UPDATE wp7j_terms SET name='Dream EV', slug='dream-ev' WHERE term_id=5076; -- #260 Voyah/Voyah Dreamer EV → Voyah/Dream EV
UPDATE wp7j_term_taxonomy SET parent=5073 WHERE term_id=5076; -- #260 parent → Voyah
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5076, '_serie_full_title', 'Voyah Dream EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #260
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5076, '_serie_api_value', 'Voyah Dreamer EV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #260
UPDATE wp7j_terms SET name='Zhiyin', slug='zhiyin' WHERE term_id=5077; -- #261 Voyah/Voyah Zhiyin → Voyah/Zhiyin
UPDATE wp7j_term_taxonomy SET parent=5073 WHERE term_id=5077; -- #261 parent → Voyah
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5077, '_serie_full_title', 'Voyah Zhiyin') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #261
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5077, '_serie_api_value', 'Voyah Zhiyin') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #261
UPDATE wp7j_terms SET name='07', slug='07' WHERE term_id=5388; -- #98 WEY/Blue Mountain → WEY/07
UPDATE wp7j_term_taxonomy SET parent=5378 WHERE term_id=5388; -- #98 parent → WEY
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5388, '_serie_full_title', 'WEY 07 Blue Mountain') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #98
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5388, '_serie_api_value', 'Blue Mountain') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #98
UPDATE wp7j_terms SET name='03 PHEV', slug='03-phev' WHERE term_id=5387; -- #262 WEY/Latte PHEV → WEY/03 PHEV
UPDATE wp7j_term_taxonomy SET parent=5378 WHERE term_id=5387; -- #262 parent → WEY
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5387, '_serie_full_title', 'WEY 03 Latte PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #262
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5387, '_serie_api_value', 'Latte PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #262
UPDATE wp7j_terms SET name='05 PHEV', slug='05-phev' WHERE term_id=5382; -- #263 WEY/Mocha PHEV → WEY/05 PHEV
UPDATE wp7j_term_taxonomy SET parent=5378 WHERE term_id=5382; -- #263 parent → WEY
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5382, '_serie_full_title', 'WEY 05 Mocha PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #263
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5382, '_serie_api_value', 'Mocha PHEV') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #263
UPDATE wp7j_terms SET name='SU7', slug='su7' WHERE term_id=5149; -- #2 Xiaomi/Xiaomi SU7 → Xiaomi/SU7
UPDATE wp7j_term_taxonomy SET parent=5148 WHERE term_id=5149; -- #2 parent → Xiaomi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5149, '_serie_full_title', 'Xiaomi SU7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #2
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5149, '_serie_api_value', 'Xiaomi SU7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #2
UPDATE wp7j_terms SET name='YU7', slug='yu7' WHERE term_id=5150; -- #12 Xiaomi/Xiaomi YU7 → Xiaomi/YU7
UPDATE wp7j_term_taxonomy SET parent=5148 WHERE term_id=5150; -- #12 parent → Xiaomi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5150, '_serie_full_title', 'Xiaomi YU7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #12
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5150, '_serie_api_value', 'Xiaomi YU7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #12
UPDATE wp7j_terms SET name='SU7 Ultra', slug='su7-ultra' WHERE term_id=5151; -- #23 Xiaomi/Xiaomi SU7 Ultra → Xiaomi/SU7 Ultra
UPDATE wp7j_term_taxonomy SET parent=5148 WHERE term_id=5151; -- #23 parent → Xiaomi
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5151, '_serie_full_title', 'Xiaomi SU7 Ultra') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #23
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5151, '_serie_api_value', 'Xiaomi SU7 Ultra') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #23
UPDATE wp7j_terms SET name='P7+', slug='p7-plus' WHERE term_id=6052; -- #9 XPeng/XPeng P7+ → XPENG/P7+
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=6052; -- #9 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6052, '_serie_full_title', 'XPENG P7+') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #9
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (6052, '_serie_api_value', 'XPeng P7+') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #9
UPDATE wp7j_terms SET name='Mona M03', slug='mona-m03' WHERE term_id=4768; -- #20 XPeng/XPeng MONA M03 → XPENG/Mona M03
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4768; -- #20 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4768, '_serie_full_title', 'XPENG Mona M03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #20
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4768, '_serie_api_value', 'XPeng MONA M03') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #20
UPDATE wp7j_terms SET name='X9', slug='x9' WHERE term_id=4767; -- #21 XPeng/XPeng X9 → XPENG/X9
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4767; -- #21 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4767, '_serie_full_title', 'XPENG X9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #21
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4767, '_serie_api_value', 'XPeng X9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #21
UPDATE wp7j_terms SET name='G9', slug='g9' WHERE term_id=4763; -- #25 XPeng/XPeng G9 → XPENG/G9
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4763; -- #25 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4763, '_serie_full_title', 'XPENG G9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #25
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4763, '_serie_api_value', 'XPeng G9') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #25
UPDATE wp7j_terms SET name='G6', slug='g6' WHERE term_id=4761; -- #42 XPeng/XPeng G6 → XPENG/G6
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4761; -- #42 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4761, '_serie_full_title', 'XPENG G6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #42
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4761, '_serie_api_value', 'XPeng G6') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #42
UPDATE wp7j_terms SET name='G7', slug='g7' WHERE term_id=4762; -- #73 XPeng/XPeng G7 → XPENG/G7
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4762; -- #73 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4762, '_serie_full_title', 'XPENG G7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #73
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4762, '_serie_api_value', 'XPeng G7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #73
UPDATE wp7j_terms SET name='P7', slug='p7' WHERE term_id=4765; -- #86 XPeng/XPeng P7 → XPENG/P7
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4765; -- #86 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4765, '_serie_full_title', 'XPENG P7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #86
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4765, '_serie_api_value', 'XPeng P7') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #86
UPDATE wp7j_terms SET name='P5', slug='p5' WHERE term_id=4766; -- #264 XPeng/XPeng P5 → XPENG/P5
UPDATE wp7j_term_taxonomy SET parent=4760 WHERE term_id=4766; -- #264 parent → XPENG
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4766, '_serie_full_title', 'XPENG P5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #264
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4766, '_serie_api_value', 'XPeng P5') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #264
UPDATE wp7j_terms SET name='7X', slug='7x' WHERE term_id=4830; -- #24 Zeekr/ZEEKR 7X → Zeekr/7X
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4830; -- #24 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4830, '_serie_full_title', 'Zeekr 7X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #24
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4830, '_serie_api_value', 'ZEEKR 7X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #24
UPDATE wp7j_terms SET name='009', slug='009' WHERE term_id=4827; -- #43 Zeekr/ZEEKR 009 → Zeekr/009
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4827; -- #43 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4827, '_serie_full_title', 'Zeekr 009') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #43
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4827, '_serie_api_value', 'ZEEKR 009') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #43
UPDATE wp7j_terms SET name='001', slug='001' WHERE term_id=4823; -- #49 Zeekr/ZEEKR 001 → Zeekr/001
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4823; -- #49 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4823, '_serie_full_title', 'Zeekr 001') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #49
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4823, '_serie_api_value', 'ZEEKR 001') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #49
UPDATE wp7j_terms SET name='007', slug='007' WHERE term_id=4829; -- #69 Zeekr/ZEEKR 007 → Zeekr/007
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4829; -- #69 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4829, '_serie_full_title', 'Zeekr 007') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #69
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4829, '_serie_api_value', 'ZEEKR 007') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #69
UPDATE wp7j_terms SET name='007 GT', slug='007-gt' WHERE term_id=4826; -- #99 Zeekr/ZEEKR 007 GT → Zeekr/007 GT
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4826; -- #99 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4826, '_serie_full_title', 'Zeekr 007 GT') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #99
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4826, '_serie_api_value', 'ZEEKR 007 GT') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #99
UPDATE wp7j_terms SET name='9X', slug='9x' WHERE term_id=4824; -- #100 Zeekr/ZEEKR 9X → Zeekr/9X
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4824; -- #100 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4824, '_serie_full_title', 'Zeekr 9X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #100
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4824, '_serie_api_value', 'ZEEKR 9X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #100
UPDATE wp7j_terms SET name='X', slug='x' WHERE term_id=4828; -- #101 Zeekr/ZEEKR X → Zeekr/X
UPDATE wp7j_term_taxonomy SET parent=4822 WHERE term_id=4828; -- #101 parent → Zeekr
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4828, '_serie_full_title', 'Zeekr X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #101
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (4828, '_serie_api_value', 'ZEEKR X') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #101
UPDATE wp7j_terms SET name='VX (Lanyue)', slug='vx' WHERE term_id=5200; -- #267 Exeed/Exeed Lanyue C-DM → Exeed/VX (Lanyue)
UPDATE wp7j_term_taxonomy SET parent=5192 WHERE term_id=5200; -- #267 parent → Exeed
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5200, '_serie_full_title', 'Exeed VX') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #267
INSERT INTO wp7j_termmeta (term_id, meta_key, meta_value) VALUES (5200, '_serie_api_value', 'Exeed Lanyue C-DM') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #267