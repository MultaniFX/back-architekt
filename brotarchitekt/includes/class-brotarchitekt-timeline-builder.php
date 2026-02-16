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
		$this->t      = current_time( 'timestamp' );

		$leavening     = $ctx->input['leavening'];
		$from_fridge   = ! empty( $ctx->input['bakeFromFridge'] );
		$time_budget_h = (int) $ctx->input['timeBudget'];

		// ── Phase 1: Vorbereitungen (ST, Bruehstueck, Kochstueck) ──
		$this->add_sourdough_steps();
		$this->add_parallel_steps();

		// ── Phase 2: Teigherstellung ──
		$this->add_fermentolyse();
		$this->add_kneading();
		$sf_minutes = $this->add_stretch_fold();

		// ── Phase 3: Gaerung → Variante je nach Kuehlschrank-Modus ──
		$stockgare_total = $this->compute_stockgare_minutes();

		if ( $ctx->uses_fridge && ! $from_fridge ) {
			$this->build_cold_stock( $stockgare_total, $sf_minutes, $time_budget_h );
		} elseif ( $ctx->uses_fridge && $from_fridge ) {
			$this->build_cold_proof( $stockgare_total, $sf_minutes, $time_budget_h );
		} else {
			$this->build_warm_only( $stockgare_total, $sf_minutes );
		}

		// ── Phase 4: Backen ──
		$this->add_baking();

		// Zeitformatierung
		foreach ( $this->steps as &$s ) {
			$s['time_formatted']     = date_i18n( 'H:i', $s['time'] );
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

		// Auffrischung (Regelwerk C.6: <8h muss bereit sein, 8-12h schnelle Auffrischung)
		if ( ! $st_ready && $time_budget_h >= 8 ) {
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

	/** Bruehstueck + Kochstueck (parallel zum ST, Fix 6+7). */
	private function add_parallel_steps(): void {
		$ctx           = $this->ctx;
		$leavening     = $ctx->input['leavening'];
		$extras        = (array) $ctx->input['extras'];
		$time_budget_h = (int) $ctx->input['timeBudget'];

		$bruehstueck_in_timeline = $ctx->bruehstueck_available;

		// Bruehstueck (parallel zum ST oder eigener Schritt bei Hefe)
		if ( ! empty( $extras ) && $bruehstueck_in_timeline ) {
			$this->step_parallel(
				__( 'Brühstück ansetzen', 'brotarchitekt' ),
				60,
				__( 'Extras mit kochendem Wasser übergießen, 1 Stunde quellen lassen, dann abkühlen.', 'brotarchitekt' )
			);
		}

		// Kochstueck (parallel)
		if ( $ctx->has_kochstueck ) {
			$this->step_parallel(
				__( 'Kochstück zubereiten (Tangzhong)', 'brotarchitekt' ),
				10,
				__( 'Mehl und Wasser im Topf unter Rühren auf 65°C erhitzen bis die Masse puddingartig eindickt. Abkühlen lassen.', 'brotarchitekt' )
			);
		}

		// Jetzt die Vorbereitungszeit vorruecken
		// Finde die laengste parallele Phase und ruecke $t entsprechend vor
		$this->advance_past_parallel();
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
		// Anspringzeit (warm, vor dem Kuehlschrank, Regelwerk F.4)
		$anspring_min = $this->compute_anspringzeit( $time_budget_h );
		$anspring_rest = max( 0, $anspring_min - $sf_minutes );

		if ( $anspring_rest > 0 ) {
			$this->step(
				__( 'Anspringzeit (warm)', 'brotarchitekt' ),
				$anspring_rest,
				__( 'Teig abgedeckt bei Raumtemperatur anspringen lassen, bevor er in den Kühlschrank kommt.', 'brotarchitekt' )
			);
		}

		// Kalte Stockgare
		$cold_hours = max( 8, $time_budget_h - 4 );
		$this->step(
			__( 'Stockgare im Kühlschrank', 'brotarchitekt' ),
			$cold_hours * 60,
			sprintf( __( 'Teig abgedeckt %d Stunden im Kühlschrank (4–5°C) gehen lassen.', 'brotarchitekt' ), $cold_hours )
		);

		// Formen
		$this->add_forming();

		// Warme Stueckgare (90 min, Regelwerk F.5)
		$this->step(
			__( 'Stückgare (warm)', 'brotarchitekt' ),
			90,
			__( 'Geformtes Brot abdecken und bei Raumtemperatur gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' )
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

		if ( $ctx->rye_share >= 75 ) {
			// Regelwerk C.5 / F.4: Roggen-Stockgare nach ST-Anteil
			if ( $ctx->sourdough_pct >= 40 ) {
				return 30;
			}
			if ( $ctx->sourdough_pct >= 25 ) {
				return 60;
			}
			return 120;
		}

		$has_st   = $ctx->sourdough_pct > 0;
		$has_hefe = $ctx->yeast_pct > 0 || $ctx->beginner_yeast_pct > 0;

		if ( $has_st && ! $has_hefe ) {
			// Reiner Sauerteig (F.4 "Mit Sauerteig pur")
			if ( $ctx->sourdough_pct >= 20 ) {
				return 150;
			}
			if ( $ctx->sourdough_pct >= 15 ) {
				return 210;
			}
			if ( $ctx->sourdough_pct >= 10 ) {
				return 270;
			}
			return 330;
		}

		if ( $has_st && $has_hefe ) {
			// Hybrid oder Anfaenger-Hefe (F.4 "Mit ST + minimaler Hefe")
			if ( $ctx->sourdough_pct >= 20 ) {
				return 120;
			}
			if ( $ctx->sourdough_pct >= 15 ) {
				return 180;
			}
			if ( $ctx->sourdough_pct >= 10 ) {
				return 240;
			}
			if ( $ctx->sourdough_pct >= 7.5 ) {
				return 300;
			}
			return 360;
		}

		// Nur Hefe (F.4 "Mit Hefe")
		if ( $ctx->yeast_pct >= 1.0 ) {
			return 105;
		}
		if ( $ctx->yeast_pct >= 0.3 ) {
			return 180;
		}
		return 300;
	}

	/**
	 * Anspringzeit (warm, vor Kuehlschrank).
	 *
	 * Regelwerk F.4: 8h kalt → 120 min, 12h kalt → 90 min, 16h+ kalt → 60 min.
	 */
	private function compute_anspringzeit( int $time_budget_h ): int {
		$fridge_hours = max( 8, $time_budget_h - 4 );

		if ( $fridge_hours >= 16 ) {
			return 60;
		}
		if ( $fridge_hours >= 12 ) {
			return 90;
		}
		return 120;
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

		return $ancient_share >= 60 || $vollkorn_share >= 60;
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
