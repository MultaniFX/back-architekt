# Rezept-Regelwerk: Brot-Konfigurator

Dieses Dokument ist die **vollstaendige Referenz** fuer die Rezept-Engine des Brot-Konfigurators. Alle konkreten, grammgenauen Regeln fuer die Rezeptgenerierung sind hier dokumentiert.

---

## A. Teigausbeute (TA)

**Formel:** `TA = (Mehlmenge + Wassermenge) / Mehlmenge x 100`

### A.1 Basis-TA nach Level (ohne Koch-/Bruehstueck)

Gilt fuer **alle Mehltypen gleich** (Ausnahme: Semola, siehe A.4).

| Level | Basis-TA |
|:-----:|:--------:|
| 1 | 168 |
| 2 | 170 |
| 3 | 173 |
| 4 | 176 |
| 5 | 180 |

### A.2 Maximale TA mit Koch-/Bruehstueck

Wenn ein Kochstueck und/oder ein TA-erhoehender Bruehstueck vorhanden ist, wird die TA angehoben:

| Level | Max-TA mit Koch-/Bruehstueck |
|:-----:|:----------------------------:|
| 1 | 173 |
| 2 | 175 |
| 3 | 180 |
| 4 | 185 |
| 5 | 190 |

**Erhoehungsregel:**
- Jedes Kochstueck oder TA-erhoehende Bruehstueck addiert +5 TA-Punkte auf die Basis-TA.
- Bei 2 Stuecken: jeweils +5, also bis zu +10 TA.
- Die Gesamt-TA ist **gedeckelt** auf den Max-Wert des jeweiligen Levels.
- **Nur diese 4 Bruehstueck-Typen erhoehen die TA:** Hafer, Leinsamen, Altbrot, Schrot.
- Koerner-Bruehstuecke (Sonnenblumenkerne, Kuerbiskerne, Sesam) erhoehen die TA **nicht**.

**Beispiel:** Level 3, Dinkel (auto-Kochstueck) + Hafer-Bruehstueck = Basis 173 + 5 (Kochstueck) + 5 (Hafer) = 183, gedeckelt auf Max 180.

### A.3 Vollkorn-Zuschlag

Bei Vollkornanteil > 70% der Gesamtmehlmenge: **+2 TA-Punkte** auf den jeweiligen Wert (Basis und Max).

### A.4 Semola-Sonderregel

Semola (Hartweizen) hat eine fixe TA-Range: **170-175**, unabhaengig vom Level. Kein Kochstueck, keine TA-Erhoehung.

### A.5 Rechenregeln bei Mehlmischungen

- **Gewichteter Durchschnitt** der TA-Werte, wenn verschiedene Mehltypen verwendet werden.
- Da alle Standard-Mehle denselben Basis-TA haben, ergibt sich bei Mischungen ohne Semola immer der Level-Wert.
- Bei Mischung mit Semola: Gewichteter Durchschnitt aus Level-TA und Semola-TA (170-175).

---

## B. Mehlverteilung

### B.1 Haupt- und Nebenmehle

| Konstellation | Verteilung |
|--------------|-----------|
| 1 Hauptmehl, 0 Nebenmehle | 100% Hauptmehl |
| 2 Hauptmehle, 0 Nebenmehle | 50% / 50% |
| 3 Hauptmehle, 0 Nebenmehle | 33% / 33% / 33% |
| 1 Hauptmehl + Nebenmehle | 80% Hauptmehl, 20% gleichverteilt auf Nebenmehle |
| 2 Hauptmehle + Nebenmehle | 40% / 40% Hauptmehle, 20% gleichverteilt auf Nebenmehle |
| 3 Hauptmehle + Nebenmehle | ~27% / ~27% / ~27% Hauptmehle, 20% gleichverteilt auf Nebenmehle |

**Wichtig:** Mehle im Vorteig/Sauerteig sind Teil der Gesamtberechnung, nicht zusaetzlich. Das Mehl im Sauerteig wird vom entsprechenden Mehltyp abgezogen.

### B.2 Mischbrot-Benennung (deutsche Leitsaetze)

- **Weizenbrot:** >90% Weizen
- **Weizen-Mischbrot:** 51-90% Weizen
- **Roggen-Mischbrot:** 51-90% Roggen
- **Roggenbrot:** >90% Roggen
- **Dinkel zaehlt als Weizen** (fuer die Benennung)

### B.3 Urkorn-Grenzen (Empfehlungen)

- **Emmer:** max. 30-50% empfohlen (Kastenform bei >50%)
- **Einkorn:** max. 20-40% (extrem schwacher Kleber!)
- **Kamut:** max. 50-70% (robuster als Emmer/Einkorn)
- **Semola:** 100% moeglich

---

## C. Sauerteig-Berechnung

### C.1 Sauerteig-Typen (Nutzer-Auswahl)

| Typ | Mehl | TA | Hinweis |
|-----|------|:--:|---------|
| Roggensauer | Roggen | 200 | Standard fuer Roggenbrote |
| Weizensauer | Weizen | 200 | Fuer Weizen-/Mischbrote |
| Dinkelsauer | Dinkel | 200 | Fuer Dinkelbrote |
| Lievito Madre | Weizen/Dinkel | 150 | Italienischer Festsauer |

**Mehl-Zurechnung:** Das Sauerteig-Mehl zaehlt zum entsprechenden Mehltyp in der Gesamtberechnung. Ein Roggensauer in einem Weizenbrot = das Roggenmehl im ST zaehlt als Nebenmehl.

### C.2 ASG-Verhaeltnisse

| Einsatz | Verhaeltnis (ASG:Mehl:Wasser) | Reifezeit bei 23-24°C |
|---------|:-----------------------------:|:---------------------:|
| Schnelle Auffrischung | 1:3:3 (oder 1:2:1 bei LM) | ~4h |
| Normal (6-8h) | 1:5:5 | 6-8h |
| Lang (9-12h) | 1:10:10 | 9-12h |

**Roggen-Besonderheit bei Weizensauer:** Bei Weizen ST im Peak verwenden (nicht stur auf Zeit). Bei Roggen die Zeiten durchziehen (Aroma-Entwicklung).

### C.3 Zweistufiger Roggensauer (nur bei 24h Roggenbroten)

| Stufe | Verhaeltnis | Dauer | Temperatur |
|:-----:|:-----------:|:-----:|:----------:|
| 1 | 1:10:10 (z.B. 10g ASG + 100g Mehl + 100g Wasser) | 16h | 23-24°C |
| 2 | 1:1:1,2 (z.B. 200g ST1 + 200g Mehl + 240g Wasser) | 4h | 20-21°C |

**Hinweis:** Stufe 2 hat TA 220 (mehr Wasser = anderes Aroma). Niedrigere Temperatur in Stufe 2.

### C.4 Roggen-Sauerteig nach Zeitfenster

| Gesamt-Zeit | ST-Zeit | Teig-Zeit | ASG-Verhaeltnis |
|:-----------:|:-------:|:---------:|:---------------:|
| 8h | 4h | 4h | 1:3:3 |
| 12h | 8h | 4h | 1:5:5 |
| 16h | 12h | 4h | 1:10:10 |
| 24h | 20h (zweistufig) | 4h | Siehe C.3 |

### C.5 Versaeuerungsgrad (Roggen >75%)

| ST-Anteil | Stockgare | Stueckgare |
|:---------:|:---------:|:----------:|
| 20% | 2h | 2,5-3h |
| 33% (Standard) | 1h | 2,5h |
| 50% | 30 min | 1,5-2h |

### C.6 Sauerteig-Status (Nutzer-Abfrage)

| Zeitfenster | Regel |
|-------------|-------|
| < 8h | Sauerteig muss **einsatzbereit** sein (keine Auffrischung moeglich) |
| 8-12h | Nur **schnelle Auffrischung** (1:3:3 oder 1:2:1 bei LM, ~4h) |
| > 12h | Normale Auffrischung moeglich |

### C.7 Roggen-Pflichtversaeuerung

- **Roggen > 50%:** Sauerteig ist Pflicht. Hefe darf zusaetzlich gegeben werden.
- **Roggen <= 50%:** Sauerteig optional (fuer Geschmack).

---

## D. Triebmittel-Mengen

### D.1 Hefe und Sauerteig nach Zeitbucket (bezogen auf Gesamtmehl)

| Zeitbucket | Hefe frisch (%) | Sauerteig (%) |
|:----------:|:---------------:|:-------------:|
| 4-6h | 1,0-1,5 | 10-25 |
| 6-8h | 0,7-1,0 | 15-25 |
| 8-12h | 0,3-0,7 | 10-15 |
| 12-24h | 0,1-0,3 | 8-10 |
| 24-36h | 0,05-0,1 | 5-10 |
| 36-48h | 0,03-0,05 | 5-8 |

### D.2 Kombi-Regel (Hefe + Sauerteig)

Bei Kombination Hefe + Sauerteig: **beide Werte halbieren.**

### D.3 Anfaenger-Unterstuetzung

Level 1-2 mit reinem Sauerteig erhalten automatisch **0,1% Hefe** zur Gelingsicherheit. Dazu ein Hinweis: _"Wir empfehlen Back-Anfaengern ein klein wenig Hefe zur Gelingsicherheit."_

### D.4 Roggen-Sonderregeln

- **Roggen > 50%:** Hefe nie pur, immer nur als Beigabe zum Sauerteig.
- **Roggen > 75% + nur Hefe:** Kombination wird **ausgegraut** (nicht angeboten) bei > 8h.
- **Roggen > 75%:** Grundsaetzlich 4-8h Teigzeit (Sauerteig-Zeit kommt dazu, siehe C.4).

### D.5 Salz

Immer **2% der Gesamtmehlmenge.** Salz wird immer erst **nach der ersten Knet-Phase** zugegeben (nicht waehrend Fermentolyse).

---

## E. Bruehstueck und Kochstueck

### E.1 Automatisches Kochstueck (Dinkel und Urkorn)

| Parameter | Wert |
|-----------|------|
| Aktivierung | Automatisch bei Dinkel oder Urkorn als Hauptmehl |
| Methode | Tangzhong |
| Mehlanteil | 4% der Gesamtmehlmenge |
| Wasser | 1:5 (Mehl:Wasser) |
| Vom Gesamtmehl abziehen | JA |
| Vom Gesamtwasser abziehen | JA |

**Beispiel:** 500g Dinkelmehl → 20g Mehl + 100g Wasser als Kochstueck. Hauptteig: 480g Mehl, Wasser entsprechend reduziert.

**Entfaellt bei Level 1-3** wenn ein TA-erhoehender Bruehstueck vorhanden ist (Hafer, Leinsamen, Altbrot, Schrot). **Level 4-5 haben beides** (Kochstueck + Bruehstueck).

**Kein Kochstueck fuer:** Weizen, Roggen, Semola.

### E.2 Bruehstueck (alle Add-ons)

Alle Extras werden als **Bruehstueck** (heisses Wasser) zubereitet. Keine Quellstuecke mehr.

| Zutat | Zutat:Wasser | Erhoehung TA? |
|-------|:------------:|:------------:|
| Sonnenblumenkerne | 1:0,75 | Nein |
| Kuerbiskerne | 1:0,75 | Nein |
| Sesam | 1:0,75 | Nein |
| Leinsamen | 1:4 | **Ja (+5 TA)** |
| Haferflocken | 1:4 | **Ja (+5 TA)** |
| Altbrot | 1:4 | **Ja (+5 TA)** |
| Schrot (Weizen/Roggen) | 1:4 | **Ja (+5 TA)** |

**Timing:** 1 Stunde quellen lassen, Rest abkuehlen. Wird zeitlich mit Vorteigen/Sauerteig gekoppelt.

**Wasser abziehen:** Das Bruehstueck-Wasser wird von der Gesamt-Wassermenge abgezogen.

### E.3 Verfuegbarkeit von Bruehstuecken

| Zeitfenster | Bruehstueck moeglich? |
|-------------|:--------------------:|
| 4h | **Nein** (kein Bruehstueck) |
| 6-8h + Sauerteig | **Nein** (keine Zeit, ST braucht die Zeit) |
| 6-8h + Hefe | Ja |
| 8h+ | Ja |

### E.4 Add-on Mengenberechnung

**Koerner (Sonnenblumenkerne, Kuerbiskerne, Sesam):**
- Erstes Korn: 15% der Mehlmenge
- Ab dem zweiten Korn: gleichmaessig aufteilen
- Gesamtmaximum: 20% (Level 1-3), 30% (Level 4-5)

**TA-erhoehende Extras (Leinsamen, Haferflocken, Altbrot, Schrot):**
- Allein: 10% der Mehlmenge
- In Kombination mit anderen Extras: 5% der Mehlmenge
- Gesamtmaximum: 20% (Level 1-3), 30% (Level 4-5)

**Schnelle Brote (4h, kein Bruehstueck):**
- Koerner (wenig Wasseraufnahme): Trocken am Ende des Knetens einarbeiten, kein extra Wasser.
- Altbrot/Hafer/Leinsamen: Mit dem Mehl einarbeiten + gleiche Menge Wasser extra.
- Schrot: **Nicht verfuegbar** bei 4h (nur als Bruehstueck moeglich).

### E.5 Schrot als Extra

- Neuer Extra-Typ: Schrot (Weizen oder Roggen)
- Zaehlt **nicht** als Mehlersatz (wird nicht von Mehlmenge abgezogen)
- Nur als Bruehstueck (auch bei schnellen Broten nicht trocken einarbeiten)
- Menge: 10% allein, 5% in Kombination

---

## F. Zeitplan-Bausteine

### F.1 Fermentolyse

| Mehltyp | Fermentolyse | Dauer |
|---------|:------------:|:-----:|
| Dinkel (>= 60% Anteil) | **Ja, Pflicht** | 15 min |
| Urkorn (>= 60% Anteil) | **Ja, Pflicht** | 15 min |
| Vollkorn (>= 60% Anteil) | **Ja, Pflicht** | 15 min |
| Weizen (Auszugsmehle) | Nein | - |
| Roggen | Nein | - |
| Semola | Nein | - |

**Ablauf:** Mehl + Wasser + Triebmittel (Hefe und/oder Sauerteig) vermischen, 15 min ruhen. Dann Salz zugeben und Knetphase starten.

**Auch bei 4h-Broten:** Fermentolyse wird gemacht, Stockgare faellt dann entsprechend kuerzer aus (Hefen vermehren sich bereits).

### F.2 Knetzeiten (von Hand)

| Mehltyp | Knetstrategie | Dauer | Fenstertest | Besonderheit |
|---------|:------------:|:-----:|:-----------:|-------------|
| Weizen | Normal | 10-14 min | Ja | 2-3 min langsam, 2-3 min mittel (ggf. Schuettwasser), Salz, 4-8 min fertig |
| Dinkel | Moderat | 8-9 min | Vorsichtig | Ueberkneten vermeiden! |
| Urkorn | Kurz | 8-9 min | Eingeschraenkt | Empfindliches Gluten |
| Semola | Normal | 10-12 min | Ja | Wie Weizen, etwas kuerzer |
| Roggen >= 75% | Nur mischen | 3-5 min | Nein | Kein Kneten! Direkt mit Salz. |
| Roggen 50-74% | Normal + 2 min | 12-16 min | Eingeschraenkt | Normal kneten, 2 min laenger als Weizen |

### F.3 Stretch & Fold

| Parameter | Wert |
|-----------|------|
| Anzahl | 3 Runden |
| Abstand | alle 15 Minuten |
| Gesamtdauer | 45 Minuten (innerhalb der Stockgare) |
| Ab Level | **1** (alle Level) |

| Mehltyp | Stretch & Fold |
|---------|:--------------:|
| Dinkel | **Pflicht** |
| Urkorn | **Pflicht** |
| Weizen | Empfohlen |
| Semola | Empfohlen |
| Roggen >= 75% | **Nicht anwenden** |

### F.4 Stockgare

#### Warme Stockgare (23°C)

**Mit Hefe:**

| Hefemenge | Dauer |
|-----------|:-----:|
| Viel (1-1,5%) | 90-120 min |
| Wenig (0,3-1%) | 2-4h |
| Sehr wenig (<0,3%) | 4-6h |

**Mit Sauerteig (pur):**

| ST-Anteil | Dauer |
|:---------:|:-----:|
| 20% | 2,5h |
| 15% | 3,5h |
| 10% | 4,5h |
| 7,5% | 5,5h |

**Mit Sauerteig + minimaler Hefe (Anfaenger):**

| ST-Anteil | Dauer |
|:---------:|:-----:|
| 20% | 2h |
| 15% | 3h |
| 10% | 4h |
| 7,5% | 5h |
| 5% | 6h |

Hinweis: 5% Sauerteig nur ab Level 3. Bei Kombi (Hefe + ST) die Sauerteig-Werte **nicht** halbieren.

**Roggen (>75%, siehe auch C.5):**

| ST-Anteil | Stockgare |
|:---------:|:---------:|
| 20% | 2h |
| 33% | 1h |
| 50% | 30 min |

#### Kalte Stockgare (Kuehlschrank, 4-5°C)

| Kuehlschrank-Dauer | Anspringzeit (warm, vorher) |
|:-------------------:|:---------------------------:|
| 8h | 120 min |
| 12h | 90 min |
| 16h | 60 min |

Nur mit wenig bis sehr wenig Hefe/Sauerteig sinnvoll. Abhaengig von der Triebmittelmenge.

### F.5 Stueckgare

#### Warme Stueckgare

| Mehltyp | Ziel-Dauer |
|---------|:----------:|
| Weizen / Dinkel / Urkorn / Semola | 1,5h |
| Roggen (>75%) | 2-2,5h |

**Roggen-Besonderheit:** Roggenteig laesst sich nach Stockgare kaum formen ohne Gas zu verlieren. Darum: **kurze Stockgare, laengere Stueckgare.** Brot ist fertig, wenn typische Risse im Mehl auf der Oberflaeche erscheinen.

#### Kalte Stueckgare (direkt aus Kuehlschrank backen)

Mindestens **8 Stunden** im Kuehlschrank. Danach direkt in den vorgeheizten Ofen.

### F.6 Kuehlschrank-Logik

| Zeitfenster | Kuehlschrank |
|-------------|:------------:|
| < 12h | Nein (Ausnahme: ST-Auffrischung benoetigt die Zeit) |
| 12h+ (ST fertig oder nur Hefe) | **Immer** |
| 16h+ | **Immer** |
| Roggen > 75% | **Nie** (nur 4-8h Teigzeit) |

**Zwei Modi:**
- **Normal:** Kalte Stockgare → rausnehmen → formen → warme Stueckgare → backen
- **Direkt aus Kuehlschrank:** Formen → kalte Stueckgare (mind. 8h) → direkt in den Ofen

### F.7 Ofen vorheizen

| Methode | Vorheizdauer |
|---------|:-----------:|
| Topf (Dutch Oven) | 30-45 min (Topf mit aufheizen!) |
| Pizzastein | 45-60 min |
| Backstahl | 30-40 min |

### F.8 Backprofile

#### Weizen / Dinkel / Urkorn / Semola

**Offen (Stein/Stahl):**

| Phase | Temperatur | Dauer (500-600g) | Dauer (600-800g) | Dauer (800-1000g) |
|-------|:----------:|:----------------:|:----------------:|:-----------------:|
| 1: Mit Schwaden | 250°C | 10 min | 10 min | 10 min |
| 2: Ohne Schwaden | 230°C | 25 min | 35 min | 45 min |
| **Gesamt** | | **35 min** | **45 min** | **55 min** |

**Im Topf:**

| Phase | Temperatur | Dauer (500-600g) | Dauer (600-800g) | Dauer (800-1000g) |
|-------|:----------:|:----------------:|:----------------:|:-----------------:|
| 1: Mit Deckel | 250°C | 25 min | 25 min | 25 min |
| 2: Ohne Deckel | 230°C | 15 min | 25 min | 35 min |
| **Gesamt** | | **40 min** | **50 min** | **60 min** |

#### Roggen

**Offen (Stein/Stahl):**

| Phase | Temperatur | Dauer (500-600g) | Dauer (600-800g) | Dauer (800-1000g) |
|-------|:----------:|:----------------:|:----------------:|:-----------------:|
| 1: Mit Schwaden | 230°C | 5 min | 5 min | 5 min |
| 2: Ohne Schwaden | 215°C | 40 min | 50 min | 60 min |
| **Gesamt** | | **45 min** | **55 min** | **65 min** |

**Im Topf:**

| Phase | Temperatur | Dauer (500-600g) | Dauer (600-800g) | Dauer (800-1000g) |
|-------|:----------:|:----------------:|:----------------:|:-----------------:|
| 1: Mit Deckel | 230°C | 25 min | 25 min | 25 min |
| 2: Ohne Deckel | 215°C | 20 min | 30 min | 40 min |
| **Gesamt** | | **45 min** | **55 min** | **65 min** |

**Mehlmenge > 1000g = mehrere Brote backen.**

### F.9 Nach dem Backen

- **Weizen/Dinkel/Urkorn/Semola:** Mind. 30-60 min auf Gitter auskuehlen.
- **Roggen:** Mind. **24 Stunden** liegen lassen, bevor das Brot angeschnitten wird!
- Kerntemperatur fertig: 95-98°C (Roggen: 98-100°C).

---

## G. Arbeitsschritt-Anleitungstexte

### G.1 Fermentolyse (Dinkel/Urkorn/Vollkorn)
> "Mehl und Wasser zusammen mit {TRIEBMITTEL} in einer grossen Schuessel grob vermischen, bis kein trockenes Mehl mehr sichtbar ist. **Noch kein Salz zugeben!** Mit einem Tuch abdecken und 15 Minuten ruhen lassen. Das Mehl saugt das Wasser auf und beginnt von allein Gluten zu bilden."

### G.2 Kneten (Weizen/Semola)
> "Teig auf eine leicht bemehlte Arbeitsflaeche geben. Zuerst 2-3 Minuten langsam kneten, dann 2-3 Minuten kraeftiger. Jetzt das Salz zugeben und weitere 4-8 Minuten kneten, bis der Teig glatt und elastisch ist. **Fenstertest:** Ein Stueck Teig duenn auseinanderziehen - er sollte so duenn werden, dass Licht durchscheint, ohne zu reissen."

### G.3 Kneten (Dinkel/Urkorn)
> "Teig auf eine leicht bemehlte Arbeitsflaeche geben und **maximal 8-9 Minuten** von Hand kneten. **Vorsicht: Dinkel/Urkorn kann leicht ueberknetet werden!** Der Teig muss nicht perfekt glatt sein - die Stretch & Folds uebernehmen den Rest."

### G.4 Mischen (Roggen >=75%)
> "Alle Zutaten (inkl. Salz) in einer grossen Schuessel 3-5 Minuten kraeftig zusammenruehren. **Roggenteig wird NICHT geknetet** wie Weizen - nur gruendlich vermischen, bis eine gleichmaessige Masse entsteht. Der Teig bleibt klebrig, das ist normal."

### G.5 Stretch & Fold
> "Teig in der Schuessel lassen. Mit nassen Haenden eine Seite des Teigs hochziehen und zur Mitte falten. Schuessel um 90° drehen und wiederholen - insgesamt 4x (Nord, Sued, Ost, West). Abdecken und 15 Minuten warten bis zur naechsten Runde. Insgesamt 3 Runden."

### G.6 Formen (Weizen/Dinkel/Urkorn/Semola)
> "Teig auf eine leicht bemehlte Flaeche geben. Von allen Seiten zur Mitte falten, umdrehen (Schluss nach unten), und mit beiden Haenden in kreisenden Bewegungen zu einer Kugel formen. Dabei Spannung auf der Oberflaeche aufbauen."

### G.7 Formen (Roggen)
> "Haende und Arbeitsflaeche gut anfeuchten (Roggenteig ist sehr klebrig). Den Teig vorsichtig zu einem laenglichen Laib oder einer Kugel formen. **Nicht zu viel formen** - Roggenteig verliert schnell sein Gas. In ein gut bemehltes Gaerkoerbchen legen (Schluss nach oben)."

### G.8 Fingertest
> "Druecke mit einem bemehlten Finger ca. 1-2 cm tief in den Teig. Springt die Delle langsam und nur teilweise zurueck = perfekt, ab in den Ofen! Springt sie sofort komplett zurueck = noch 15-30 Minuten warten. Bleibt die Delle stehen = leicht uebergaert, sofort backen."

### G.9 Einschneiden (Weizen/Dinkel/Urkorn/Semola)
> "Mit einem scharfen Messer oder einer Rasierklinge 1-2 Schnitte ca. 1 cm tief in die Oberflaeche setzen. Schnell und entschlossen schneiden. Das Einschneiden kontrolliert, wo das Brot aufreisst."

### G.10 Roggen-Reifeerkennung
> "Roggenbrote werden **nicht** mit dem Fingertest geprueft. Stattdessen: Das Brot ist bereit fuer den Ofen, wenn typische feine Risse im Mehl auf der Oberflaeche sichtbar werden."

---

## H. Beispiel-Timelines

### Szenario 1: Schnelles Weizenbrot (Level 1, 4-5h, Hefe, 500g Weizen 1050)

**Rezeptberechnung:**
- Mehl: 500g Weizen 1050
- TA 168 (Level 1, kein Kochstueck) → Wasser: 500 x 0,68 = 340g
- Hefe (1,0%): 5g frische Hefe
- Salz (2%): 10g

**Zeitplan:**
```
+0:00  Zutaten abwiegen
+0:10  Kneten (12 min von Hand)
+0:25  Stretch & Fold 1
+0:40  Stretch & Fold 2
+0:55  Stretch & Fold 3
+1:10  Restliche Stockgare (bis ~2h gesamt)
+2:10  Formen (10 min)
+2:20  Stueckgare (90 min)
+3:50  Ofen vorheizen (Topf, 40 min)
+4:30  Backen (40 min: 25 mit Deckel 250°C, 15 ohne 230°C)
+5:10  Auskuehlen (30 min)
= ~5:15 gesamt
```

**Wasserbilanz:** 340g Hauptteig-Wasser = 340g gesamt

---

### Szenario 2: Mischbrot mit Sauerteig (Level 3, 12h, ST, 400g Weizen 1050 + 100g Roggen als Neben, Sonnenblumenkerne)

**Rezeptberechnung:**
- Mehl gesamt: 500g (80% = 400g Weizen 1050, 20% = 100g Roggen)
- Roggen < 50% → ST optional, hier gewaehlt fuer Geschmack
- TA 173 (Level 3, Basis) → Wasser gesamt: 500 x 0,73 = 365g
- Roggensauer: 50g Roggenmehl + 50g Wasser + ASG (1:5:5 → ~9g)
  - Roggen im Hauptteig: 100g - 50g = 50g
- Sonnenblumenkerne: 15% = 75g → Bruehstueck-Wasser: 75 x 0,75 = 56g (erhoehen TA nicht)
- Salz (2%): 10g
- Hauptteig-Wasser: 365g - 50g (ST) - 56g (Bruehstueck) = 259g
- Hauptteig-Mehl: 400g Weizen + 50g Roggen = 450g

**Zeitplan:**
```
+0:00  Sauerteig ansetzen (1:5:5, 50g Roggenmehl + 50g Wasser + 9g ASG)
+0:00  Bruehstueck: 75g Sonnenblumenkerne + 56g heisses Wasser
+8:00  Kneten (12 min): Mehl + Wasser + ST, nach 5 min Salz
+8:15  Bruehstueck unterkneten
+8:20  Stretch & Fold 1
+8:35  Stretch & Fold 2
+8:50  Stretch & Fold 3
+9:05  Restliche Stockgare (bis ~10:30)
+10:30 Formen
+10:40 Stueckgare (90 min)
+12:10 Ofen vorheizen (Stein, 50 min)
+13:00 Backen (45 min: 10 min Schwaden 250°C, 35 min 230°C)
+13:45 Auskuehlen (45 min)
= ~13:00-14:00 gesamt
```

**Wasserbilanz:** 50g (ST) + 56g (Bruehstueck) + 259g (Hauptteig) = 365g

---

### Szenario 3: Langsames Dinkelbrot (Level 4, 24h, Hybrid, Haferflocken)

**Rezeptberechnung:**
- Mehl gesamt: 500g Dinkel 630
- TA Basis 176 (Level 4) + Kochstueck (+5) + Hafer-Bruehstueck (+5) = 186, gedeckelt auf **185** (Max Level 4)
- Wasser gesamt: 500 x 0,85 = 425g
- Auto-Kochstueck: 20g Dinkelmehl + 100g Wasser (4%, 1:5)
- Hafer-Bruehstueck: 50g Haferflocken (10%) + 200g Wasser (1:4)
- Dinkelsauer (Hybrid): 50g Dinkelmehl + 50g Wasser + ~5g ASG (1:10:10)
- Hefe (Hybrid, 12-24h halbiert): 0,05-0,15% = ~0,5g frische Hefe
- Salz (2%): 10g
- Hauptteig-Mehl: 500 - 20 (Kochst.) - 50 (ST) = 430g
- Hauptteig-Wasser: 425 - 100 (Kochst.) - 200 (Hafer) - 50 (ST) = 75g

**Zeitplan:**
```
Tag 1, 20:00:
  Sauerteig ansetzen (Dinkelsauer 1:10:10)
  Kochstueck kochen (20g Dinkelmehl + 100g Wasser, auf 65°C)
  Bruehstueck: 50g Haferflocken + 200g heisses Wasser
  → alles ueber Nacht stehen lassen

Tag 2, 08:00:
  Fermentolyse (15 min): 430g Dinkelmehl + 75g Wasser + ST + Kochstueck + 0,5g Hefe
  08:15  Salz einkneten (8 min), Bruehstueck unterkneten
  08:25  Stretch & Fold 1
  08:40  Stretch & Fold 2
  08:55  Stretch & Fold 3
  09:10  Restliche Stockgare (bis ~10:00, kuerzer wegen Fermentolyse)
  10:00  Formen
  10:15  Stueckgare im Kuehlschrank (mind. 8h)

Tag 2, 18:00-20:00:
  Ofen + Topf vorheizen (45 min)
  Direkt aus Kuehlschrank backen (50 min: 25 Deckel 250°C, 25 ohne 230°C)
  Auskuehlen (60 min)
= ~24h gesamt
```

**Wasserbilanz:** 100g (Kochst.) + 200g (Hafer) + 50g (ST) + 75g (Hauptteig) = 425g
