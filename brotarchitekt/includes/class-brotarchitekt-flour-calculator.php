<?php
/**
 * Mehl- und TA-Berechnung: Mehlverteilung, Roggenanteil, Teigausbeute, Wassermenge.
 *
 * Regelwerk-Quellen: A.1-A.5, B.1, E.1-E.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Flour_Calculator {

	/** Getreidesorten die Kochstueck / Fermentolyse ausloesen */
	private const ANCIENT_GRAINS = array( 'spelt', 'emmer', 'einkorn', 'kamut' );

	/** Extras die die TA um +5 erhoehen */
	private const TA_RAISE_EXTRAS = array( 'linseed', 'oatmeal', 'old_bread', 'grist' );

	public function compute( Brotarchitekt_Recipe_Context $ctx ): void {
		$this->compute_flour_breakdown( $ctx );
		$this->compute_rye_share( $ctx );
		$this->finalize_fridge( $ctx );
		$this->compute_ta( $ctx );
	}

	/**
	 * Mehlverteilung berechnen (Regelwerk B.1).
	 *
	 * 1 Hauptmehl = 100%. Mit Nebenmehlen: 80% Haupt / 20% Neben (gleichverteilt).
	 */
	private function compute_flour_breakdown( Brotarchitekt_Recipe_Context $ctx ): void {
		$main = array_filter( (array) $ctx->input['mainFlours'] );
		$side = array_filter( (array) $ctx->input['sideFlours'] );

		if ( empty( $main ) ) {
			$main = array( 'wheat_1050' );
		}

		$main_count = count( $main );
		$side_count = count( $side );
		$ctx->flour_breakdown = array();

		if ( $side_count === 0 ) {
			$pct = 100 / $main_count;
			foreach ( $main as $id ) {
				$ctx->flour_breakdown[ $id ] = $pct;
			}
		} else {
			$main_pct = 80 / $main_count;
			$side_pct = 20 / $side_count;
			foreach ( $main as $id ) {
				$ctx->flour_breakdown[ $id ] = $main_pct;
			}
			foreach ( $side as $id ) {
				$ctx->flour_breakdown[ $id ] = $side_pct;
			}
		}

		// Semola-only?
		$grains = array();
		foreach ( array_keys( $ctx->flour_breakdown ) as $id ) {
			$grains[ $this->get_grain( $id ) ] = true;
		}
		$ctx->is_semola_only = count( $grains ) === 1 && isset( $grains['semola'] );
	}

	/**
	 * Roggenanteil berechnen (fuer Sonderregeln bei >50%, >75%).
	 */
	private function compute_rye_share( Brotarchitekt_Recipe_Context $ctx ): void {
		$ctx->rye_share = 0;
		foreach ( $ctx->flour_breakdown as $id => $pct ) {
			if ( $this->get_grain( $id ) === 'rye' ) {
				$ctx->rye_share += $pct;
			}
		}
	}

	/**
	 * Kuehlschrank-Entscheidung finalisieren.
	 *
	 * Regelwerk F.6: Roggen > 75% → nie Kuehlschrank.
	 * Leaven_Calculator hat uses_fridge vorlaeufig auf true gesetzt (>=12h).
	 */
	private function finalize_fridge( Brotarchitekt_Recipe_Context $ctx ): void {
		if ( $ctx->rye_share >= 75 ) {
			$ctx->uses_fridge = false;
		}
	}

	/**
	 * TA und Wasser berechnen.
	 *
	 * Regelwerk A.1: Basis-TA nach Level
	 * Regelwerk A.2: Max-TA mit Koch-/Bruehstueck (+5 pro Stueck, gedeckelt)
	 * Regelwerk A.3: Vollkorn > 70% → +2 TA
	 * Regelwerk A.4: Semola fix 170-175
	 * Fix 4:  Bruehstueck-Verfuegbarkeit pruefen (nicht bei <=6h, nicht bei <=8h + ST)
	 * Fix 5:  6-8h + Hefe → Bruehstueck erlaubt
	 * Fix 14: Kochstueck nur bei Hauptmehl
	 */
	private function compute_ta( Brotarchitekt_Recipe_Context $ctx ): void {
		$ta_base = $ctx->level_info['ta_base'];
		$ta_max  = $ctx->level_info['ta_max'];

		// A.4: Semola-Sonderregel
		if ( $ctx->is_semola_only ) {
			$ctx->ta = 172;
			$ctx->has_kochstueck = false;
			$ctx->has_ta_raise_bruehstueck = false;
			$ctx->bruehstueck_available = true;
			$ctx->water_total = $ctx->total_flour * ( ( $ctx->ta - 100 ) / 100 );
			return;
		}

		// A.3: Vollkorn-Zuschlag
		$vk_share = 0;
		foreach ( $ctx->flour_breakdown as $id => $pct ) {
			if ( strpos( $id, '_Vollkorn' ) !== false ) {
				$vk_share += $pct;
			}
		}
		if ( $vk_share > 70 ) {
			$ta_base += 2;
			$ta_max  += 2;
		}

		// E.1: Kochstueck nur bei Dinkel/Urkorn als HAUPTMEHL (Fix 14)
		$ctx->has_kochstueck = false;
		$main_flour_ids = array_filter( (array) $ctx->input['mainFlours'] );
		foreach ( $main_flour_ids as $id ) {
			if ( in_array( $this->get_grain( $id ), self::ANCIENT_GRAINS, true ) ) {
				$ctx->has_kochstueck = true;
				break;
			}
		}

		// TA-erhoehende Extras erkennen
		$ctx->has_ta_raise_bruehstueck = false;
		$extras = (array) $ctx->input['extras'];
		foreach ( $extras as $e ) {
			if ( in_array( $e, self::TA_RAISE_EXTRAS, true ) ) {
				$ctx->has_ta_raise_bruehstueck = true;
				break;
			}
		}

		// Bruehstueck-Verfuegbarkeit (EINE Wahrheitsquelle, Fix 4 + Fix 5)
		$h = (int) $ctx->input['timeBudget'];
		$leavening = $ctx->input['leavening'];
		$ctx->bruehstueck_available = true;

		if ( $h <= 6 ) {
			// 4-6h: kein Bruehstueck (Regelwerk E.3)
			$ctx->bruehstueck_available = false;
		} elseif ( $h <= 8 && $leavening !== 'yeast' ) {
			// 6-8h + Sauerteig/Hybrid: kein Bruehstueck (ST braucht die Zeit)
			$ctx->bruehstueck_available = false;
		}
		// 6-8h + Hefe: Bruehstueck erlaubt (Fix 5)
		// 8h+: immer erlaubt

		// TA-Erhoehung nur wenn Bruehstueck verfuegbar (Fix 4)
		if ( ! $ctx->bruehstueck_available ) {
			$ctx->has_ta_raise_bruehstueck = false;
		}

		// E.1: Level 1-3: Kochstueck entfaellt wenn TA-erhoehender Bruehstueck vorhanden
		if ( $ctx->level <= 3 && $ctx->has_ta_raise_bruehstueck ) {
			$ctx->has_kochstueck = false;
		}

		// TA berechnen: Basis + Modifier, gedeckelt auf Max
		$ctx->ta = $ta_base;
		if ( $ctx->has_kochstueck ) {
			$ctx->ta += 5;
		}
		if ( $ctx->has_ta_raise_bruehstueck ) {
			$ctx->ta += 5;
		}
		$ctx->ta = min( $ctx->ta, $ta_max );

		// Wassermenge aus TA: Wasser = Mehl * (TA - 100) / 100
		$ctx->water_total = $ctx->total_flour * ( ( $ctx->ta - 100 ) / 100 );
	}

	/** Getreide-Kuerzel aus Mehl-ID extrahieren (wheat_1050 → wheat). */
	private function get_grain( string $flour_id ): string {
		return explode( '_', $flour_id, 2 )[0];
	}
}
