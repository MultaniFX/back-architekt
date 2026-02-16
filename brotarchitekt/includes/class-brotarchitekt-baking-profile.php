<?php
/**
 * Backprofil: Temperaturen, Zeiten und Anleitungstexte.
 *
 * Regelwerk-Quellen: F.7, F.8, F.9
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Baking_Profile {

	/**
	 * Backdauer in Minuten (nach Methode, Roggen-Anteil und Mehlmenge).
	 *
	 * Regelwerk F.8: Separate Profile fuer Weizen und Roggen, skaliert nach Mehlmenge.
	 */
	public function get_duration( Brotarchitekt_Recipe_Context $ctx ): int {
		$is_rye = $ctx->rye_share >= 50;
		$slot   = $this->get_weight_slot( $ctx->total_flour );

		$durations = array(
			'pot'   => $is_rye ? array( 45, 55, 65 ) : array( 40, 50, 60 ),
			'stone' => $is_rye ? array( 45, 55, 65 ) : array( 35, 45, 55 ),
			'steel' => $is_rye ? array( 45, 55, 65 ) : array( 35, 45, 55 ),
		);

		$method = $ctx->input['backMethod'];
		$key = isset( $durations[ $method ] ) ? $method : 'pot';

		return $durations[ $key ][ $slot ];
	}

	/**
	 * Vorheizzeit in Minuten.
	 *
	 * Regelwerk F.7:
	 * - Topf: 30-45 min → 40 min Mitte
	 * - Pizzastein: 45-60 min → 50 min Mitte
	 * - Backstahl: 30-40 min → 35 min Mitte (Fix 11)
	 */
	public function get_preheat( Brotarchitekt_Recipe_Context $ctx ): int {
		$method = $ctx->input['backMethod'];

		if ( $method === 'pot' ) {
			return 40;
		}
		if ( $method === 'steel' ) {
			return 35;
		}
		return 50; // Pizzastein
	}

	/**
	 * Backanleitungs-Text.
	 *
	 * Regelwerk F.8: Roggen-Schwaden nur 5 min (Fix 12), Weizen 10 min.
	 */
	public function get_instructions( Brotarchitekt_Recipe_Context $ctx ): string {
		$method   = $ctx->input['backMethod'];
		$is_rye   = $ctx->rye_share >= 50;
		$temp1    = $is_rye ? 230 : 250;
		$temp2    = $is_rye ? 215 : 230;
		$duration = $this->get_duration( $ctx );

		if ( $method === 'pot' ) {
			$text = sprintf(
				__( 'Topf mit Deckel %d°C: 25 Min. Dann Deckel abnehmen, %d°C: weitere %d Min. (je nach Mehlmenge).', 'brotarchitekt' ),
				$temp1,
				$temp2,
				$duration - 25
			);
		} else {
			// Fix 12: Roggen nur 5 min Schwaden
			$schwaden_min = $is_rye ? 5 : 10;
			$text = sprintf(
				__( 'Mit Schwaden/Dampf %d°C: %d Min. Dann Dampf ablassen, %d°C: weitere %d Min.', 'brotarchitekt' ),
				$temp1,
				$schwaden_min,
				$temp2,
				$duration - $schwaden_min
			);
		}

		// F.9: Roggen-Hinweis
		if ( $ctx->rye_share >= 75 ) {
			$text .= ' ' . __( 'Roggenbrot mind. 24 Stunden ruhen lassen vor dem Anschneiden!', 'brotarchitekt' );
		}

		return $text;
	}

	/** Gewichtsslot: 0 = 500-600g, 1 = 600-800g, 2 = 800-1000g */
	private function get_weight_slot( int $flour_g ): int {
		if ( $flour_g <= 600 ) {
			return 0;
		}
		if ( $flour_g <= 800 ) {
			return 1;
		}
		return 2;
	}
}
