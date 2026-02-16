<?php
/**
 * Triebmittel-Berechnung: Time-Bucket, Hefe-%, Sauerteig-%, Kuehlschrank-Logik.
 *
 * Regelwerk-Quellen: D.1-D.4, F.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Leaven_Calculator {

	/** Hefe-% Mittelwerte pro Zeitbucket (Regelwerk D.1) */
	private const YEAST_PCT = array(
		'4-6h'   => 1.25,
		'6-8h'   => 0.85,
		'8-12h'  => 0.5,
		'12-24h' => 0.2,
		'24-36h' => 0.075,
		'36-48h' => 0.04,
	);

	/** Sauerteig-% Mittelwerte pro Zeitbucket (Regelwerk D.1) */
	private const ST_PCT = array(
		'4-6h'   => 17,
		'6-8h'   => 20,
		'8-12h'  => 12,
		'12-24h' => 9,
		'24-36h' => 7,
		'36-48h' => 6,
	);

	public function compute( Brotarchitekt_Recipe_Context $ctx ): void {
		$this->compute_time_bucket( $ctx );
		$this->compute_triebmittel( $ctx );
		$this->compute_fridge( $ctx );
	}

	/**
	 * Zeitbudget → Bucket zuordnen.
	 *
	 * Fix 1: Korrekte Grenzen (<=6 → 4-6h, <=8 → 6-8h, etc.)
	 */
	private function compute_time_bucket( Brotarchitekt_Recipe_Context $ctx ): void {
		$h = (int) $ctx->input['timeBudget'];

		if ( $h <= 6 ) {
			$ctx->time_bucket = '4-6h';
		} elseif ( $h <= 8 ) {
			$ctx->time_bucket = '6-8h';
		} elseif ( $h <= 12 ) {
			$ctx->time_bucket = '8-12h';
		} elseif ( $h <= 24 ) {
			$ctx->time_bucket = '12-24h';
		} elseif ( $h <= 36 ) {
			$ctx->time_bucket = '24-36h';
		} else {
			$ctx->time_bucket = '36-48h';
		}
	}

	/**
	 * Hefe-% und Sauerteig-% berechnen.
	 *
	 * Regelwerk D.1: Lookup pro Bucket
	 * Regelwerk D.2: Hybrid → beide halbieren
	 * Regelwerk D.3: Level 1-2 + reiner ST → 0.1% Hefe zur Gelingsicherheit
	 */
	private function compute_triebmittel( Brotarchitekt_Recipe_Context $ctx ): void {
		$leavening = $ctx->input['leavening'];
		$bucket    = $ctx->time_bucket;

		$ctx->sourdough_pct      = 0;
		$ctx->yeast_pct          = 0;
		$ctx->beginner_yeast_pct = 0;

		if ( $leavening === 'yeast' ) {
			$ctx->yeast_pct = self::YEAST_PCT[ $bucket ];
		} elseif ( $leavening === 'sourdough' || $leavening === 'hybrid' ) {
			$ctx->sourdough_pct = self::ST_PCT[ $bucket ];

			if ( $leavening === 'hybrid' ) {
				// D.2: Kombi → beide halbieren
				$ctx->sourdough_pct /= 2;
				$ctx->yeast_pct = self::YEAST_PCT[ $bucket ] / 2;
			}

			// D.3: Anfaenger-Hefe bei reinem Sauerteig
			if ( $ctx->level <= 2 && $leavening === 'sourdough' ) {
				$ctx->beginner_yeast_pct = 0.1;
			}
		}
	}

	/**
	 * Kuehlschrank-Entscheidung.
	 *
	 * Regelwerk F.6: Ab 12h immer, ausser Roggen > 75%.
	 * Fix 17: Roggen > 75% → nie Kuehlschrank.
	 *
	 * Hinweis: rye_share wird erst von Flour_Calculator gesetzt.
	 * Daher wird uses_fridge hier vorlaeufig gesetzt und
	 * in Flour_Calculator nochmal geprueft.
	 */
	private function compute_fridge( Brotarchitekt_Recipe_Context $ctx ): void {
		$h = (int) $ctx->input['timeBudget'];
		$ctx->uses_fridge = $h >= 12;
		// Finale Pruefung (Roggen > 75%) erfolgt in Flour_Calculator nach Roggenanteil-Berechnung
	}
}
