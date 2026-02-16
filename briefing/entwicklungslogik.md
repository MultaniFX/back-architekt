# Entwicklungslogik: Brot-Konfigurator

Dieses Dokument enthaelt alle Regeln, bedingte Logik und Berechnungsgrundlagen die fuer die technische Umsetzung relevant sind.

---

## 1. Erfahrungslevel-System (1-5)

Das Level steuert die gesamte Komplexitaet des Wizards:

| Level | Label | Hauptmehle | Weitere Mehle | Urkorn | TA-Basis | Max-TA (mit Kochst.) | Formgebung |
|:-----:|-------|:----------:|:------------:|:------:|:--------:|:--------------------:|------------|
| 1 | Einsteiger | 1 | 0 | Nein | 168 | 173 | Topf empfohlen |
| 2 | Grundkenntnisse | 1 | 1 | Nein | 170 | 175 | Topf |
| 3 | Fortgeschritten | 1 | 2 | Nur als weiteres Mehl | 173 | 180 | Frei waehlbar |
| 4 | Erfahren | 2 | 3 | Ja (Haupt + Weitere) | 176 | 185 | Freigeschoben moeglich |
| 5 | Profi | 3 | 3 | Ja (Haupt + Weitere) | 180 | 190 | Freigeschoben empfohlen |

**Weitere Level-Unterschiede:**
- Level 1: Kein Nebenmehl, 1 Extra, Topf empfohlen
- Level 2: 1 Nebenmehl, 2 Extras, TA leicht hoeher, Topf
- Level 3: 2 Nebenmehle, reiner Sauerteig erlaubt (5% ST nur ab hier), Urkorn als Neben
- Level 4-5: Kochstueck + Bruehstueck gleichzeitig moeglich, max 30% Extras

**Vollkorn-Zuschlag:** Bei Vollkornanteil > 70%: +2 TA-Punkte auf Basis und Max.

---

## 2. Mehl-Datenbank

### Verfuegbare Mehlsorten

| Getreide | Typen | Kategorie | Min. Level (Hauptmehl) | Min. Level (Weiteres) |
|----------|-------|-----------|:----------------------:|:---------------------:|
| Weizen | 550, 812, 1050, 1600, Vollkorn | Standard | 1 | 1 |
| Roggen | 997, 1150, 1370, 1740, Vollkorn | Standard | 1 | 1 |
| Dinkel | 630, 812, 1050, Vollkorn | Standard | 1 | 1 |
| Semola | (Hartweizen, eine Type) | Standard | 1 | 1 |
| Emmer | 812, Vollkorn | Urkorn | 4 | 3 |
| Einkorn | 812, Vollkorn | Urkorn | 4 | 3 |
| Kamut/Khorasan | Vollkorn | Urkorn | 4 | 3 |

### Mehlverteilung

| Konstellation | Verteilung |
|--------------|-----------|
| 1 Hauptmehl, 0 Nebenmehle | 100% Hauptmehl |
| 2 Hauptmehle, 0 Nebenmehle | 50% / 50% |
| 3 Hauptmehle, 0 Nebenmehle | 33% / 33% / 33% |
| Mit Nebenmehle | Hauptmehle = 80% (gleichverteilt), Nebenmehle = 20% (gleichverteilt) |

**Vorteig-Mehl:** Wird vom entsprechenden Mehltyp abgezogen, nicht zusaetzlich.

### Mehl-spezifische Regeln

- **Dinkel/Urkorn:** Automatisches Kochstueck (Tangzhong, 4% Mehl, 1:5 Wasser). Fermentolyse (15 min). Stretch & Fold Pflicht. Knetzeit max 8-9 min.
- **Semola:** Eigene Kategorie. TA fix 170-175. Kein Kochstueck. Kein Fermentolyse. Knetzeit 10-12 min. 100% moeglich.
- **Roggen > 50%:** Sauerteig ist Pflicht. Hefe darf nur als Beigabe.
- **Roggen >= 75%:** Nur mischen (3-5 min), kein Kneten. Keine Stretch & Folds. Kein Kuehlschrank. 4-8h Teigzeit (plus ggf. Sauerteig-Zeit).
- **Roggen 50-74%:** Normal kneten, +2 min laenger als Weizen.
- **Vollkorn >= 60%:** Fermentolyse (15 min). TA +2 bei > 70%.

---

## 3. Triebmittel-Logik

### Optionen

- **Hefe:** Schnell, zuverlaessig, weniger Aroma
- **Sauerteig:** Mehr Geschmack, laengere Fuehrung, notwendig bei hohem Roggenanteil
- **Beides (Hybrid):** Sauerteig fuer Geschmack + Hefe fuer Triebsicherheit. Beide Werte halbieren.

### Sauerteig-Typ (neue UI-Frage, Schritt 2)

Wird immer abgefragt wenn Sauerteig/Hybrid gewaehlt:
- Roggensauer (TA 200)
- Weizensauer (TA 200)
- Dinkelsauer (TA 200)
- Lievito Madre (TA 150)

**Mehl-Zurechnung:** ST-Mehl zaehlt zum entsprechenden Mehltyp.

### Sauerteig-Status (neue UI-Frage, immer bei ST-Auswahl)

"Ist dein Sauerteig einsatzbereit?"
- Ja → kein Auffrischungsschritt
- Nein → Auffrischungsschritt wird eingeplant (braucht mind. 4h)

### Zeitabhaengige Regeln

| Zeitfenster | Regel |
|-------------|-------|
| < 8h | Sauerteig muss einsatzbereit sein (keine Auffrischung moeglich) |
| 8-12h | Nur schnelle Auffrischung (1:3:3 oder 1:2:1 bei LM, ~4h) |
| > 12h | Normale Auffrischung (1:5:5 bei 6-8h, 1:10:10 bei 9-12h) |

### Triebmittel-Mengen (bezogen auf Gesamtmehl)

| Zeitbucket | Hefe frisch (%) | Sauerteig (%) |
|:----------:|:---------------:|:-------------:|
| 4-6h | 1,0 - 1,5 | 10 - 25 |
| 6-8h | 0,7 - 1,0 | 15 - 25 |
| 8-12h | 0,3 - 0,7 | 10 - 15 |
| 12-24h | 0,1 - 0,3 | 8 - 10 |
| 24-36h | 0,05 - 0,1 | 5 - 10 |
| 36-48h | 0,03 - 0,05 | 5 - 8 |

**Kombi:** Beide Werte halbieren.
**Anfaenger (Level 1-2) + reiner ST:** Automatisch 0,1% Hefe + Hinweis.

### Roggen-Sonderregeln Triebmittel

- **Roggen > 50%:** Hefe nie pur, immer nur als Beigabe zum Sauerteig.
- **Roggen > 75% + nur Hefe:** Wird ausgegraut bei > 8h Gesamtzeit.
- **Roggen > 75%:** Teigzeit immer 4h, Sauerteig-Zeit kommt dazu:
  - 8h gesamt = 4h ST + 4h Teig
  - 12h gesamt = 8h ST + 4h Teig
  - 16h gesamt = 12h ST + 4h Teig
  - 24h gesamt = 20h zweistufiger ST + 4h Teig

---

## 4. Teigausbeute (TA) Berechnung

**Formel:** `TA = (Mehlmenge + Wassermenge) / Mehlmenge * 100`

### Basis-TA (ohne Koch-/Bruehstueck)

| Level | 1 | 2 | 3 | 4 | 5 |
|-------|:-:|:-:|:-:|:-:|:-:|
| **Basis-TA** | 168 | 170 | 173 | 176 | 180 |

Gilt fuer alle Mehltypen gleich. **Ausnahme:** Semola fix 170-175.

### TA-Erhoehung durch Koch-/Bruehstueck

Jedes Kochstueck oder TA-erhoehende Bruehstueck (Hafer, Leinsamen, Altbrot, Schrot) addiert **+5 TA**, gedeckelt auf:

| Level | 1 | 2 | 3 | 4 | 5 |
|-------|:-:|:-:|:-:|:-:|:-:|
| **Max-TA** | 173 | 175 | 180 | 185 | 190 |

Koerner-Bruehstuecke (Sonnenblumenkerne, Kuerbiskerne, Sesam) erhoehen die TA **nicht**.

---

## 5. Extras & Bruehstueck-Berechnung

### Verfuegbare Extras

**Koerner (Bruehstueck 1:0,75, erhoehen TA nicht):**
1. Sonnenblumenkerne
2. Kuerbiskerne
3. Sesam

**TA-erhoehende Extras (Bruehstueck 1:4, erhoehen TA um +5):**
4. Leinsamen
5. Haferflocken
6. Altbrot
7. Schrot (Weizen/Roggen)

### Mengenberechnung

**Koerner:**
- Erstes Korn: 15% der Mehlmenge
- Ab dem zweiten: gleichmaessig aufteilen
- Max gesamt: 20% (Level 1-3), 30% (Level 4-5)

**TA-erhoehende Extras:**
- Allein: 10% der Mehlmenge
- In Kombination: 5% der Mehlmenge
- Max gesamt: 20% (Level 1-3), 30% (Level 4-5)

### Bruehstueck-Verfuegbarkeit

| Zeitfenster | Bruehstueck? |
|-------------|:------------:|
| 4h | Nein |
| 6-8h + Sauerteig | Nein (ST braucht die Zeit) |
| 6-8h + Hefe | Ja |
| 8h+ | Ja |

### Schnelle Brote (4h, kein Bruehstueck)

- Koerner: Trocken am Ende des Knetens, kein extra Wasser
- Altbrot/Hafer/Leinsamen: Mit dem Mehl + gleiche Menge Wasser extra
- Schrot: Nicht verfuegbar bei 4h

### Kochstueck (automatisch bei Dinkel/Urkorn)

- 4% Gesamtmehl als Tangzhong (1:5 Mehl:Wasser)
- Bei Level 1-3: Entfaellt wenn TA-erhoehender Bruehstueck vorhanden
- Bei Level 4-5: Immer vorhanden (auch mit Bruehstueck zusammen)
- Nicht bei Weizen, Roggen, Semola

---

## 6. Zeitplan-Logik

### Zeitfenster-Buckets

| Bucket | Min | Max |
|--------|:---:|:---:|
| 4-6h | 4h | 6h |
| 6-8h | 6h | 8h |
| 8-12h | 8h | 12h |
| 12-24h | 12h | 24h |
| 24-36h | 24h | 36h |
| 36-48h | 36h | 48h |

**Mindestzeit:** 4h. Unter 4h wird nichts angeboten.

### Kuehlschrank-Logik

| Bedingung | Kuehlschrank |
|-----------|:------------:|
| < 12h | Nein |
| 12h+ (ST fertig/nur Hefe) | Immer |
| 16h+ | Immer |
| Roggen > 75% | Nie |

**Zwei Modi (Nutzer-Frage ab 12h, Schritt 1):**
- Normal: Kalte Stockgare → formen → warme Stueckgare → backen
- Direkt aus Kuehlschrank: Formen → kalte Stueckgare (mind. 8h) → direkt backen

**Anspringzeiten (warm vor Kuehlschrank):**
- 8h kalt → 120 min anspringen
- 12h kalt → 90 min anspringen
- 16h kalt → 60 min anspringen

### Prozess-Module (Timeline-Bausteine)

1. **Sauerteig-Auffrischung** (wenn ST nicht bereit, min. 4h)
2. **Sauerteig ansetzen** (wenn ST gewaehlt)
3. **Bruehstueck ansetzen** (parallel zu ST, wenn Extras)
4. **Kochstueck kochen** (parallel zu ST, wenn Dinkel/Urkorn)
5. **Fermentolyse** (15 min, bei Dinkel/Urkorn/VK >= 60%)
6. **Kneten** (abhaengig von Mehltyp)
7. **Stretch & Fold** (3x alle 15 min)
8. **Stockgare** (warm oder kalt)
9. **Formen**
10. **Stueckgare** (warm 90 min, oder kalt mind. 8h)
11. **Backen** (Profil + Gewichts-Skalierung)

### Zeitplan-Berechnung

- Startpunkt: "Jetzt" (aktuelle Uhrzeit)
- Vorwaerts-Berechnung: Jeder Schritt addiert seine Dauer
- **Sauerteig-Zeit wird von der Gesamtzeit abgezogen** fuer die effektive Teig-Zeit
- System muss pruefen ob die Gesamtzeit ins Zeitbudget passt
- Ziel warme Stueckgare: 90 min (Weizen/Dinkel/Urkorn/Semola), 2-2,5h (Roggen)

---

## 7. Backmethoden-Details

### Backmethode (Nutzer-Auswahl, Schritt 4)

**Empfehlungs-Logik:**

| Level | Empfehlung |
|:-----:|-----------|
| 1-2 | Topf (empfohlen) |
| 3 | Alle gleichwertig |
| 4-5 | Pizzastein / Backstahl (empfohlen) |

### Backprofile (nach Mehlmenge skaliert)

**Weizen/Dinkel/Urkorn/Semola - Offen:**
10 min Schwaden 250°C → 230°C: 25 min (500-600g), 35 min (600-800g), 45 min (800-1000g)

**Weizen/Dinkel/Urkorn/Semola - Topf:**
25 min Deckel 250°C → 230°C ohne Deckel: 15 min (500-600g), 25 min (600-800g), 35 min (800-1000g)

**Roggen - Offen:**
5 min Schwaden 230°C → 215°C: 40 min (500-600g), 50 min (600-800g), 60 min (800-1000g)

**Roggen - Topf:**
25 min Deckel 230°C → 215°C ohne Deckel: 20 min (500-600g), 30 min (600-800g), 40 min (800-1000g)

**> 1000g Mehl = mehrere Brote backen.**

### Nach dem Backen

- Weizen/Dinkel/Urkorn/Semola: 30-60 min auskuehlen auf Gitter
- **Roggen: Mindestens 24 Stunden liegen lassen vor dem Anschneiden!**

---

## 8. Dynamischer Rezeptname

**Schema:** `[Geschwindigkeit] [Hauptmehl] [-Mischbrot/brot] [mit Extras]`

| Komponente | Bedingung | Wert |
|-----------|-----------|------|
| Geschwindigkeit | < 8h | "Schnelles" |
| Geschwindigkeit | 8-16h | (leer) |
| Geschwindigkeit | > 16h | "Langsam gefuehrtes" |
| Mehlsorte | 1 Mehl | "Weizen-" / "Roggen-" / "Dinkel-" etc. |
| Mehlsorte | 2+ Mehle | "[Hauptmehl]-Misch" |
| Brot-Typ | Standard | "-brot" |
| Extras | Wenn vorhanden | "mit [Extra1] und [Extra2]" |

---

## 9. Basisberechnung (Baeckerprozent)

Alle Zutaten werden relativ zur Gesamtmehlmenge berechnet:

**Mehlmenge = 100% (Basis, default 500g)**

| Zutat | Formel |
|-------|--------|
| Wasser | (TA - 100) / 100 x Mehlmenge |
| Salz | 2% der Mehlmenge |
| Hefe (frisch) | Je nach Zeitbucket (siehe Abschnitt 3) |
| Sauerteig | Je nach Zeitbucket + ASG-Verhaeltnis |
| Koerner-Extras | 15% erstes, gleichverteilt danach |
| TA-erhoehende Extras | 10% allein, 5% in Kombi |
| Bruehstueck-Wasser | Zutat x Faktor (0,75 oder 4) |

**Wichtig:** Bruehstueck-Wasser + Kochstueck-Wasser + Sauerteig-Wasser werden von der Gesamt-Wassermenge abgezogen.

---

## 10. Rule Engine - Prioritaeten

Eine robuste Rule Engine braucht klare Priorisierung:

1. **Harte Ausschluesse:** Mindestzeit 4h, Roggen > 50% ohne ST
2. **Zeitfenster-Constraints:** Kuehlschrank ab 12h, Roggen > 75% max 8h Teigzeit
3. **Mehl-/Teiglogik:** Roggen-Schwelle (75% mix only), Dinkel/Urkorn → Kochstueck, Fermentolyse
4. **Skill-Level-Limits:** Max TA pro Level, Anzahl Mehle, 5% ST nur ab Level 3
5. **User Preferences:** Backmethode, Extras, direkt aus Kuehlschrank
