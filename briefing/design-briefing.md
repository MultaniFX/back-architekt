# Design-Briefing: Brot-Konfigurator

## Kontext

Hobby-Baecker stehen vor dem Problem, dass das Erstellen eigener Brotrezepte komplexe Baeckermathematik erfordert (Teigausbeute, Versaeuerungsgrad, Hydration, Bruehstueck-Berechnung). Statt eines Taschenrechners soll ein gefuehrter Wizard entstehen, der diese Komplexitaet im Hintergrund loest und ein individuelles, grammgenaues Rezept mit Zeitplan ausgibt.

**Arbeitstitel:** "Der Brot-Architekt" / "Brot-Konfigurator" (Name noch offen)
**Plattform:** Responsive Web-App (Mobile + Desktop)
**Zielgruppe:** Breites Spektrum - Anfaenger bis erfahrene Hobby-Baecker
**Visueller Stil:** Warm & handwerklich (Erdtoene, Baeckerei-Charme)
**Bildsprache:** Icons + Typografie (minimalistisch, keine Fotos im MVP)

---

## 1. Screen-Inventar & User Flow

### 1.1 Landing / Start
- Kurze Einleitung: "Bau dir dein eigenes Brot - Schritt fuer Schritt"
- CTA-Button: "Rezept erstellen" / "Los geht's"
- Optional: Kurze Animation oder Hero-Illustration

### 1.2 Wizard (4 Schritte)

**Prinzip:** Schritt-fuer-Schritt, ein Schritt pro Ansicht. Die Auswahl in Schritt 1 beeinflusst, welche Optionen in den Folgeschritten sichtbar sind (Progressive Disclosure basierend auf Erfahrungslevel).

---

#### Schritt 1: Zeit & Erfahrung
*"Wie viel Zeit hast du und wie erfahren bist du?"*

Dieser Schritt ist die Grundlage - alles andere haengt davon ab.

| Element | Typ | Details |
|---------|-----|---------|
| **Zeitbudget** | Stunden-Slider | Bereich: 4h - 48h. Markierungen bei typischen Werten (6h, 12h, 24h). |
| **Erfahrungslevel** | Slider 1-5 mit Labels | 1 = Einsteiger, 2 = Grundkenntnisse, 3 = Fortgeschritten, 4 = Erfahren, 5 = Profi. Jede Stufe mit kurzer Beschreibung darunter sichtbar. |
| **Direkt aus Kuehlschrank backen?** | Toggle/Checkbox | **Nur sichtbar ab 12h Zeitbudget.** Wenn aktiv: Brot wird geformt, ueber Nacht in den Kuehlschrank gestellt und am naechsten Tag direkt gebacken. |

**Auswirkung des Erfahrungslevels auf den gesamten Wizard:**

| Level | Mehl-Optionen | Teig-Eigenschaften |
|:-----:|--------------|-------------------|
| 1 | 1 Hauptmehl, keine weiteren Mehle, 1 Extra | TA 168, Topf empfohlen |
| 2 | 1 Hauptmehl + 1 weiteres Mehl, 2 Extras | TA 170, Topf |
| 3 | 1 Hauptmehl + 2 weitere Mehle (Urkorn als Neben) | TA 173, frei waehlbar |
| 4 | 2 Hauptmehle + 3 weitere Mehle (Urkorn als Haupt) | TA 176 (max 185 mit Kochst.), freigeschoben moeglich |
| 5 | 3 Hauptmehle + 3 weitere Mehle (Urkorn als Haupt) | TA 180 (max 190 mit Kochst.), freigeschoben empfohlen |

---

#### Schritt 2: Basis-Infos
*"Dein Teig: Triebmittel & Mehl"*

| Element | Typ | Details |
|---------|-----|---------|
| **Triebmittel** | 3 Auswahl-Karten | Hefe / Sauerteig / Beides (Hybrid) |
| **Sauerteig-Typ** | 4 Auswahl-Chips | **Nur sichtbar wenn ST/Hybrid gewaehlt.** Roggen / Weizen / Dinkel / Lievito Madre. Jeder Typ mit kurzem Erklaertext. |
| **Sauerteig-Status** | Toggle/Frage | **Immer sichtbar wenn ST/Hybrid gewaehlt.** "Ist dein Sauerteig einsatzbereit?" Ja/Nein. Bei Nein + < 8h: Warnung dass Zeit nicht reicht. |
| **Sauerteig-Warnhinweis** | Kontextueller Hinweis | Bei < 8h: "Dein Sauerteig muss bereits einsatzbereit sein." / Bei 8-12h: "Es ist nur eine schnelle Auffrischung moeglich (~4h)." |
| **Anfaenger-ST-Hinweis** | Info-Box | Bei Level 1-2 + reinem ST: "Wir empfehlen Back-Anfaengern ein klein wenig Hefe zur Gelingsicherheit. Wir fuegen automatisch eine kleine Menge Hefe hinzu." |
| **Roggen-Hinweis** | Kontextueller Hinweis | Bei Roggen > 50%: "Roggenbrote benoetigen Sauerteig fuer die Teigstruktur." Hefe-only wird ausgegraut. Bei Roggen > 75% + > 8h: Hefe-only ausgegraut. |
| **Hauptmehl(e)** | Dropdown(s) | Anzahl abhaengig vom Level (1-3). Gruppiert nach Getreidesorte. Urkorn ab Level 4. |
| **Weitere Mehle** | Dropdown(s) | Ab Level 2. Anzahl abhaengig vom Level (1-3). Urkorn ab Level 3. |
| **Mehlmenge** | Slider oder +/- Stepper | Default: 500g, einstellbar in 50g-Schritten. Max 1000g (darueber = mehrere Brote). |

**Verfuegbare Mehlsorten (als Dropdown-Optionen):**

| Getreide | Verfuegbare Typen | Ab Level (Haupt) | Ab Level (Neben) |
|----------|-----------------|:----------------:|:----------------:|
| **Weizen** | 550, 812, 1050, 1600, Vollkorn | 1 | 1 |
| **Roggen** | 997, 1150, 1370, 1740, Vollkorn | 1 | 1 |
| **Dinkel** | 630, 812, 1050, Vollkorn | 1 | 1 |
| **Semola** | (Hartweizen) | 1 | 1 |
| **Emmer** | 812, Vollkorn | 4 | 3 |
| **Einkorn** | 812, Vollkorn | 4 | 3 |
| **Kamut** | Vollkorn | 4 | 3 |

---

#### Schritt 3: Extras
*"Moechtest du Extras einarbeiten?"*

| Element | Typ | Details |
|---------|-----|---------|
| **Extras** | Multi-Select Chips/Tags | Mehrere waehlbar. System berechnet automatisch Bruehstueck + Wasser. Max Anzahl abhaengig von Level. |

**Verfuegbare Extras:**

| Extra | Kategorie | Hinweis |
|-------|-----------|---------|
| Sonnenblumenkerne | Koerner | Bruehstueck 1:0,75 |
| Kuerbiskerne | Koerner | Bruehstueck 1:0,75 |
| Sesam | Koerner | Bruehstueck 1:0,75 |
| Leinsamen | TA-erhoehend | Bruehstueck 1:4 |
| Haferflocken | TA-erhoehend | Bruehstueck 1:4 |
| Altbrot | TA-erhoehend | Bruehstueck 1:4 |
| Schrot (Weizen/Roggen) | TA-erhoehend | Bruehstueck 1:4 |

**Einschraenkungen:**
- Bei 4h: Hinweis "Koerner werden trocken eingearbeitet. Schrot nicht verfuegbar bei 4h."
- Bei 6-8h + Sauerteig: Hinweis "Kein Bruehstueck moeglich - Sauerteig braucht die Zeit."

*(Erweiterung spaeter: Roestzwiebeln, Brotgewuerz (bei Roggen), Oliven, getrocknete Tomaten etc.)*

---

#### Schritt 4: Backmethode
*"Wie backst du dein Brot?"*

| Element | Typ | Details |
|---------|-----|---------|
| **Backmethode** | 3 Auswahl-Karten | Topf (Gusseisen/Dutch Oven) / Pizzastein / Backstahl |
| **Empfehlung** | Visueller Hinweis | Je nach Level wird eine Methode als "Empfohlen" markiert. |

**Empfehlungs-Logik:**

| Level | Empfehlung |
|:-----:|-----------|
| 1-2 | Topf (empfohlen) - Einfachste Methode, beste Kruste fuer Anfaenger |
| 3 | Alle gleichwertig |
| 4-5 | Pizzastein / Backstahl (empfohlen) - Mehr Kontrolle |

---

#### Wizard-Navigation
- **Progress-Indikator** oben: 4 Schritte als Dots oder nummerierte Leiste
- **"Weiter" / "Zurueck"** Buttons am unteren Rand
- **Zusammenfassung** der bisherigen Auswahl als Tags/Chips am oberen Rand sichtbar
- Schritt 3 (Extras) kann uebersprungen werden ("Keine Extras")

---

### 1.3 Ergebnis-Seite (Rezept-Output)

Das Herzstueck - hier wird das generierte Rezept angezeigt:

#### Rezept-Header
- **Dynamischer Name:** z.B. "Schnelles Weizen-Mischbrot" (generiert aus den Auswahlen)
- **Tags:** Schwierigkeit, Zeitaufwand, Mehlsorte, Backmethode
- **Brot-Steckbrief:** Teigausbeute, Gesamtgewicht, Backzeit auf einen Blick

#### Zutaten-Liste
- Uebersichtliche Tabelle, grammgenau
- Gruppiert nach: Sauerteig | Kochstueck | Bruehstueck | Hauptteig
- Bezogen auf die gewaehlte Mehlmenge (500g default)
- Moeglichkeit die Mehlmenge nachtraeglich zu aendern (Neuberechnung)

#### Zeitplan / Timeline
- Visuelle Timeline (vertikal, wie eine Zeitleiste)
- Startpunkt: "Jetzt" (aktuelle Uhrzeit)
- Jeder Schritt mit: **Uhrzeit, Aktion, Dauer, ausfuehrliche Erklaerung des Arbeitsschritts**
- Pflicht-Schritte hervorgehoben (Kneten, Formen)
- **Arbeitsschritte gut erklaeren** - nicht nur "Kneten", sondern ausfuehrliche Anleitung mit Tipps
- **Unterschiedliche Texte fuer Weizen vs. Roggen** (Roggen wird gemischt, nicht geknetet)

#### Backhinweise
- Anleitung passend zur gewaehlten Backmethode (Topf/Stein/Stahl)
- Temperatur, Schwaden-Empfehlung, Backzeit (nach Mehlmenge skaliert)
- Wann Deckel abnehmen (bei Topf), wann Dampf ablassen (bei Stein/Stahl)
- **Roggen-Hinweis:** "Bitte das Brot mindestens 24 Stunden ruhen lassen, bevor es angeschnitten wird!"

#### Aktionen
- "Neues Rezept" Button (zurueck zum Wizard)
- "Drucken" Button (Browser-Print, MVP-tauglich)
- PDF-Export (Nice-to-have, nicht MVP)

---

## 2. Design-Richtlinien

### 2.1 Farbpalette (Vorschlag)
- **Primary:** Warmes Braun / Terrakotta (Brotkruste)
- **Secondary:** Cremiges Beige / Weizengelb (Teig/Mehl)
- **Accent:** Warmes Orange oder Rostrot (CTAs, Highlights)
- **Background:** Helles Creme/Off-White (Mehlstaub-Feeling)
- **Text:** Dunkles Braun / Fast-Schwarz
- **Erfolg/Info:** Gedaempftes Gruen, gedaempftes Blau
- **Warnung:** Warmes Gelb (fuer Sauerteig-Hinweise etc.)
- **Deaktiviert:** Gedaempftes Grau (fuer ausgegraute Optionen wie Hefe bei Roggen > 50%)

### 2.2 Typografie
- **Headlines:** Serif oder Slab-Serif (handwerklich, warm) - z.B. Playfair Display, Lora, oder Merriweather
- **Body:** Gut lesbare Sans-Serif - z.B. Inter, Source Sans, oder Nunito
- **Zahlen/Mengen:** Monospace oder Tabular Figures fuer die Rezept-Tabelle

### 2.3 Icons
- Linien-Icons, leicht gerundet (freundlich, nicht zu technisch)
- Konsistenter Stil: 2px Strichstaerke, abgerundete Ecken
- Thematisch: Brot, Mehl, Waage, Uhr, Thermometer, Hefe, Sauerteig-Glas, Topf, Pizzastein, Backstahl, Kuehlschrank
- Icon-Set: z.B. Phosphor Icons, Tabler Icons, oder Custom

### 2.4 Komponenten-Stil
- **Karten:** Leicht abgerundete Ecken (12-16px), dezenter Schatten, Hover/Active-State
- **Buttons:** Abgerundet, gross genug fuer Touch (min. 48px), warme Farben
- **Slider:** Grosser Anfasser, deutlicher Track, Werte-Label sichtbar
- **Dropdowns:** Klar beschriftet, gruppiert nach Getreidesorte
- **Chips/Tags:** Fuer Extras-Auswahl und Zusammenfassungs-Anzeige
- **Toggles:** Fuer Ja/Nein-Fragen (ST-Status, Kuehlschrank)
- **Warnhinweise:** Gelber/oranger Kasten mit Icon (fuer Sauerteig-Zeitwarnungen, Roggen-Hinweise)
- **Info-Box:** Blauer Kasten mit Icon (fuer Anfaenger-Hinweise)
- **Deaktivierte Optionen:** Ausgegraut mit Erklaerungstext warum nicht verfuegbar
- **Wizard-Karten:** Gross, mit Icon, eindeutig klickbar, Selected-State deutlich

### 2.5 Responsive Breakpoints
- Mobile: 320px - 768px (Wizard-Karten untereinander, volle Breite)
- Tablet: 768px - 1024px (2-spaltig wo sinnvoll)
- Desktop: 1024px+ (max-width Container ~960px, zentriert)

---

## 3. Logik-Zusammenfassung (fuer Designer-Kontext)

Der Designer muss nicht die Formeln kennen, aber die Auswirkungen verstehen:

| Nutzer-Auswahl | Auswirkung auf Rezept |
|---|---|
| **Zeit < 8h + Sauerteig** | Warnhinweis: "Sauerteig muss einsatzbereit sein" |
| **Zeit 8-12h + Sauerteig** | Warnhinweis: "Nur schnelle Auffrischung moeglich" |
| **Sauerteig gewaehlt** | Zusaetzliche Fragen: ST-Typ + ST-Status erscheinen |
| **Sauerteig + Level 1-2** | Info-Box: "Kleine Menge Hefe wird hinzugefuegt" |
| **Dinkel/Urkorn als Hauptmehl** | Automatisches Kochstueck (unsichtbar fuer User, ausser in Zutatenliste) |
| **Roggen > 50%** | Hefe-only wird ausgegraut, ST ist Pflicht |
| **Roggen > 75% + > 8h** | Hefe-only komplett ausgegraut |
| **Level 1-2** | Weniger Mehl-Optionen, weniger Extras, Topf empfohlen |
| **Level 4-5** | Urkorn freigeschaltet, hoehere TA, Stein/Stahl empfohlen, Kochstueck + Bruehstueck gleichzeitig |
| **Ab 12h Zeitbudget** | Toggle "Direkt aus Kuehlschrank backen?" erscheint |
| **4h Zeitbudget** | Keine Bruehstuecke, Koerner trocken, Schrot nicht verfuegbar |
| **6-8h + Sauerteig** | Kein Bruehstueck moeglich |
| **Extras mit Hafer/Leinsamen/Altbrot/Schrot** | Erhoehte TA, bei Level 1-3 ersetzt Kochstueck bei Dinkel |
| **Topf** | Backanleitung mit Deckel-Timing, Vorheizzeit |
| **Pizzastein/Backstahl** | Backanleitung mit Schwaden/Dampf |
| **Roggen-Brot** | Hinweis: "24h ruhen vor dem Anschneiden" |

---

## 4. MVP-Scope vs. Spaeter

### MVP (Version 1)
- [x] 4-Schritte-Wizard (Zeit&Erfahrung / Basis / Extras / Backmethode)
- [x] Level-basierte progressive Mehlauswahl (Dropdowns)
- [x] Kontextuelle Warnhinweise (Sauerteig + Zeit, Roggen + Hefe)
- [x] Sauerteig-Typ und Status-Abfrage
- [x] Toggle "Direkt aus Kuehlschrank" (ab 12h)
- [x] Automatisches Kochstueck (Dinkel/Urkorn)
- [x] Rezept-Generierung mit grammgenauen Mengen (Basis: Mehlmenge)
- [x] Zeitplan mit echten Uhrzeiten + ausfuehrlichen Arbeitsschritt-Erklaerungen
- [x] Unterschiedliche Anleitungstexte fuer Weizen vs. Roggen
- [x] Backhinweise passend zur Methode + nach Mehlmenge skaliert
- [x] Dynamischer Rezeptname
- [x] Responsive Design (Mobile + Desktop)
- [x] Browser-Druckansicht
- [x] Icons + Typografie basiertes Design
- [x] Semola als eigene Mehlkategorie

### Spaeter (V2+)
- [ ] PDF-Export
- [ ] Weitere Extras (Roestzwiebeln, Brotgewuerz, Oliven etc.)
- [ ] Temperatur-Beruecksichtigung (Raumtemperatur, Wassertemperatur)
- [ ] Rezepte speichern / Favoriten
- [ ] Nutzer-Accounts
- [ ] Rezept teilen (Link)
- [ ] Mehr Brotsorten / Spezialbrote
- [ ] Foto-Upload vom Ergebnis
- [ ] Community-Features
- [ ] Dark Mode

---

## 5. Deliverables fuer den Designer

### 5.1 Screens zu designen (12 Screens)
1. Landing Page / Startseite
2. Wizard Schritt 1: Zeit & Erfahrung (Slider-UI + Kuehlschrank-Toggle ab 12h)
3. Wizard Schritt 2: Basis-Infos (Triebmittel-Karten + Mehl-Dropdowns)
4. Wizard Schritt 2: Variante mit Sauerteig-Auswahl (ST-Typ + ST-Status + Warnhinweis)
5. Wizard Schritt 2: Variante mit Roggen-Einschraenkung (Hefe ausgegraut)
6. Wizard Schritt 2: Variante mit Anfaenger-ST-Hinweis (Level 1-2)
7. Wizard Schritt 2: Variante fuer Level 1 vs. Level 5 (unterschiedliche Anzahl Dropdowns)
8. Wizard Schritt 3: Extras (Chips/Multi-Select mit Einschraenkungs-Hinweisen)
9. Wizard Schritt 4: Backmethode (3 Karten mit Empfehlung)
10. Ergebnis-Seite: Rezept mit Zutaten + Timeline + Backhinweise
11. Druckansicht (vereinfacht)
12. Leere/Fehler-States

### 5.2 Zustaende pro Komponente
- **Wizard-Karten:** Default, Hover, Selected, Disabled (ausgegraut mit Erklaerung)
- **Slider:** Default, Active, Wert-Anzeige
- **Dropdowns:** Geschlossen, Offen, Ausgewaehlt, Gruppierte Optionen
- **Chips/Tags:** Default, Selected, Hover, Disabled
- **Toggles:** Off, On, mit Label
- **Buttons:** Default, Hover, Active, Disabled
- **Progress-Bar:** 4 Stufen + aktiver Schritt
- **Warnhinweis:** Gelber Info-Kasten mit Icon
- **Info-Box:** Blauer Info-Kasten mit Icon
- **Timeline:** Aktiver Schritt, abgeschlossen, ausstehend

### 5.3 Responsive
- Jeder Screen in Mobile (375px) und Desktop (1280px)
- Tablet optional aber empfohlen

### 5.4 Uebergabe-Format
- Komponentenbibliothek / Design System (Farben, Typo, Spacing, Icons)
- Klickbarer Prototyp des Wizard-Flows (mindestens Happy Path + Sauerteig-Variante + Roggen-Variante)
- Asset-Export (Icons als SVG)
