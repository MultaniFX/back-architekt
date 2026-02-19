<?php
/**
 * Brotarchitekt — Deutsche Sprachdatei
 * Alle UI-Labels, Texte und Templates zentral verwaltet.
 */
return array(

	// ── App ──
	'app_title'    => 'Der Brot-Architekt',
	'app_subtitle' => 'Bau dir dein eigenes Brot — Schritt für Schritt.',

	// ── Progress Steps ──
	'step_zeit'    => 'Zeit',
	'step_mehl'    => 'Mehl',
	'step_extras'  => 'Extras',
	'step_backen'  => 'Backen',
	'step_rezept'  => 'Rezept',

	// ── Navigation ──
	'nav_back'           => '← Zurück',
	'nav_next'           => 'Weiter →',
	'nav_create_recipe'  => 'Rezept erstellen →',
	'nav_new_recipe'     => '↻ Neues Rezept',
	'nav_print'          => '🖨 Drucken',

	// ── Step 1: Zeit & Erfahrung ──
	'step1_title'          => 'Zeit & Erfahrung',
	'step1_subtitle'       => 'Wie viel Zeit hast du und wie erfahren bist du?',
	'step1_hero'           => 'Egal wie lange dein Brot braucht: Deine aktive Arbeitszeit beträgt je nach Rezept nur zwischen 20 und 40 Minuten.',
	'step1_time_title'     => 'Zeitbudget',
	'step1_time_subtitle'  => 'Von Teig bis fertiges Brot',
	'step1_time_unit'      => 'Stunden',
	'step1_cold_gare_hint' => 'Ab einer Zeit von 12 Stunden nutzen wir die „Kalte Gare" im Kühlschrank. Das sorgt für ein tieferes Aroma und macht dich zeitlich absolut flexibel.',
	'step1_fridge_title'   => 'Direkt aus dem Kühlschrank backen?',
	'step1_fridge_desc'    => 'Brot formen, über Nacht kühlen, am nächsten Tag direkt backen',
	'step1_level_title'    => 'Erfahrungslevel',
	'step1_level_subtitle' => 'Beeinflusst die verfügbaren Optionen',

	// ── Vibe Labels (Zeitslider) ──
	'vibe_fast'       => 'Schnelles Feierabendbrot',
	'vibe_relaxed'    => 'Entspannter Backtag',
	'vibe_cozy'       => 'Gemütliches Tagesbrot',
	'vibe_overnight'  => 'Über-Nacht-Brot mit Tiefgang',
	'vibe_slow'       => 'Slow Baking für Genießer',

	// ── Level ──
	'level_1'      => 'Einsteiger',
	'level_2'      => 'Grundkenntnisse',
	'level_3'      => 'Fortgeschritten',
	'level_4'      => 'Erfahren',
	'level_5'      => 'Profi',
	'level_desc_1' => 'Erste Gehversuche am Backen',
	'level_desc_2' => 'Einige Brote gebacken',
	'level_desc_3' => 'Routine mit verschiedenen Mehlen',
	'level_desc_4' => 'Viele Brote, auch Sauerteig',
	'level_desc_5' => 'Erfahren mit allen Techniken',

	// ── Step 2: Mehl & Triebmittel ──
	'step2_title'            => 'Mehl & Triebmittel',
	'step2_subtitle'         => 'Dein Teig: Triebmittel & Mehlauswahl',
	'step2_leavening_label'  => 'Triebmittel',
	'step2_main_flour_label' => 'Hauptmehle',
	'step2_main_flour_hint'  => 'Wähle bis zu %d Hauptmehl%s',
	'step2_main_flour_error' => 'Bitte wähle mindestens ein Hauptmehl, um fortzufahren.',
	'step2_side_flour_label' => 'Weitere Mehle',
	'step2_side_flour_opt'   => '(optional)',
	'step2_flour_amount'     => 'Mehlmenge',
	'step2_flour_hint'       => 'Basis für alle Berechnungen (in 50g-Schritten, max 1000g)',
	'step2_main_flour_n'     => 'Hauptmehl %d',
	'step2_main_select'      => 'Hauptmehl wählen…',
	'step2_side_flour_n'     => 'Weiteres Mehl %d (optional)',
	'step2_side_select'      => '— optional —',
	'step2_flour_ratio'      => 'Dein Brot besteht zu mindestens 80 % aus Hauptmehl. Wählst du ein „Weiteres Mehl", macht dieses maximal 20 % aus, um die Backstabilität nicht zu gefährden.',
	'step2_flour_modal_title' => 'Mischverhältnisse für Profis',
	'step2_flour_modal_beginner'    => 'Anfänger: 1× Hauptmehl (100 %). Einfach & sicher.',
	'step2_flour_modal_advanced'    => 'Fortgeschritten: 2× Hauptmehl (je 40 %) + 1× Nebenmehl (20 %).',
	'step2_flour_modal_pro'         => 'Profi: 3× Hauptmehl (je ~27 %) + 1× Nebenmehl (20 %).',
	'step2_flour_modal_note'        => 'Gleiche Mehlsorten in verschiedenen Slots addieren sich automatisch.',

	// ── Triebmittel ──
	'leav_yeast'          => 'Hefe',
	'leav_yeast_desc'     => 'Der Klassiker: Schnell, unkompliziert und absolut gelingsicher.',
	'leav_sourdough'      => 'Sauerteig',
	'leav_sourdough_desc' => 'Die Königsdisziplin: Bringt komplexes Aroma und macht das Brot lange haltbar.',
	'leav_hybrid'         => 'Beides',
	'leav_hybrid_desc'    => 'Viel Aroma durch Sauerteig und trotzdem gelingsicher durch einen Hauch Hefe.',

	// ── Sauerteig-Optionen ──
	'st_section_label'   => 'Sauerteig-Details',
	'st_type_label'      => 'Sauerteig-Typ',
	'st_rye'             => 'Roggen-ST',
	'st_rye_desc'        => 'Kräftig, säurebetont',
	'st_wheat'           => 'Weizen-ST',
	'st_wheat_desc'      => 'Mild, triebstark',
	'st_spelt'           => 'Dinkel-ST',
	'st_spelt_desc'      => 'Nussig, aromatisch',
	'st_lievito'         => 'Lievito Madre',
	'st_lievito_desc'    => 'Italienisch, mild-süß',
	'st_ready_question'  => 'Ist dein Sauerteig einsatzbereit?',
	'st_ready_hint'      => 'Aktiv und innerhalb der letzten 12h gefüttert',
	'st_beginner_hint'   => 'Wir empfehlen Back-Anfängern ein wenig Hefe zur Gelingsicherheit. Wir fügen automatisch eine kleine Menge hinzu.',

	// ── Sauerteig-Typen (Backend) ──
	'sourdough_rye'      => 'Roggensauer',
	'sourdough_wheat'    => 'Weizensauer',
	'sourdough_spelt'    => 'Dinkelsauer',
	'sourdough_lievito'  => 'Lievito Madre',

	// ── Mehle ──
	'flour_wheat'   => 'Weizen',
	'flour_rye'     => 'Roggen',
	'flour_spelt'   => 'Dinkel',
	'flour_semola'  => 'Semola (Hartweizen)',
	'flour_emmer'   => 'Emmer',
	'flour_einkorn' => 'Einkorn',
	'flour_kamut'   => 'Kamut/Khorasan',
	'flour_type_vollkorn' => 'Vollkorn',
	'flour_type_hartweizen' => 'Hartweizen',

	// ── Extras ──
	'extra_sunflower' => 'Sonnenblumenkerne',
	'extra_pumpkin'   => 'Kürbiskerne',
	'extra_sesame'    => 'Sesam',
	'extra_linseed'   => 'Leinsamen',
	'extra_oatmeal'   => 'Haferflocken',
	'extra_old_bread' => 'Altbrot',
	'extra_grist'     => 'Schrot (Weizen/Roggen)',

	// ── Step 3: Extras ──
	'step3_title'       => 'Extras',
	'step3_subtitle'    => 'Möchtest du Saaten oder Körner einarbeiten?',
	'step3_counter'     => '%d/7 Extras ausgewählt',
	'step3_counter_bs'  => '%d/7 Extras ausgewählt — Brühstück wird automatisch berechnet',
	'step3_none_hint'   => 'Keine Extras? Einfach weiter zum nächsten Schritt.',
	'step3_warn_quick'  => 'Bei 4h werden Körner trocken eingearbeitet. Schrot nicht verfügbar.',
	'step3_warn_no_bs'  => 'Kein Brühstück möglich – Sauerteig braucht die Zeit.',
	'step3_footer'      => 'Kerne und Saaten können extrem viel Wasser speichern. Damit dein Brot saftig bleibt, berechnen wir automatisch ein Brühstück: Hierbei überbrühst du die Saaten einfach mit der angegebenen Wassermenge, bevor sie in den Teig kommen.',

	// ── Step 4: Backmethode ──
	'step4_title'       => 'Backmethode',
	'step4_subtitle'    => 'Wie möchtest du dein Brot backen?',
	'step4_recommended' => 'Empfohlen',
	'step4_hint'        => 'Alle Methoden funktionieren gut — die Empfehlung basiert auf deinem Erfahrungslevel. Backe so, wie du dich am wohlsten fühlst!',
	'method_steel'      => 'Backstahl',
	'method_steel_desc' => 'Das Optimum für zuhause. Leitet Hitze extrem schnell für den besten Ofentrieb.',
	'method_stone'      => 'Pizzastein',
	'method_stone_desc' => 'Der bewährte Klassiker. Funktioniert ähnlich wie der Stahl und sorgt für eine tolle Kruste.',
	'method_pot'        => 'Topf / Dutch Oven',
	'method_pot_desc'   => 'Simuliert den Profi-Ofen, indem er den Dampf direkt am Brot hält.',
	'method_tray'       => 'Backblech',
	'method_tray_desc'  => 'Nur nutzen, wenn nicht anders möglich. Es funktioniert zwar, aber für ein wirklich gutes Ergebnis empfehlen wir dringend mindestens einen Pizzastein oder einen Topf.',
	'method_pot_short'  => 'Topf',
	'method_stone_short' => 'Pizzastein',
	'method_steel_short' => 'Backstahl',
	'method_tray_short' => 'Backblech',

	// ── Step 5: Rezept ──
	'step5_title'       => 'Rezept',
	'step5_subtitle'    => 'Dein persönliches Brotrezept mit Mengen und Zeitplan.',
	'step5_empty'       => 'Alle Angaben sind erfasst. Erstelle jetzt dein Rezept.',
	'step5_btn_create'  => 'Rezept erstellen',
	'step5_loading'     => 'Rezept wird berechnet…',
	'step5_error_default' => 'Es ist ein Fehler aufgetreten.',

	// ── Rezept-Anzeige ──
	'recipe_ingredients' => 'Zutaten',
	'recipe_timeline'    => 'Zeitplan',
	'recipe_baking'      => 'Backhinweise',
	'recipe_metric_ta'   => 'Teigausbeute',
	'recipe_metric_weight' => 'Gesamtgewicht',
	'recipe_metric_bake' => 'Backzeit',

	// ── Hilfe: Rezept-Seite ──
	'help_ta'              => 'Die TA beschreibt das Wasser-Mehl-Verhältnis. Werte über 170 bedeuten ein sehr saftiges Brot, erfordern aber Übung beim Formen.',
	'help_kochstueck_title' => 'Warum ein Kochstück?',
	'help_kochstueck_text' => 'Dinkel und Urkörner werden schneller trocken. Dein Kochstück bindet Wasser dauerhaft im Mehl. Das Ergebnis: Dein Brot bleibt tagelang saftig und der Teig stabil.',
	'help_knead'           => 'Nutze den Fenstertest: Ziehe den Teig dünn aus. Wenn du fast durchschauen kannst, ohne dass er reißt, ist er fertig.',
	'help_stockgare'       => 'Dehnen & Falten: Greife den Teig am Rand, ziehe ihn sanft nach oben und lege ihn zur Mitte ab. Das stärkt das Teiggerüst.',

	// ── Debug ──
	'debug_title'       => 'Debug: Berechnungsdetails',
	'debug_input'       => 'Eingabeparameter',
	'debug_decisions'   => 'Entscheidungsprotokoll',
	'debug_col_module'  => 'Modul',
	'debug_col_rule'    => 'Regel',
	'debug_col_result'  => 'Ergebnis',

	// ── Zutaten-Gruppen ──
	'group_sourdough'   => 'Sauerteig',
	'group_kochstueck'  => 'Kochstück (Tangzhong)',
	'group_bruehstueck' => 'Brühstück',
	'group_main'        => 'Hauptteig',

	// ── Zutaten-Items ──
	'ing_sourdough_full' => 'Sauerteig (Anstellgut + Mehl + Wasser)',
	'ing_flour'          => 'Mehl',
	'ing_water'          => 'Wasser',
	'ing_water_hot'      => 'Wasser (heiß)',
	'ing_water_extra'    => 'Wasser (extra)',
	'ing_yeast'          => 'Hefe (frisch)',
	'ing_salt'           => 'Salz',
	'ing_dry_suffix'     => ' (trocken einarbeiten)',
	'ing_flour_suffix'   => ' (mit Mehl einarbeiten)',

	// ── Rezeptname ──
	'recipe_fast'        => 'Schnelles',
	'recipe_slow'        => 'Langsam geführtes',
	'recipe_mixed'       => 'Misch',
	'recipe_bread_suffix' => '-brot',
	'recipe_with'        => 'mit',
	'recipe_and'         => 'und',

	// ── Summary-Tags ──
	'tag_flour'  => '%dg Mehl',
	'tag_yeast'  => 'Hefe',
	'tag_sourdough' => 'Sauerteig',
	'tag_hybrid' => 'Hybrid',
	'tag_pot'    => 'Topf',
	'tag_stone'  => 'Stein',
	'tag_steel'  => 'Stahl',
	'tag_tray'   => 'Backblech',

	// ── Warnungen ──
	'warn_sourdough_ready' => 'Dein Sauerteig muss bereits einsatzbereit sein.',
	'warn_rye_rest'        => 'Roggenbrot mind. 24 Stunden vor dem Anschneiden ruhen lassen.',

	// ── Input-Summary (Debug) ──
	'summary_level'      => 'Level',
	'summary_time'       => 'Zeitbudget',
	'summary_flour_amount' => 'Mehlmenge',
	'summary_main_flours' => 'Hauptmehle',
	'summary_side_flours' => 'Nebenmehle',
	'summary_leavening'  => 'Triebmittel',
	'summary_st_type'    => 'ST-Typ',
	'summary_st_ready'   => 'ST bereit',
	'summary_extras'     => 'Extras',
	'summary_method'     => 'Backmethode',
	'summary_fridge'     => 'Kühlschrank',
	'summary_none'       => '(keine)',
	'summary_yes'        => 'Ja',
	'summary_no'         => 'Nein',
	'summary_fridge_direct' => 'Direkt aus Kühlschrank backen',
	'summary_leav_yeast' => 'Nur Hefe',
	'summary_leav_sourdough' => 'Nur Sauerteig',
	'summary_leav_hybrid' => 'Hybrid (Hefe + Sauerteig)',

	// ── Timeline ──
	'tl_sourdough_refresh'     => 'Sauerteig auffrischen',
	'tl_sourdough_refresh_desc' => 'Anstellgut mit Mehl und Wasser im Verhältnis 1:3:3 (bzw. 1:2:1 bei Lievito Madre) mischen. 4 Stunden bei Raumtemperatur reifen lassen.',
	'tl_sourdough_set'         => 'Sauerteig ansetzen',
	'tl_sourdough_set_desc'    => 'Sauerteig mit Mehl und Wasser mischen. Reifen lassen bis er deutlich aufgeht.',
	'tl_sourdough_ready'       => 'Auffrischung + Ansetzen entfallen',
	'tl_bruehstueck'           => 'Brühstück ansetzen',
	'tl_bruehstueck_desc'      => 'Extras mit kochendem Wasser übergießen, quellen und abkühlen lassen (mind. 2 Stunden).',
	'tl_kochstueck'            => 'Kochstück zubereiten (Tangzhong)',
	'tl_kochstueck_desc'       => 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen (mind. 2 Stunden gesamt).',
	'tl_fermentolyse'          => 'Fermentolyse (15 min)',
	'tl_fermentolyse_desc'     => 'Mehl und Wasser mit Triebmittel grob vermischen. Noch kein Salz! 15 Minuten ruhen lassen.',
	'tl_knead_rye'             => 'Teig mischen (Roggen)',
	'tl_knead_rye_desc'        => 'Alle Zutaten in einer Schüssel 3–5 Minuten kräftig zusammenrühren. Roggenteig nicht kneten wie Weizen.',
	'tl_knead'                 => 'Kneten',
	'tl_knead_desc'            => 'Teig auf bemehlte Fläche geben. 2–3 min langsam, dann 2–3 min kräftiger kneten. Salz zugeben, weitere 4–8 min kneten bis glatt und elastisch.',
	'tl_stretch_fold'          => 'Stretch & Fold Runde %d',
	'tl_stretch_fold_desc'     => 'Teig in der Schüssel: eine Seite hochziehen, zur Mitte falten. Schüssel 90° drehen, wiederholen. 4x (Nord, Süd, Ost, West). Abdecken, 15 Min warten.',
	'tl_stockgare_rest'        => 'Restliche Stockgare',
	'tl_stockgare'             => 'Stockgare',
	'tl_stockgare_desc'        => 'Teig abdecken und gehen lassen.',
	'tl_form'                  => 'Formen',
	'tl_form_rye_desc'         => 'Hände und Fläche anfeuchten. Teig vorsichtig zu Laib oder Kugel formen. In bemehltes Gärkörbchen legen.',
	'tl_form_desc'             => 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.',
	'tl_stueckgare'            => 'Stückgare',
	'tl_stueckgare_rye_desc'   => 'Brot im Gärkörbchen gehen lassen. Fertig wenn feine Risse im Mehl auf der Oberfläche sichtbar werden.',
	'tl_stueckgare_desc'       => 'Geformtes Brot abdecken und gehen lassen. Fingertest: Delle soll langsam zurückgehen.',
	'tl_anspring'              => 'Anspringzeit (warm)',
	'tl_anspring_desc'         => 'Teig abgedeckt bei Raumtemperatur anspringen lassen, bevor er in den Kühlschrank kommt.',
	'tl_cold_stock'            => 'Stockgare im Kühlschrank',
	'tl_cold_stock_desc'       => 'Teig abgedeckt %d Stunden im Kühlschrank (4–5°C) gehen lassen.',
	'tl_acclimatize'           => 'Akklimatisieren',
	'tl_acclimatize_desc'      => 'Teig aus dem Kühlschrank nehmen und 30 Minuten bei Raumtemperatur akklimatisieren lassen.',
	'tl_stueckgare_warm'       => 'Stückgare (warm)',
	'tl_stueckgare_warm_desc'  => 'Geformtes Brot abdecken und mind. 2 Stunden bei Raumtemperatur gehen lassen. Fingertest: Delle soll langsam zurückgehen.',
	'tl_form_cold_desc'        => 'Teig zur Mitte falten, umdrehen, zu Kugel formen. In bemehltes Gärkörbchen legen.',
	'tl_cold_proof'            => 'Stückgare im Kühlschrank',
	'tl_cold_proof_desc'       => 'Geformtes Brot abgedeckt mind. %d Stunden im Kühlschrank lassen. Direkt aus dem Kühlschrank backen.',
	'tl_preheat'               => 'Ofen vorheizen',
	'tl_preheat_pot_desc'      => 'Topf mit im Ofen mit aufheizen (30–45 Min).',
	'tl_preheat_tray_desc'     => 'Backblech im Ofen vorheizen (15–20 Min).',
	'tl_preheat_other_desc'    => 'Pizzastein/Backstahl 45–60 Min vorheizen.',
	'tl_bake'                  => 'Backen',
	'tl_bake_desc'             => 'Brot einschießen. Mit Schwaden/Dampf starten, dann Temperatur reduzieren.',
	'tl_cool'                  => 'Auskühlen',
	'tl_cool_rye_desc'         => 'Mind. 24 Stunden liegen lassen, bevor angeschnitten wird!',
	'tl_cool_desc'             => '30–60 Min auf Gitter auskühlen lassen.',

	// ── Backanweisungen ──
	'bake_pot_template'    => 'Topf mit Deckel %d°C: 25 Min. Dann Deckel abnehmen, %d°C: weitere %d Min. (je nach Mehlmenge).',
	'bake_tray_template'   => 'Backblech mit viel Dampf (Schüssel Wasser) %d°C: %d Min. Dann Dampf entfernen, %d°C: weitere %d Min.',
	'bake_other_template'  => 'Mit Schwaden/Dampf %d°C: %d Min. Dann Dampf ablassen, %d°C: weitere %d Min.',
	'bake_rye_note'        => ' Roggenbrot mind. 24 Stunden ruhen lassen vor dem Anschneiden!',

	// ── Serverfehler ──
	'error_server' => 'Serverfehler',
);
