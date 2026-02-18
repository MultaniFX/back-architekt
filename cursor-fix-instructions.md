# Cursor-Anweisung: Berechnungsfehler in class-brotarchitekt-calculator.php beheben

**Datei:** `brotarchitekt/includes/class-brotarchitekt-calculator.php`
**Referenz-Regelwerk:** `briefing/rezept-regelwerk.md` und `briefing/entwicklungslogik.md`

Bitte behebe die folgenden 17 Fehler. Aendere NUR die beschriebenen Stellen, keine Refactorings, keine neuen Features. Lies vor jeder Aenderung die Referenz-Dateien im `briefing/`-Ordner.

---

## Fix 1: Time-Bucket-Zuordnung (compute_time_bucket, ~Zeile 129-142)

**Fehler:** h=5 wird '6-8h', h=7 wird '8-12h'. Die Grenzen sind verschoben.

**Ersetze** die gesamte Methode `compute_time_bucket` durch:

```php
protected function compute_time_bucket(): void {
    $h = (int) $this->input['timeBudget'];
    if ( $h <= 6 ) {
        $this->time_bucket = '4-6h';
    } elseif ( $h <= 8 ) {
        $this->time_bucket = '6-8h';
    } elseif ( $h <= 12 ) {
        $this->time_bucket = '8-12h';
    } elseif ( $h <= 24 ) {
        $this->time_bucket = '12-24h';
    } elseif ( $h <= 36 ) {
        $this->time_bucket = '24-36h';
    } else {
        $this->time_bucket = '36-48h';
    }
}
```

---

## Fix 2: Sauerteig-Berechnung (get_ingredients, ~Zeile 390-398)

**Fehler:** `sourdough_pct` ist laut Regelwerk der Mehlanteil im ST als % vom Gesamtmehl. Der Code interpretiert ihn als Gesamtgewicht des ST und teilt dann falsch auf.

**Ersetze** den Block ab `$st_total = ...` bis `$sourdough_water = ...`:

```php
$sourdough_flour = round( $this->total_flour * ( $this->sourdough_pct / 100 ), 0 );
$sourdough_water = round( $sourdough_flour * ( ( $st['ta'] - 100 ) / 100 ), 0 );
$water_main -= $sourdough_water;
```

Entferne die Variablen `$st_ta` und `$st_total` — sie werden nicht mehr gebraucht. Die Zeile `$st_mehl = ...` und `$st_water = ...` entfallen ebenfalls, da `$sourdough_flour` und `$sourdough_water` jetzt direkt berechnet werden.

---

## Fix 3: Fermentolyse-Logik (get_timeline)

**Fehler:** `if ( $this->has_kochstueck || $this->rye_share < 50 )` gibt JEDEM Nicht-Roggen-Brot eine Fermentolyse. Laut Regelwerk nur bei Dinkel >= 60%, Urkorn >= 60% oder Vollkorn >= 60%.

**Fuege** diese Helper-Methode zur Klasse hinzu:

```php
/** Prueft ob Fermentolyse noetig ist (Dinkel/Urkorn >= 60% oder Vollkorn >= 60%). */
protected function needs_fermentolyse(): bool {
    $ancient_share = 0;
    $vollkorn_share = 0;
    foreach ( $this->flour_breakdown as $id => $pct ) {
        $grain = explode( '_', $id, 2 )[0];
        if ( in_array( $grain, array( 'spelt', 'emmer', 'einkorn', 'kamut' ), true ) ) {
            $ancient_share += $pct;
        }
        if ( strpos( $id, '_Vollkorn' ) !== false ) {
            $vollkorn_share += $pct;
        }
    }
    return $ancient_share >= 60 || $vollkorn_share >= 60;
}
```

Ersetze dann in `get_timeline()`:
```php
if ( $this->has_kochstueck || $this->rye_share < 50 ) {
```
durch:
```php
if ( $this->needs_fermentolyse() ) {
```

---

## Fix 4: TA-Erhoehung nur wenn Bruehstueck verfuegbar (compute_ta)

**Fehler:** TA wird um +5 fuer TA-erhoehende Extras erhoeht, auch wenn kein Bruehstueck moeglich ist (4h oder 6-8h + ST).

**Fuege** nach der Erkennung der TA-Extras, aber **vor** der TA-Addition ein:

```php
// Bruehstueck-Verfuegbarkeit: nicht bei <=6h, nicht bei <=8h + Sauerteig
$h = (int) $this->input['timeBudget'];
$leavening = $this->input['leavening'];
$bruehstueck_available = true;
if ( $h <= 6 ) {
    $bruehstueck_available = false;
} elseif ( $h <= 8 && $leavening !== 'yeast' ) {
    $bruehstueck_available = false;
}
if ( ! $bruehstueck_available ) {
    $this->has_ta_raise_bruehstueck = false;
}
```

---

## Fix 5: Bruehstueck-Verfuegbarkeit 6-8h + Hefe erlauben (get_timeline)

**Fehler:** `$time_budget_h >= 8` ist zu restriktiv. 6-8h + Hefe soll Bruehstueck haben.

**Ersetze:**
```php
if ( ! empty( $extras ) && $time_budget_h >= 8 ) {
```
**durch:**
```php
$bruehstueck_in_timeline = $time_budget_h >= 8
    || ( $time_budget_h > 6 && $leavening === 'yeast' );
if ( ! empty( $extras ) && $bruehstueck_in_timeline ) {
```

---

## Fix 6: Bruehstueck parallel zum Sauerteig (get_timeline)

**Fehler:** Bruehstueck wird seriell nach dem ST eingeplant. Laut Regelwerk (Szenario 2+3) laufen ST und Bruehstueck parallel.

**Aenderung:** Bruehstueck und Kochstueck zum selben Zeitpunkt wie ST starten. Kein `$t +=` fuer parallele Schritte.

```php
// Sauerteig ansetzen (+ Bruehstueck/Kochstueck parallel)
if ( $leavening !== 'yeast' ) {
    $st_start = $t;
    // ... ST-Step hinzufuegen ...

    // Bruehstueck parallel zum ST
    if ( ! empty( $extras ) && $bruehstueck_in_timeline ) {
        $steps[] = array(
            'time'     => $st_start,
            'label'    => __( 'Brühstück ansetzen', 'brotarchitekt' ),
            'duration' => 60,
            'desc'     => __( 'Extras mit kochendem Wasser übergießen, 1 Stunde quellen lassen, dann abkühlen.', 'brotarchitekt' ),
        );
    }

    // Kochstueck parallel
    if ( $this->has_kochstueck ) {
        $steps[] = array(
            'time'     => $st_start,
            'label'    => __( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
            'duration' => 10,
            'desc'     => __( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen.', 'brotarchitekt' ),
        );
    }

    $t += $st_duration * 60;
} else {
    // Nur Hefe: Bruehstueck als eigener Schritt
    if ( ! empty( $extras ) && $bruehstueck_in_timeline ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => __( 'Brühstück ansetzen', 'brotarchitekt' ),
            'duration' => 60,
            'desc'     => __( 'Extras mit kochendem Wasser übergießen, 1 Stunde quellen lassen, dann abkühlen.', 'brotarchitekt' ),
        );
        $t += 60 * 60;
    }
    if ( $this->has_kochstueck ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => __( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
            'duration' => 10,
            'desc'     => __( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen.', 'brotarchitekt' ),
        );
        // Kein $t += — Kochstueck ist schnell und kann parallel zum Bruehstueck-Quellen geschehen
    }
}
```

---

## Fix 7: Kochstueck-Schritt im Zeitplan (get_timeline)

Bereits in Fix 6 enthalten — Kochstueck wird jetzt als paralleler Schritt eingefuegt.

---

## Fix 8: Stretch & Fold INNERHALB der Stockgare (get_timeline, ~Zeile 706-768)

**Fehler:** S&F wird als eigene Schritte VOR der Stockgare eingefuegt. Laut Regelwerk F.3 finden S&F **innerhalb** der Stockgare statt: "Gesamtdauer: 45 Minuten (innerhalb der Stockgare)". Die Szenario-Timelines bestaetigen das — S&F laeuft waehrend der Stockgare, danach kommt nur die "restliche Stockgare".

**Aktueller Code-Ablauf (FALSCH):**
```
Kneten (12 min)
S&F 1 (+15 min)   ← 45 min AUSSERHALB
S&F 2 (+15 min)
S&F 3 (+15 min)
Stockgare (90 min) ← volle Dauer ZUSAETZLICH
= 147 min gesamt (statt ~90-120 min)
```

**Korrekter Ablauf (Szenario 1 Referenz):**
```
Kneten (12 min)
S&F 1 (+15 min)   ← innerhalb der Stockgare
S&F 2 (+15 min)
S&F 3 (+15 min)
Restliche Stockgare (45 min bei 90 min gesamt)
= 90 min Stockgare gesamt (inkl. S&F)
```

**Aenderung:** Die Stockgare-Dauer muss um die S&F-Zeit (45 min) **reduziert** werden, wenn S&F stattfindet. Also:

```php
// Stretch & Fold (nicht bei Roggen >= 75%)
$sf_total_min = 0;
if ( $this->rye_share < 75 ) {
    for ( $i = 1; $i <= 3; $i++ ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => sprintf( __( 'Stretch & Fold Runde %d', 'brotarchitekt' ), $i ),
            'duration' => 15,
            'desc'     => __( 'Teig in der Schüssel: eine Seite hochziehen, zur Mitte falten. Schüssel 90° drehen, wiederholen. 4x (Nord, Süd, Ost, West). Abdecken, 15 Min warten.', 'brotarchitekt' ),
        );
        $t += 15 * 60;
    }
    $sf_total_min = 45;
}

// Stockgare (S&F-Zeit bereits abgelaufen, nur restliche Dauer)
// ... Stockgare-Berechnung wie in Fix 9 ...
$stockgare_rest = max( 0, $stockgare_min - $sf_total_min );
if ( $stockgare_rest > 0 ) {
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Restliche Stockgare', 'brotarchitekt' ),
        'duration' => $stockgare_rest,
        'desc'     => __( 'Teig abdecken und gehen lassen.', 'brotarchitekt' ),
    );
    $t += $stockgare_rest * 60;
}
```

**Wenn keine S&F** (Roggen >= 75%): Stockgare wird wie bisher voll angezeigt mit Label "Stockgare".

---

## Fix 9: Stockgare nach Regelwerk-Tabelle (get_timeline)

**Fehler:** Stockgare-Werte sind zu pauschal. Muessen nach Triebmitteltyp und -menge berechnet werden.

**Verwende** diese Berechnung fuer `$stockgare_min` (die GESAMT-Stockgare inkl. S&F-Zeit):

```php
$stockgare_min = 90; // Default
if ( $this->rye_share >= 75 ) {
    // Roggen >75%: nach ST-Anteil (Regelwerk C.5 / F.4)
    if ( $this->sourdough_pct >= 40 ) {
        $stockgare_min = 30;
    } elseif ( $this->sourdough_pct >= 25 ) {
        $stockgare_min = 60;
    } else {
        $stockgare_min = 120;
    }
} elseif ( $this->sourdough_pct > 0 && $this->yeast_pct <= 0 && $this->beginner_yeast_pct <= 0 ) {
    // Reiner Sauerteig ohne Hefe (Regelwerk F.4 "Mit Sauerteig pur")
    if ( $this->sourdough_pct >= 20 ) {
        $stockgare_min = 150;
    } elseif ( $this->sourdough_pct >= 15 ) {
        $stockgare_min = 210;
    } elseif ( $this->sourdough_pct >= 10 ) {
        $stockgare_min = 270;
    } else {
        $stockgare_min = 330;
    }
} elseif ( $this->sourdough_pct > 0 && ( $this->yeast_pct > 0 || $this->beginner_yeast_pct > 0 ) ) {
    // Sauerteig + Hefe: Hybrid oder Anfaenger-Hefe (Regelwerk F.4 "Mit ST + minimaler Hefe")
    if ( $this->sourdough_pct >= 20 ) {
        $stockgare_min = 120;
    } elseif ( $this->sourdough_pct >= 15 ) {
        $stockgare_min = 180;
    } elseif ( $this->sourdough_pct >= 10 ) {
        $stockgare_min = 240;
    } elseif ( $this->sourdough_pct >= 7.5 ) {
        $stockgare_min = 300;
    } else {
        $stockgare_min = 360;
    }
} else {
    // Nur Hefe (Regelwerk F.4 "Mit Hefe")
    if ( $this->yeast_pct >= 1.0 ) {
        $stockgare_min = 105;
    } elseif ( $this->yeast_pct >= 0.3 ) {
        $stockgare_min = 180;
    } else {
        $stockgare_min = 300;
    }
}
```

**Wichtig:** Diese Werte sind die GESAMT-Stockgare. Die tatsaechliche "Restliche Stockgare" im Zeitplan = `$stockgare_min - $sf_total_min` (siehe Fix 8).

---

## Fix 10: Kuehlschrank-Logik komplett ueberarbeiten (get_timeline)

**Fehler:** Die gesamte Kuehlschrank-Logik ist falsch implementiert. Es gibt drei Probleme:

### Problem A: Anspringzeiten fehlen komplett
Regelwerk F.4 verlangt eine warme Anspringzeit VOR dem Kuehlschrank:
- 8h kalt → 120 min warm vorher
- 12h kalt → 90 min warm vorher
- 16h kalt → 60 min warm vorher

### Problem B: "Normal"-Modus (kalte Stockgare) fehlt
Regelwerk F.6: Ab 12h+ (ausser Roggen >75%) wird der Kuehlschrank **immer** benutzt. Die Variable `bakeFromFridge` bestimmt nur den MODUS (kalte Stockgare vs. kalte Stueckgare), nicht OB der Kuehlschrank benutzt wird.

### Problem C: "Direkt aus Kuehlschrank"-Modus ist falsch
**Code (FALSCH):** Stockgare = 8h kalt, Stueckgare = 0
**Regelwerk:** Warme Stockgare → Formen → Kalte Stueckgare (mind. 8h) → Direkt backen

### Loesung

Fuege eine Property hinzu:

```php
protected bool $uses_fridge = false;
```

Berechne in `calculate()` (nach compute_time_bucket):

```php
$h = (int) $this->input['timeBudget'];
$this->uses_fridge = $h >= 12 && $this->rye_share < 75;
```

Dann in `get_timeline()` die Stockgare/Stueckgare komplett ersetzen:

```php
// --- VARIANTE 1: "Normal" = Kalte Stockgare (bakeFromFridge = false, 12h+) ---
// Ablauf: Anspringzeit (warm) → Kuehlschrank (Stockgare) → Rausnehmen → Formen → Warme Stueckgare → Backen
//
// --- VARIANTE 2: "Direkt aus Kuehlschrank" (bakeFromFridge = true, 12h+) ---
// Ablauf: Warme Stockgare → Formen → Kuehlschrank (Stueckgare, mind. 8h) → Direkt backen
//
// --- VARIANTE 3: Kein Kuehlschrank (<12h oder Roggen >75%) ---
// Ablauf: Warme Stockgare → Formen → Warme Stueckgare → Backen

if ( $this->uses_fridge && ! $from_fridge ) {
    // VARIANTE 1: Kalte Stockgare
    // Anspringzeit (warm, vor Kuehlschrank)
    $fridge_hours = $h - 4; // grobe Schaetzung fuer Kuehlschrank-Stunden
    if ( $fridge_hours >= 16 ) {
        $anspring_min = 60;
    } elseif ( $fridge_hours >= 12 ) {
        $anspring_min = 90;
    } else {
        $anspring_min = 120;
    }

    // S&F ist Teil der Anspringzeit (wenn < anspring_min, S&F reicht als Anspringzeit)
    $anspring_rest = max( 0, $anspring_min - $sf_total_min );
    if ( $anspring_rest > 0 ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => __( 'Anspringzeit (warm)', 'brotarchitekt' ),
            'duration' => $anspring_rest,
            'desc'     => __( 'Teig abgedeckt bei Raumtemperatur anspringen lassen, bevor er in den Kühlschrank kommt.', 'brotarchitekt' ),
        );
        $t += $anspring_rest * 60;
    }

    // Kalte Stockgare (Kuehlschrank)
    $cold_hours = max( 8, $fridge_hours );
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Stockgare im Kühlschrank', 'brotarchitekt' ),
        'duration' => $cold_hours * 60,
        'desc'     => sprintf( __( 'Teig abgedeckt %d Stunden im Kühlschrank (4–5°C) gehen lassen.', 'brotarchitekt' ), $cold_hours ),
    );
    $t += $cold_hours * 60 * 60;

    // Formen
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Formen', 'brotarchitekt' ),
        'duration' => 10,
        'desc'     => __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.', 'brotarchitekt' ),
    );
    $t += 10 * 60;

    // Warme Stueckgare
    $stueckgare_min = 90;
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Stückgare (warm)', 'brotarchitekt' ),
        'duration' => $stueckgare_min,
        'desc'     => __( 'Geformtes Brot abdecken und bei Raumtemperatur gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' ),
    );
    $t += $stueckgare_min * 60;

} elseif ( $this->uses_fridge && $from_fridge ) {
    // VARIANTE 2: Direkt aus Kuehlschrank
    // Warme Stockgare (normal berechnet)
    $stockgare_rest = max( 0, $stockgare_min - $sf_total_min );
    if ( $stockgare_rest > 0 ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => __( 'Restliche Stockgare', 'brotarchitekt' ),
            'duration' => $stockgare_rest,
            'desc'     => __( 'Teig abdecken und gehen lassen.', 'brotarchitekt' ),
        );
        $t += $stockgare_rest * 60;
    }

    // Formen
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Formen', 'brotarchitekt' ),
        'duration' => 10,
        'desc'     => __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' ),
    );
    $t += 10 * 60;

    // Kalte Stueckgare (mind. 8h)
    $cold_stueck_hours = max( 8, $h - 6 ); // Rest der Gesamtzeit abzgl. Vorbereitung
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Stückgare im Kühlschrank', 'brotarchitekt' ),
        'duration' => $cold_stueck_hours * 60,
        'desc'     => sprintf( __( 'Geformtes Brot abgedeckt mind. %d Stunden im Kühlschrank lassen. Direkt aus dem Kühlschrank backen.', 'brotarchitekt' ), $cold_stueck_hours ),
    );
    $t += $cold_stueck_hours * 60 * 60;

} else {
    // VARIANTE 3: Kein Kuehlschrank (< 12h oder Roggen > 75%)
    // Restliche Stockgare (nach S&F)
    $stockgare_rest = max( 0, $stockgare_min - $sf_total_min );
    if ( $stockgare_rest > 0 ) {
        $steps[] = array(
            'time'     => $t,
            'label'    => $sf_total_min > 0 ? __( 'Restliche Stockgare', 'brotarchitekt' ) : __( 'Stockgare', 'brotarchitekt' ),
            'duration' => $stockgare_rest,
            'desc'     => __( 'Teig abdecken und gehen lassen.', 'brotarchitekt' ),
        );
        $t += $stockgare_rest * 60;
    }

    // Formen
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Formen', 'brotarchitekt' ),
        'duration' => 10,
        'desc'     => $this->rye_share >= 75
            ? __( 'Hände und Fläche anfeuchten. Teig vorsichtig zu Laib oder Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' )
            : __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.', 'brotarchitekt' ),
    );
    $t += 10 * 60;

    // Warme Stueckgare
    $stueckgare_min = $this->rye_share >= 75 ? 150 : 90;
    $steps[] = array(
        'time'     => $t,
        'label'    => __( 'Stückgare', 'brotarchitekt' ),
        'duration' => $stueckgare_min,
        'desc'     => $this->rye_share >= 75
            ? __( 'Brot im Gärkörbchen gehen lassen. Fertig wenn feine Risse im Mehl auf der Oberfläche sichtbar werden.', 'brotarchitekt' )
            : __( 'Geformtes Brot abdecken und gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' ),
    );
    $t += $stueckgare_min * 60;
}
```

**Wichtig:** Dieser Block ersetzt ALLES ab "Stockgare" bis einschliesslich "Stueckgare" im alten Code. Die Schritte "Formen" und "Stueckgare" sind jetzt Teil der drei Varianten, nicht mehr separate Bloecke danach.

---

## Fix 11: Backstahl Vorheizzeit (get_timeline)

**Fehler:** Backstahl bekommt 50 min, soll aber 35 min (Mitte aus 30-40) bekommen.

**Ersetze:**
```php
$preheat = $this->input['backMethod'] === 'pot' ? 40 : 50;
```
**durch:**
```php
$method = $this->input['backMethod'];
if ( $method === 'pot' ) {
    $preheat = 40;
} elseif ( $method === 'steel' ) {
    $preheat = 35;
} else {
    $preheat = 50; // Pizzastein
}
```

---

## Fix 12: Roggen-Schwaden nur 5 min (get_baking_instructions)

**Fehler:** Offenes Backen zeigt immer "10 Min" Schwaden, auch bei Roggen (soll 5 min sein laut Regelwerk F.8).

**Ersetze** den `else`-Zweig (offenes Backen):
```php
} else {
    $schwaden_min = $is_rye ? 5 : 10;
    $text = sprintf(
        __( 'Mit Schwaden/Dampf %d°C: %d Min. Dann Dampf ablassen, %d°C: weitere %d Min.', 'brotarchitekt' ),
        $temp1,
        $schwaden_min,
        $temp2,
        $duration - $schwaden_min
    );
}
```

---

## Fix 13: 4h-Brot Extras ohne Bruehstueck (get_ingredients)

**Fehler:** Bei 4h werden Extras immer als Bruehstueck mit Wasserabzug berechnet. Laut Regelwerk E.4: Bei 4h Koerner trocken (kein extra Wasser), Altbrot/Hafer/Leinsamen mit gleicher Menge Wasser, Schrot nicht verfuegbar.

**Ersetze** die bestehende `foreach ( $extras as $eid )`-Schleife durch:

```php
$h = (int) $this->input['timeBudget'];
$is_quick = $h <= 6; // 4-6h Bucket = kein Bruehstueck

foreach ( $extras as $eid ) {
    if ( ! isset( $extra_data[ $eid ] ) ) {
        continue;
    }
    $e = $extra_data[ $eid ];

    // Schrot bei 4h nicht verfuegbar
    if ( $is_quick && $eid === 'grist' ) {
        continue;
    }

    $amount = $e['category'] === 'kern'
        ? round( $this->total_flour * ( $kern_count === 1 ? $first_kern : $max_kern / $kern_count ) / 100, 0 )
        : round( $this->total_flour * ( $ta_raise_count === 1 ? $ta_raise_single : $ta_raise_multi ) / 100, 0 );

    if ( $is_quick ) {
        if ( $e['category'] === 'kern' ) {
            // 4h: Koerner trocken einarbeiten, kein extra Wasser
            $bruehstueck[] = array(
                'name'   => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ),
                'amount' => $amount,
                'water'  => 0,
            );
        } else {
            // 4h: Altbrot/Hafer/Leinsamen mit dem Mehl + gleiche Menge Wasser extra
            $water_main += $amount; // Zusaetzliches Wasser UEBER die TA-Menge hinaus
            $bruehstueck[] = array(
                'name'   => $e['name'] . __( ' (mit Mehl einarbeiten)', 'brotarchitekt' ),
                'amount' => $amount,
                'water'  => $amount,
            );
        }
    } else {
        $water_extra = round( $amount * $e['ratio'], 0 );
        $water_main -= $water_extra;
        $bruehstueck[] = array(
            'name'   => $e['name'],
            'amount' => $amount,
            'water'  => $water_extra,
        );
    }
}
```

---

## Fix 14: Kochstueck nur bei Hauptmehl triggern (compute_ta)

**Fehler:** Prueft alle Mehle inkl. Nebenmehle. Soll nur mainFlours pruefen.

**Ersetze:**
```php
$main_flour_ids = array_keys( $this->flour_breakdown );
foreach ( $main_flour_ids as $id ) {
```
**durch:**
```php
$main_flour_ids = array_filter( (array) $this->input['mainFlours'] );
foreach ( $main_flour_ids as $id ) {
```

---

## Fix 15: Extras im Gesamtgewicht beruecksichtigen (get_recipe_meta + get_recipe_teaser)

**Fehler:** `weight` = Mehl + Wasser + Salz. Die Extras (Koerner, Haferflocken etc.) fehlen komplett im angezeigten Gesamtgewicht.

**Ersetze** in `get_recipe_meta()` und `get_recipe_teaser()`:
```php
'weight' => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 ),
```
**durch:**
```php
'weight' => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 + $this->get_extras_weight() ),
```

**Fuege** diese Helper-Methode hinzu:

```php
/** Gesamtgewicht aller Extras (ohne Bruehstueck-Wasser, das ist bereits in water_total). */
protected function get_extras_weight(): float {
    $extras = (array) $this->input['extras'];
    $extra_data = Brotarchitekt_Data::get_extras();
    $level = (int) $this->input['experienceLevel'];

    $kern_count = 0;
    $ta_raise_count = 0;
    foreach ( $extras as $eid ) {
        if ( ! isset( $extra_data[ $eid ] ) ) {
            continue;
        }
        if ( $extra_data[ $eid ]['category'] === 'kern' ) {
            $kern_count++;
        } else {
            $ta_raise_count++;
        }
    }

    $max_kern = $level <= 3 ? 20 : 30;
    $weight = 0;
    foreach ( $extras as $eid ) {
        if ( ! isset( $extra_data[ $eid ] ) ) {
            continue;
        }
        $e = $extra_data[ $eid ];
        if ( $e['category'] === 'kern' ) {
            $pct = $kern_count === 1 ? 15 : $max_kern / $kern_count;
        } else {
            $pct = $ta_raise_count === 1 ? 10 : 5;
        }
        $weight += $this->total_flour * $pct / 100;
    }
    return $weight;
}
```

---

## Fix 16: Wassermenge — Bruehstueck-Wasser bei 6-8h + ST nicht abziehen (get_ingredients)

**Fehler:** Wenn bei 6-8h + Sauerteig Extras gewaehlt sind, gibt es laut Regelwerk KEIN Bruehstueck (E.3). Der Code berechnet aber trotzdem Bruehstueck-Wasser und zieht es vom Hauptteig-Wasser ab. Das Hauptteig-Wasser wird dadurch zu niedrig.

**Loesung:** Die gleiche `bruehstueck_available`-Pruefung aus Fix 4 (compute_ta) muss auch in `get_ingredients()` VOR der Bruehstueck-Schleife greifen. Wenn kein Bruehstueck moeglich ist UND es kein 4h-Brot ist, sollen die Extras trotzdem als Zutat aufgelistet werden, aber OHNE Wasserabzug:

Fuege vor der Extras-Schleife ein:

```php
$bruehstueck_possible = true;
if ( $h <= 6 ) {
    // is_quick — wird separat behandelt
} elseif ( $h <= 8 && in_array( $this->input['leavening'], array( 'sourdough', 'hybrid' ), true ) ) {
    $bruehstueck_possible = false;
}
```

Dann in der Schleife fuer den `else`-Fall (nicht-quick):

```php
} else {
    if ( $bruehstueck_possible ) {
        $water_extra = round( $amount * $e['ratio'], 0 );
        $water_main -= $water_extra;
        $bruehstueck[] = array(
            'name'   => $e['name'],
            'amount' => $amount,
            'water'  => $water_extra,
        );
    } else {
        // 6-8h + ST: Kein Bruehstueck moeglich, Koerner trocken, kein Wasserabzug
        $bruehstueck[] = array(
            'name'   => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ),
            'amount' => $amount,
            'water'  => 0,
        );
    }
}
```

---

## Fix 17: Roggen > 75% — kein Kuehlschrank (uses_fridge)

**Fehler:** Sicherstellen dass `uses_fridge = false` bei Roggen > 75%. Regelwerk F.6: "Roggen > 75%: Nie".

Dies ist in Fix 10 bereits enthalten: `$this->uses_fridge = $h >= 12 && $this->rye_share < 75;`

Pruefe zusaetzlich, dass im alten `from_fridge`-Handling keine kalte Gaerung fuer Roggen >75% passiert.

---

## Reihenfolge der Fixes

Bitte in dieser Reihenfolge umsetzen, da manche Fixes aufeinander aufbauen:

1. **Fix 1** (Time-Bucket) — Grundlage fuer alle Zeitberechnungen
2. **Fix 2** (Sauerteig-Berechnung) — Grundlage fuer Wasser und Stockgare
3. **Fix 14** (Kochstueck nur Hauptmehl)
4. **Fix 3** (Fermentolyse)
5. **Fix 4** (TA ohne Zeitcheck)
6. **Fix 16** (Wasser bei 6-8h+ST)
7. **Fix 13** (4h Extras)
8. **Fix 15** (Extras im Gewicht)
9. **Fix 6** (Bruehstueck/Kochstueck parallel + Kochstueck-Step = Fix 7 inklusive)
10. **Fix 8 + 9 + 10** (S&F innerhalb Stockgare + Stockgare-Tabelle + Kuehlschrank-Logik) — zusammen umsetzen, da sie den gesamten Stockgare/Stueckgare-Block ersetzen
11. **Fix 11** (Backstahl Vorheizzeit)
12. **Fix 12** (Roggen Schwaden)

---

## Validierung nach allen Fixes

Pruefe die drei Szenarien aus `briefing/rezept-regelwerk.md` Abschnitt H:

**Szenario 1:** 500g Weizen 1050, Level 1, 4-5h, Hefe
- TA 168 → Wasser 340g, Hefe 5g, Salz 10g
- Keine Fermentolyse (Weizen!), S&F innerhalb Stockgare, Gesamtzeit ~5h

**Szenario 2:** 400g Weizen + 100g Roggen, Level 3, 12h, ST, Sonnenblumenkerne
- TA 173 → Wasser 365g (davon 50g ST, 56g Bruehstueck, 259g Hauptteig)
- Kuehlschrank "Normal": Anspringzeit → Kalte Stockgare → Formen → Warme Stueckgare

**Szenario 3:** 500g Dinkel, Level 4, 24h, Hybrid, Haferflocken
- TA 185 (Basis 176 + Kochstueck +5 + Hafer +5, Cap 185)
- Wasser 425g (davon 100g Kochstueck, 200g Hafer, ST-Wasser, 75g Hauptteig)
- Kuehlschrank "Direkt": Warme Stockgare → Formen → Kalte Stueckgare → Direkt backen
