# T-265 — końcówka „Twoje auto czeka” (klip AI)

Scena po „70% dopłacasz później — bez odsetek”, przed CTA i outro. Ruslan (15.10): auto przyjechało, zadowolony klient,
podchodzi do auta z kokardą, obejmuje maskę. Generacja: `koncowka.py kadr` (Gemini `gemini-2.5-flash-image`, z referencją
zdjęcia auta z ogłoszenia) → `koncowka.py wideo` (Veo 3.1 Fast, image-to-video, 9:16, 6 s).
Budżet (Janek 15.10): do 10 prób wideo, ~30 zł. Osoba fikcyjna. Przy publikacji oznaczenie „treść wygenerowana przez AI” (T-267).

**Przy podmianie auta:** zmień tylko zdjęcie referencyjne (`img/auto-1.jpg` z nowego ogłoszenia) i w razie potrzeby kolor
lakieru w promptach poniżej; resztę zostaw.

```kadr
Photorealistic vertical 9:16 photo, shot on a full-frame camera, 35mm lens, natural daylight, late afternoon warm sun.
Use the car from the reference image exactly: same model, same body shape, same front design, same paint colour, same wheels.
Do not add or change any logos or badges. No text, no watermarks, no licence plate characters (plain blank plate).
Scene: the car stands at a clean, modern car delivery yard in Poland, a few other cars blurred far in the background.
A huge glossy red satin gift bow (colour #C92A2B) with long ribbons sits on the bonnet / hood, ribbon running across the hood.
A happy man in his late 30s, casual smart clothes (navy jacket, white shirt), stands at the front corner of the car,
leaning over and hugging the bonnet with both arms, cheek close to the hood, eyes closed, big genuine smile.
He is a fictional person, not resembling any celebrity. Hands natural with five fingers, relaxed.
Composition: car front three-quarter view filling the lower two thirds of the frame, the man and the bow clearly visible,
empty calm sky and soft background in the upper third (space for headline text). Shallow depth of field, crisp detail on car and man.
```

```wideo
Static locked-off camera on a tripod: no zoom, no push-in, no pan — the whole car stays fully in frame the entire time.
The happy man stays leaning over the bonnet next to the big red bow: he lifts his head, laughs with joy, hugs the bonnet
again and gently pats it twice with his palm. He does NOT stand up straight — his head stays below the top of the car roof
line. The red ribbons flutter slightly in a light breeze. The car stays completely still and keeps its exact shape, grille,
design and colour; no new logos or text appear. Realistic natural motion, warm afternoon light, cinematic, no cuts.
Audio: quiet outdoor ambience, no music, no speech.
```

## Wybrane ujęcie

**v4, 15.10.2026 — Zeekr 8X (oferta 540080, przysłana przez Ruslana):**
- Referencja: zdjęcie 1 z ogłoszenia (`zeekr-8x-2026-shenzhen-60140145-1.webp`, przód 3/4).
- Kadr: wariant 3 z 3 (cała postać, cały przód auta z grillem, niebo na nagłówek).
- Wideo: próba 2 dla Zeekra (prompt ze statyczną kamerą). Próba 1 z najazdem kamery odrzucona: pod koniec głowa wchodziła
  pod nagłówek, a auto było ucięte. Kopie: Dysk / auratest `primaauto-T265-koncowka-ai-*-2026-10-15.*`.
- Klip 720×1280 z czarnymi pasami 10 px → `crop=720:1260:0:10`, skalowanie do 1080 szer. i przycięcie do 1920; stopklatka do
  karuzeli z 1,9 s (`img/koniec-stopklatka.jpg`).
- Koszt prób łącznie (z testem na Denzie): kadry 6 × ~$0.04, wideo 3 × $0.60 ≈ **$2.04 (≈8 zł)** z budżetu 30 zł.
- Zanim padła Zeekr, test na Denzie Z9 GT (kadr 2 + wideo 720p) był udany — technika sprawdzona na dwóch autach.
