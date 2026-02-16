<?php
/**
 * Zeitplan-Generator: Schritte mit Uhrzeiten, Dauern und Anleitungstexten.
 *
 * Drei Kuehlschrank-Varianten:
 * 1. Warm only   (<12h oder Roggen >75%)
 * 2. Cold stock   (12h+, "Normal": Anspringzeit → Kuehlschrank-Stockgare → Formen → warme Stueckgare)
 * 3. Cold proof   (12h+, "Direkt": warme Stockgare → Formen → Kuehlschrank-Stueckgare → direkt backen)
 *
 * Regelwerk-Quellen: F.1-F.9, C.3-C.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Timeline_Builder {

	private Brotarchitekt_Recipe_Context $ctx;
	private Brotarchitekt_Baking_Profile $baking;

	/** @var list<array{time: int, label: string, duration: int, desc: string}> */
	private array $steps = [];

	/** Aktuelle Uhrzeit (Unix-Timestamp, wird fortgeschrieben) */
	private int $t;

	/**
	 * @return list<array{time: int, label: string, duration: int, desc: string, time_formatted: string, duration_formatted: string}>
	 */
	public function build( Brotarchitekt_Recipe_Context $ctx ): array {
		$this->ctx    = $ctx;
		$this->baking = new Brotarchitekt_Baking_Profile();
		$this->steps  = array();
		$this->t      = 0; // Relative Zeit: Start bei 0:00

		$leavening     = $ctx->input['leavening'];
		$from_fridge   = ! empty( $ctx->input['bakeFromFridge'] );
		$time_budget_h = (int) $ctx->input['timeBudget'];

		$ctx->log( 'Timeline', 'Zeitbudget', $time_budget_h . 'h gesamt (inkl. ST-Vorbereitung)' );

		// ── Phase 1: Vorbereitungen (ST, Bruehstueck, Kochstueck) ──
		$this->add_sourdough_steps();
		$this->add_parallel_steps();

		// ── Phase 2: Teigherstellung ──
		$this->add_fermentolyse();
		$this->add_kneading();
		$sf_minutes = $this->add_stretch_fold();

		// ── Phase 3: Gaerung → Variante je nach Kuehlschrank-Modus ──
		$stockgare_total = $this->compute_stockgare_minutes();

		$ctx->log( 'Timeline', 'F.3: Stretch & Fold', 'S&F ' . $sf_minutes . ' min verbraucht' );
		$ctx->log( 'Timeline', 'F.4: Stockgare gesamt', $stockgare_total . ' min (davon S&F ' . $sf_minutes . ' min, Rest ' . max( 0, $stockgare_total - $sf_minutes ) . ' min)' );

		if ( $ctx->uses_fridge && ! $from_fridge ) {
			$ctx->log( 'Timeline', 'F.6: Gaervariante', 'Kalte Stockgare (Normal) — uses_fridge=true, bakeFromFridge=false' );
			$this->build_cold_stock( $stockgare_total, $sf_minutes, $time_budget_h );
		} elseif ( $ctx->uses_fridge && $from_fridge ) {
			$ctx->log( 'Timeline', 'F.6: Gaervariante', 'Kalte Stueckgare (Direkt) — uses_fridge=true, bakeFromFridge=true' );
			$this->build_cold_proof( $stockgare_total, $sf_minutes, $time_budget_h );
		} else {
			$ctx->log( 'Timeline', 'F.6: Gaervariante', 'Warm only — uses_fridge=false' );
			$this->build_warm_only( $stockgare_total, $sf_minutes );
		}

		// ── Phase 4: Backen ──
		$this->add_baking();

		// Zeitformatierung
		foreach ( $this->steps as &$s ) {
			$elapsed_h               = floor( $s['time'] / 3600 );
			$elapsed_m               = floor( ( $s['time'] % 3600 ) / 60 );
			$s['time_formatted']     = sprintf( '%d:%02d', $elapsed_h, $elapsed_m );
			$s['duration_formatted'] = $s['duration'] >= 60
				? ( floor( $s['duration'] / 60 ) . ' h ' . ( $s['duration'] % 60 ? $s['duration'] % 60 . ' min' : '' ) )
				: ( $s['duration'] . ' min' );
		}
		unset( $s );

		return $this->steps;
	}

	// ══════════════════════════════════════════════════
	// Phase 1: Vorbereitungen
	// ══════════════════════════════════════════════════

	/** ST-Auffrischung (wenn noetig) + ST ansetzen. */
	private function add_sourdough_steps(): void {
		$ctx           = $this->ctx;
		$leavening     = $ctx->input['leavening'];
		$st_ready      = $ctx->input['sourdoughReady'] === 'yes';
		$time_budget_h = (int) $ctx->input['timeBudget'];

		if ( $leavening === 'yeast' ) {
			return;
		}

		// C.6: ST bereits fertig → Auffrischung UND Ansetzen entfallen
		if ( $st_ready ) {
			$ctx->log( 'Timeline', 'C.6: ST bereit', 'Auffrischung + Ansetzen entfallen' );
			return;
		}

		// Auffrischung (Regelwerk C.6: <8h muss bereit sein, 8-12h schnelle Auffrischung)
		if ( $time_budget_h >= 8 ) {
			$this->step(
				__( 'Sauerteig auffrischen', 'brotarchitekt' ),
				240,
				__( 'Anstellgut mit Mehl und Wasser im Verhältnis 1:3:3 (bzw. 1:2:1 bei Lievito Madre) mischen. 4 Stunden bei Raumtemperatur reifen lassen.', 'brotarchitekt' )
			);
		}

		// ST ansetzen (Dauer je nach Gesamtzeit, Regelwerk C.4)
		if ( $time_budget_h >= 24 ) {
			$st_duration = 720; // 12h
		} elseif ( $time_budget_h >= 12 ) {
			$st_duration = 480; // 8h
		} elseif ( $time_budget_h >= 8 ) {
			$st_duration = 240; // 4h
		} else {
			$st_duration = 360; // 6h default
		}

		$this->step(
			__( 'Sauerteig ansetzen', 'brotarchitekt' ),
			$st_duration,
			__( 'Sauerteig mit Mehl und Wasser mischen. Reifen lassen bis er deutlich aufgeht.', 'brotarchitekt' ),
			false // Zeit NICHT vorruecken — parallele Steps folgen
		);
	}

	/** Bruehstueck + Kochstueck (parallel zum ST oder eigener Schritt). */
	private function add_parallel_steps(): void {
		$ctx       = $this->ctx;
		$leavening = $ctx->input['leavening'];
		$extras    = (array) $ctx->input['extras'];

		// ST-Schritt existiert nur wenn Sauerteig UND nicht ready
		$has_st_step = $leavening !== 'yeast'
		               && $ctx->input['sourdoughReady'] !== 'yes';

		$has_vorteig = false;

		// Bruehstueck
		if ( ! empty( $extras ) && $ctx->bruehstueck_available ) {
			$has_vorteig = true;
			if ( $has_st_step ) {
				$this->step_parallel(
					__( 'Brühstück ansetzen', 'brotarchitekt' ),
					120,
					__( 'Extras mit kochendem Wasser übergießen, quellen und abkühlen lassen (mind. 2 Stunden).', 'brotarchitekt' )
				);
			} else {
				$this->step(
					__( 'Brühstück ansetzen', 'brotarchitekt' ),
					120,
					__( 'Extras mit kochendem Wasser übergießen, quellen und abkühlen lassen (mind. 2 Stunden).', 'brotarchitekt' )
				);
			}
		}

		// Kochstueck
		if ( $ctx->has_kochstueck ) {
			if ( $has_st_step ) {
				$this->step_parallel(
					__( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
					120,
					__( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen (mind. 2 Stunden gesamt).', 'brotarchitekt' )
				);
			} elseif ( ! $has_vorteig ) {
				// Nur Kochstueck, kein Bruehstueck → eigener Schritt
				$this->step(
					__( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
					120,
					__( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen (mind. 2 Stunden gesamt).', 'brotarchitekt' )
				);
			} else {
				// Beides vorhanden, Bruehstueck-Schritt hat schon 120 min, Kochstueck parallel dazu
				$this->step_parallel(
					__( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
					120,
					__( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen (mind. 2 Stunden gesamt).', 'brotarchitekt' )
				);
			}
			$has_vorteig = true;
		}

		// Vorbereitungszeit vorruecken
		if ( $has_st_step ) {
			$this->advance_past_parallel();
		}
	}

	/** Fermentolyse (Fix 3: nur bei Dinkel/Urkorn >= 60% oder Vollkorn >= 60%). */
	private function add_fermentolyse(): void {
		if ( ! $this->needs_fermentolyse() ) {
			return;
		}
		$this->step(
			__( 'Fermentolyse (15 min)', 'brotarchitekt' ),
			15,
			__( 'Mehl und Wasser mit Triebmittel grob vermischen. Noch kein Salz! 15 Minuten ruhen lassen.', 'brotarchitekt' )
		);
	}

	/** Kneten/Mischen (Regelwerk F.2). */
	private function add_kneading(): void {
		$ctx = $this->ctx;

		if ( $ctx->rye_share >= 75 ) {
			$this->step(
				__( 'Teig mischen (Roggen)', 'brotarchitekt' ),
				4,
				__( 'Alle Zutaten in einer Schüssel 3–5 Minuten kräftig zusammenrühren. Roggenteig nicht kneten wie Weizen.', 'brotarchitekt' )
			);
		} else {
			$this->step(
				__( 'Kneten', 'brotarchitekt' ),
				12,
				__( 'Teig auf bemehlte Fläche geben. 2–3 min langsam, dann 2–3 min kräftiger kneten. Salz zugeben, weitere 4–8 min kneten bis glatt und elastisch.', 'brotarchitekt' )
			);
		}
	}

	/**
	 * Stretch & Fold (Fix 8: innerhalb der Stockgare).
	 *
	 * Regelwerk F.3: 3 Runden alle 15 min, nicht bei Roggen >= 75%.
	 *
	 * @return int Verbrauchte Minuten (45 oder 0)
	 */
	private function add_stretch_fold(): int {
		if ( $this->ctx->rye_share >= 75 ) {
			return 0;
		}

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->step(
				sprintf( __( 'Stretch & Fold Runde %d', 'brotarchitekt' ), $i ),
				15,
				__( 'Teig in der Schüssel: eine Seite hochziehen, zur Mitte falten. Schüssel 90° drehen, wiederholen. 4x (Nord, Süd, Ost, West). Abdecken, 15 Min warten.', 'brotarchitekt' )
			);
		}

		return 45;
	}

	// ══════════════════════════════════════════════════
	// Phase 3: Gaerungsvarianten
	// ══════════════════════════════════════════════════

	/**
	 * Variante 1: Kein Kuehlschrank (<12h oder Roggen >75%).
	 *
	 * Ablauf: [S&F bereits gelaufen] → Restliche Stockgare → Formen → Warme Stueckgare
	 */
	private function build_warm_only( int $stockgare_total, int $sf_minutes ): void {
		$ctx = $this->ctx;

		// Restliche Stockgare (S&F-Zeit schon abgelaufen, Fix 8)
		$rest = max( 0, $stockgare_total - $sf_minutes );
		if ( $rest > 0 ) {
			$label = $sf_minutes > 0
				? __( 'Restliche Stockgare', 'brotarchitekt' )
				: __( 'Stockgare', 'brotarchitekt' );
			$this->step( $label, $rest, __( 'Teig abdecken und gehen lassen.', 'brotarchitekt' ) );
		}

		// Formen
		$this->add_forming();

		// Warme Stueckgare
		$stueck_min = $ctx->rye_share >= 75 ? 150 : 90;
		$desc = $ctx->rye_share >= 75
			? __( 'Brot im Gärkörbchen gehen lassen. Fertig wenn feine Risse im Mehl auf der Oberfläche sichtbar werden.', 'brotarchitekt' )
			: __( 'Geformtes Brot abdecken und gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' );

		$this->step( __( 'Stückgare', 'brotarchitekt' ), $stueck_min, $desc );
	}

	/**
	 * Variante 2: Kalte Stockgare, "Normal" (Fix 10).
	 *
	 * Ablauf: [S&F] → Anspringzeit (warm) → Kalte Stockgare (Kuehlschrank) → Formen → Warme Stueckgare
	 *
	 * Regelwerk F.4 (Kalte Stockgare) + F.6 (Kuehlschrank-Logik)
	 */
	private function build_cold_stock( int $stockgare_total, int $sf_minutes, int $time_budget_h ): void {
		$ctx = $this->ctx;

		// ── Rückwärtsrechnung: verfügbare Zeit für kalte Stockgare ──
		$elapsed_min    = (int) ( $this->t / 60 );
		$formen_min     = 10;
		$akklim_min     = 30;  // Akklimatisierung nach Kühlschrank
		$stueckgare_min = 120; // mind. 2h Stückgare nach Kälte
		$preheat_min    = $this->baking->get_preheat( $ctx );
		$bake_min       = $this->baking->get_duration( $ctx );

		// Anspringzeit hängt von Kaltdauer ab → erst grob schätzen, dann iterieren
		$budget_min     = $time_budget_h * 60;
		$fixed_after    = $formen_min + $akklim_min + $stueckgare_min + $preheat_min + $bake_min;

		// Erst Anspringzeit ohne S&F-Abzug für Kaltzeit-Schätzung
		$cold_estimate  = $budget_min - $elapsed_min - $fixed_after - 120; // 120 min Anspring-Schätzung
		$cold_hours_raw = max( 8, (int) floor( $cold_estimate / 60 ) );

		$anspring_min   = $this->compute_anspringzeit( $time_budget_h, $cold_hours_raw );
		$anspring_rest  = max( 0, $anspring_min - $sf_minutes );

		// Verfügbar für kalte Stockgare = Budget − bisherige Zeit − Anspringen − feste Schritte danach
		$available_for_cold = $budget_min - $elapsed_min - $anspring_rest - $fixed_after;
		$cold_hours         = max( 8, (int) floor( $available_for_cold / 60 ) );

		$ctx->log( 'Timeline', 'F.6: Kalte Stockgare (Rückwärtsrechnung)',
			'Budget ' . $budget_min . ' min − bisher ' . $elapsed_min . ' min − Anspringen ' . $anspring_rest
			. ' min − Formen ' . $formen_min . ' min − Akklim. ' . $akklim_min . ' min − Stückgare ' . $stueckgare_min
			. ' min − Vorheizen ' . $preheat_min . ' min − Backen ' . $bake_min . ' min = '
			. $available_for_cold . ' min verfügbar → ' . $cold_hours . 'h kalte Stockgare'
		);

		// Anspringzeit (warm, vor dem Kühlschrank)
		if ( $anspring_rest > 0 ) {
			$this->step(
				__( 'Anspringzeit (warm)', 'brotarchitekt' ),
				$anspring_rest,
				__( 'Teig abgedeckt bei Raumtemperatur anspringen lassen, bevor er in den Kühlschrank kommt.', 'brotarchitekt' )
			);
		}

		// Kalte Stockgare
		$this->step(
			__( 'Stockgare im Kühlschrank', 'brotarchitekt' ),
			$cold_hours * 60,
			sprintf( __( 'Teig abgedeckt %d Stunden im Kühlschrank (4–5°C) gehen lassen.', 'brotarchitekt' ), $cold_hours )
		);

		// Akklimatisierung (nach Kühlschrank, vor Formen)
		$this->step(
			__( 'Akklimatisieren', 'brotarchitekt' ),
			$akklim_min,
			__( 'Teig aus dem Kühlschrank nehmen und 30 Minuten bei Raumtemperatur akklimatisieren lassen.', 'brotarchitekt' )
		);

		// Formen
		$this->add_forming();

		// Warme Stückgare (mind. 2h nach Kälte, Regelwerk F.5)
		$this->step(
			__( 'Stückgare (warm)', 'brotarchitekt' ),
			$stueckgare_min,
			__( 'Geformtes Brot abdecken und mind. 2 Stunden bei Raumtemperatur gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' )
		);
	}

	/**
	 * Variante 3: Direkt aus Kuehlschrank, "Direkt" (Fix 10).
	 *
	 * Ablauf: [S&F] → Warme Stockgare → Formen → Kalte Stueckgare (mind. 8h) → Direkt backen
	 *
	 * Regelwerk F.5 (Kalte Stueckgare) + F.6
	 */
	private function build_cold_proof( int $stockgare_total, int $sf_minutes, int $time_budget_h ): void {
		// Warme Stockgare (normal berechnet, Fix 8: S&F-Zeit abziehen)
		$rest = max( 0, $stockgare_total - $sf_minutes );
		if ( $rest > 0 ) {
			$this->step(
				__( 'Restliche Stockgare', 'brotarchitekt' ),
				$rest,
				__( 'Teig abdecken und gehen lassen.', 'brotarchitekt' )
			);
		}

		// Formen
		$this->step(
			__( 'Formen', 'brotarchitekt' ),
			10,
			__( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' )
		);

		// Kalte Stueckgare
		$cold_hours = max( 8, $time_budget_h - 6 );
		$this->step(
			__( 'Stückgare im Kühlschrank', 'brotarchitekt' ),
			$cold_hours * 60,
			sprintf( __( 'Geformtes Brot abgedeckt mind. %d Stunden im Kühlschrank lassen. Direkt aus dem Kühlschrank backen.', 'brotarchitekt' ), $cold_hours )
		);
	}

	// ══════════════════════════════════════════════════
	// Phase 4: Backen
	// ══════════════════════════════════════════════════

	private function add_baking(): void {
		$ctx = $this->ctx;

		// Ofen vorheizen
		$preheat = $this->baking->get_preheat( $ctx );
		$preheat_desc = $ctx->input['backMethod'] === 'pot'
			? __( 'Topf mit im Ofen mit aufheizen (30–45 Min).', 'brotarchitekt' )
			: __( 'Pizzastein/Backstahl 45–60 Min vorheizen.', 'brotarchitekt' );
		$this->step( __( 'Ofen vorheizen', 'brotarchitekt' ), $preheat, $preheat_desc );

		// Backen
		$bake_min = $this->baking->get_duration( $ctx );
		$this->step(
			__( 'Backen', 'brotarchitekt' ),
			$bake_min,
			__( 'Brot einschießen. Mit Schwaden/Dampf starten, dann Temperatur reduzieren.', 'brotarchitekt' )
		);

		// Auskuehlen
		$this->steps[] = array(
			'time'     => $this->t,
			'label'    => __( 'Auskühlen', 'brotarchitekt' ),
			'duration' => 45,
			'desc'     => $ctx->rye_share >= 75
				? __( 'Mind. 24 Stunden liegen lassen, bevor angeschnitten wird!', 'brotarchitekt' )
				: __( '30–60 Min auf Gitter auskühlen lassen.', 'brotarchitekt' ),
		);
	}

	// ══════════════════════════════════════════════════
	// Berechnungs-Helpers
	// ══════════════════════════════════════════════════

	/**
	 * Stockgare-Dauer (GESAMT inkl. S&F-Anteil) nach Triebmittel.
	 *
	 * Regelwerk F.4: Tabellen nach Hefe-%, ST-% und Roggen-Anteil.
	 */
	private function compute_stockgare_minutes(): int {
		$ctx = $this->ctx;
		$result = 0;
		$rule   = '';

		if ( $ctx->rye_share >= 75 ) {
			// Regelwerk C.5 / F.4: Roggen-Stockgare nach ST-Anteil
			if ( $ctx->sourdough_pct >= 40 ) {
				$result = 30;
				$rule = 'Roggen ≥75%, ST ≥40% → 30 min';
			} elseif ( $ctx->sourdough_pct >= 25 ) {
				$result = 60;
				$rule = 'Roggen ≥75%, ST ≥25% → 60 min';
			} else {
				$result = 120;
				$rule = 'Roggen ≥75%, ST <25% → 120 min';
			}
		} else {
			$has_st   = $ctx->sourdough_pct > 0;
			$has_hefe = $ctx->yeast_pct > 0 || $ctx->beginner_yeast_pct > 0;

			if ( $has_st && ! $has_hefe ) {
				// Reiner Sauerteig (F.4 "Mit Sauerteig pur")
				if ( $ctx->sourdough_pct >= 20 ) {
					$result = 150;
					$rule = 'ST pur ≥20% → 150 min';
				} elseif ( $ctx->sourdough_pct >= 15 ) {
					$result = 210;
					$rule = 'ST pur ≥15% → 210 min';
				} elseif ( $ctx->sourdough_pct >= 10 ) {
					$result = 270;
					$rule = 'ST pur ≥10% → 270 min';
				} else {
					$result = 330;
					$rule = 'ST pur <10% → 330 min';
				}
			} elseif ( $has_st && $has_hefe ) {
				// Hybrid oder Anfaenger-Hefe (F.4 "Mit ST + minimaler Hefe")
				if ( $ctx->sourdough_pct >= 20 ) {
					$result = 120;
					$rule = 'ST+Hefe ≥20% → 120 min';
				} elseif ( $ctx->sourdough_pct >= 15 ) {
					$result = 180;
					$rule = 'ST+Hefe ≥15% → 180 min';
				} elseif ( $ctx->sourdough_pct >= 10 ) {
					$result = 240;
					$rule = 'ST+Hefe ≥10% → 240 min';
				} elseif ( $ctx->sourdough_pct >= 7.5 ) {
					$result = 300;
					$rule = 'ST+Hefe ≥7.5% → 300 min';
				} else {
					$result = 360;
					$rule = 'ST+Hefe <7.5% → 360 min';
				}
			} else {
				// Nur Hefe (F.4 "Mit Hefe")
				if ( $ctx->yeast_pct >= 1.0 ) {
					$result = 105;
					$rule = 'Hefe ≥1% → 105 min';
				} elseif ( $ctx->yeast_pct >= 0.3 ) {
					$result = 180;
					$rule = 'Hefe ≥0.3% → 180 min';
				} else {
					$result = 300;
					$rule = 'Hefe <0.3% → 300 min';
				}
			}
		}

		$ctx->log( 'Timeline', 'F.4: Stockgare-Regel', $rule . ' (ST ' . $ctx->sourdough_pct . '%, Hefe ' . $ctx->yeast_pct . '%, Roggen ' . $ctx->rye_share . '%)' );
		return $result;
	}

	/**
	 * Anspringzeit (warm, vor Kuehlschrank).
	 *
	 * Regelwerk F.4: 8h kalt → 120 min, 12h kalt → 90 min, 16h+ kalt → 60 min.
	 */
	private function compute_anspringzeit( int $time_budget_h, int $cold_hours ): int {
		if ( $cold_hours >= 16 ) {
			$result = 60;
			$rule = '≥16h kalt → 60 min';
		} elseif ( $cold_hours >= 12 ) {
			$result = 90;
			$rule = '≥12h kalt → 90 min';
		} else {
			$result = 120;
			$rule = '<12h kalt → 120 min';
		}

		$this->ctx->log( 'Timeline', 'F.4: Anspringzeit', $rule . ' (Kühlschrank ' . $cold_hours . 'h, Budget ' . $time_budget_h . 'h)' );
		return $result;
	}

	/**
	 * Fermentolyse noetig? (Fix 3)
	 *
	 * Regelwerk F.1: Pflicht bei Dinkel/Urkorn >= 60% oder Vollkorn >= 60%.
	 */
	private function needs_fermentolyse(): bool {
		$ancient_share  = 0;
		$vollkorn_share = 0;

		foreach ( $this->ctx->flour_breakdown as $id => $pct ) {
			$grain = explode( '_', $id, 2 )[0];
			if ( in_array( $grain, array( 'spelt', 'emmer', 'einkorn', 'kamut' ), true ) ) {
				$ancient_share += $pct;
			}
			if ( strpos( $id, '_Vollkorn' ) !== false ) {
				$vollkorn_share += $pct;
			}
		}

		$needs = $ancient_share >= 60 || $vollkorn_share >= 60;
		$this->ctx->log( 'Timeline', 'F.1: Fermentolyse', 'Dinkel/Urkorn ' . $ancient_share . '%, Vollkorn ' . $vollkorn_share . '% → ' . ( $needs ? 'Ja' : 'Nein' ) );
		return $needs;
	}

	// ══════════════════════════════════════════════════
	// Step-Helpers
	// ══════════════════════════════════════════════════

	/** Schritt hinzufuegen und Zeit vorruecken. */
	private function step( string $label, int $duration_min, string $desc, bool $advance = true ): void {
		$this->steps[] = array(
			'time'     => $this->t,
			'label'    => $label,
			'duration' => $duration_min,
			'desc'     => $desc,
		);
		if ( $advance ) {
			$this->t += $duration_min * 60;
		}
	}

	/** Paralleler Schritt (gleicher Zeitpunkt, keine Zeitvorrueckung). */
	private function step_parallel( string $label, int $duration_min, string $desc ): void {
		$this->step( $label, $duration_min, $desc, false );
	}

	/**
	 * Zeit vorruecken bis nach dem laengsten parallelen Schritt.
	 *
	 * Sucht den letzten Schritt ohne advance und findet die laengste Dauer seit dessen Startzeit.
	 */
	private function advance_past_parallel(): void {
		if ( empty( $this->steps ) ) {
			return;
		}

		// Finde die frueheste parallele Startzeit und die laengste Gesamtdauer
		$max_end = $this->t;
		foreach ( $this->steps as $s ) {
			$end = $s['time'] + $s['duration'] * 60;
			if ( $end > $max_end ) {
				$max_end = $end;
			}
		}

		$this->t = $max_end;
	}

	/** Formen-Schritt (DRY fuer alle Varianten). */
	private function add_forming(): void {
		$desc = $this->ctx->rye_share >= 75
			? __( 'Hände und Fläche anfeuchten. Teig vorsichtig zu Laib oder Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' )
			: __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.', 'brotarchitekt' );

		$this->step( __( 'Formen', 'brotarchitekt' ), 10, $desc );
	}
}
