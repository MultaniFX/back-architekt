<?php
/**
 * Zutatenliste: Sauerteig, Kochstueck, Bruehstueck, Hauptteig.
 *
 * Regelwerk-Quellen: C.1-C.2, D.5, E.1-E.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Ingredients_Builder {

	private Brotarchitekt_Recipe_Context $ctx;

	/**
	 * @return array<string, array{label: string, items: list<array{name: string, amount: int|float, unit: string, percent?: float}>}>
	 */
	public function build( Brotarchitekt_Recipe_Context $ctx ): array {
		$this->ctx = $ctx;

		// Mehlmengen pro Sorte (Arbeitskopie, wird durch Abzuege veraendert)
		$flour_amounts = array();
		foreach ( $ctx->flour_breakdown as $id => $pct ) {
			$g = round( $ctx->total_flour * $pct / 100, 0 );
			if ( $g > 0 ) {
				$flour_amounts[ $id ] = $g;
			}
		}

		$extras_weight = $this->get_extras_weight( $ctx );
		$salt_base     = $ctx->total_flour + $extras_weight;
		$salt          = round( $salt_base * 0.02, 0 );
		$ctx->log( 'Ingredients', 'Salz', 'Salz: 2% von (' . $ctx->total_flour . 'g Mehl + ' . $extras_weight . 'g Extras = ' . $salt_base . 'g) = ' . $salt . 'g' );
		$water_main = $ctx->water_total;
		$groups     = array();

		// ── Sauerteig ──
		$sourdough_flour = 0;
		$sourdough_water = 0;
		$st_flour_grain  = null;

		if ( $ctx->sourdough_pct > 0 ) {
			$st_types       = Brotarchitekt_Data::get_sourdough_types();
			$st             = $st_types[ $ctx->input['sourdoughType'] ] ?? $st_types['rye'];
			$st_flour_grain = $st['flour_grain'];

			// Fix 2: ST-% = Mehlanteil im ST als % vom Gesamtmehl
			$sourdough_flour = round( $ctx->total_flour * ( $ctx->sourdough_pct / 100 ), 0 );
			$sourdough_water = round( $sourdough_flour * ( ( $st['ta'] - 100 ) / 100 ), 0 );
			$water_main     -= $sourdough_water;

			$ctx->log( 'Ingredients', 'C.1: Sauerteig', 'ST-Mehl ' . $sourdough_flour . 'g (' . $ctx->sourdough_pct . '% von ' . $ctx->total_flour . 'g), ST-Wasser ' . $sourdough_water . 'g (TA ' . $st['ta'] . '), Hauptwasser -' . $sourdough_water . 'g' );

			$st_total_g = $sourdough_flour + $sourdough_water;
			$groups['sourdough'] = array(
				'label' => __( 'Sauerteig', 'brotarchitekt' ),
				'items' => array(
					array(
						'name'    => __( 'Sauerteig (Anstellgut + Mehl + Wasser)', 'brotarchitekt' ),
						'amount'  => $st_total_g,
						'unit'    => 'g',
						'percent' => $this->pct( $st_total_g ),
					),
				),
			);
		}

		// ── Kochstueck (Tangzhong, Regelwerk E.1) ──
		$kochstueck_mehl  = 0;
		$kochstueck_water = 0;

		if ( $ctx->has_kochstueck ) {
			$kochstueck_mehl  = round( $ctx->total_flour * 0.04, 0 );
			$kochstueck_water = $kochstueck_mehl * 5;
			$water_main      -= $kochstueck_water;
			$this->subtract_flour_from_ancient( $flour_amounts, $kochstueck_mehl );

			$ctx->log( 'Ingredients', 'E.1: Kochstueck', 'Mehl ' . $kochstueck_mehl . 'g (4%), Wasser ' . $kochstueck_water . 'g (1:5), Hauptwasser -' . $kochstueck_water . 'g' );

			$groups['kochstueck'] = array(
				'label' => __( 'Kochstück (Tangzhong)', 'brotarchitekt' ),
				'items' => array(
					array( 'name' => __( 'Mehl', 'brotarchitekt' ), 'amount' => $kochstueck_mehl, 'unit' => 'g', 'percent' => $this->pct( $kochstueck_mehl ) ),
					array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $kochstueck_water, 'unit' => 'g', 'percent' => $this->pct( $kochstueck_water ) ),
				),
			);
		}

		// ST-Mehl vom passenden Getreide abziehen
		if ( $sourdough_flour > 0 && $st_flour_grain !== null ) {
			$subtracted = $this->subtract_flour_from_grain( $flour_amounts, $st_flour_grain, $sourdough_flour );
			if ( $subtracted < $sourdough_flour && ! empty( $flour_amounts ) ) {
				$remaining = $sourdough_flour - $subtracted;
				foreach ( array_keys( $flour_amounts ) as $id ) {
					if ( $remaining <= 0 ) {
						break;
					}
					$take = min( $remaining, (float) $flour_amounts[ $id ] );
					$flour_amounts[ $id ] -= $take;
					$remaining -= $take;
					if ( $flour_amounts[ $id ] <= 0 ) {
						unset( $flour_amounts[ $id ] );
					}
				}
			}
		}

		// ── Extras / Bruehstueck (Regelwerk E.2-E.5) ──
		$bruehstueck_items = $this->build_extras( $ctx, $water_main );
		$water_main = $bruehstueck_items['water_main'];

		if ( ! empty( $bruehstueck_items['items'] ) ) {
			$groups['bruehstueck'] = array(
				'label' => __( 'Brühstück', 'brotarchitekt' ),
				'items' => $bruehstueck_items['items'],
			);
		}

		// ── Hauptteig ──
		$water_main = max( 0, round( $water_main, 0 ) );
		$yeast_total = $ctx->yeast_pct + $ctx->beginner_yeast_pct;
		$hefe_g = round( $ctx->total_flour * $yeast_total / 100, 1 );

		$ctx->log( 'Ingredients', 'Hauptteig', 'Restwasser ' . $water_main . 'g, Hefe ' . $hefe_g . 'g (' . $yeast_total . '%), Salz ' . $salt . 'g (2%)' );

		$main_items = array();
		foreach ( $flour_amounts as $id => $g ) {
			if ( $g <= 0 ) {
				continue;
			}
			$main_items[] = array(
				'name'    => Brotarchitekt_Data::get_flour_label( $id ),
				'amount'  => $g,
				'unit'    => 'g',
				'percent' => $this->pct( $g ),
			);
		}
		$main_items[] = array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $water_main, 'unit' => 'g', 'percent' => $this->pct( $water_main ) );
		if ( $hefe_g > 0 ) {
			$main_items[] = array( 'name' => __( 'Hefe (frisch)', 'brotarchitekt' ), 'amount' => $hefe_g, 'unit' => 'g', 'percent' => $this->pct( $hefe_g ) );
		}
		$main_items[] = array( 'name' => __( 'Salz', 'brotarchitekt' ), 'amount' => $salt, 'unit' => 'g', 'percent' => $this->pct( $salt ) );

		$groups['main'] = array( 'label' => __( 'Hauptteig', 'brotarchitekt' ), 'items' => $main_items );

		return $groups;
	}

	/**
	 * Extras-Gewicht (fuer Gesamtgewicht-Anzeige, Fix 15).
	 */
	public function get_extras_weight( Brotarchitekt_Recipe_Context $ctx ): float {
		$extras     = (array) $ctx->input['extras'];
		$extra_data = Brotarchitekt_Data::get_extras();

		$kern_count     = 0;
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

		$max_kern = $ctx->level <= 3 ? 20 : 30;
		$weight   = 0.0;

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
			$weight += $ctx->total_flour * $pct / 100;
		}

		// Gesamte Zugaben auf max. 30% vom Mehl begrenzen
		$max_weight = $ctx->total_flour * 0.30;
		if ( $weight > $max_weight ) {
			$weight = $max_weight;
		}

		return $weight;
	}

	// ── Private Helpers ──

	/**
	 * Extras verarbeiten: Bruehstueck, trocken oder mit Extra-Wasser (je nach Verfuegbarkeit).
	 *
	 * Fix 13: 4h-Brot → Koerner trocken, TA-Extras mit 1:1 Wasser, Schrot nicht verfuegbar
	 * Fix 16: 6-8h + ST → kein Bruehstueck-Wasser abziehen
	 *
	 * @return array{items: list<array>, water_main: float}
	 */
	private function build_extras( Brotarchitekt_Recipe_Context $ctx, float $water_main ): array {
		$extras     = (array) $ctx->input['extras'];
		$extra_data = Brotarchitekt_Data::get_extras();
		$items      = array();

		if ( empty( $extras ) ) {
			return array( 'items' => $items, 'water_main' => $water_main );
		}

		// Kategorien zaehlen
		$kern_count     = 0;
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

		$max_kern      = $ctx->level <= 3 ? 20 : 30;
		$h             = (int) $ctx->input['timeBudget'];
		$is_quick      = $h <= 6; // 4-6h Bucket

		// Gesamt-% aller Extras berechnen und auf 30% deckeln
		$total_pct = 0;
		foreach ( $extras as $eid ) {
			if ( ! isset( $extra_data[ $eid ] ) ) {
				continue;
			}
			if ( $is_quick && $eid === 'grist' ) {
				continue;
			}
			$e = $extra_data[ $eid ];
			if ( $e['category'] === 'kern' ) {
				$total_pct += $kern_count === 1 ? 15 : $max_kern / $kern_count;
			} else {
				$total_pct += $ta_raise_count === 1 ? 10 : 5;
			}
		}
		$extras_scale = $total_pct > 30 ? 30 / $total_pct : 1.0;
		if ( $extras_scale < 1.0 ) {
			$ctx->log( 'Ingredients', 'Extras-Cap', 'Zugaben ' . round( $total_pct, 1 ) . '% > 30% → auf 30% skaliert (Faktor ' . round( $extras_scale, 3 ) . ')' );
		}

		foreach ( $extras as $eid ) {
			if ( ! isset( $extra_data[ $eid ] ) ) {
				continue;
			}
			$e = $extra_data[ $eid ];

			// Schrot bei 4h nicht verfuegbar (Regelwerk E.4)
			if ( $is_quick && $eid === 'grist' ) {
				continue;
			}

			// Menge berechnen (Regelwerk E.4), skaliert auf max 30% gesamt
			if ( $e['category'] === 'kern' ) {
				$pct = $kern_count === 1 ? 15 : $max_kern / $kern_count;
			} else {
				$pct = $ta_raise_count === 1 ? 10 : 5;
			}
			$pct    *= $extras_scale;
			$amount = round( $ctx->total_flour * $pct / 100, 0 );

			if ( $is_quick ) {
				// ── 4h: Kein Bruehstueck (Fix 13) ──
				if ( $e['category'] === 'kern' ) {
					// Koerner trocken, kein extra Wasser
					$items[] = array( 'name' => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ), 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct( $amount ) );
				} else {
					// TA-Extras: mit Mehl einarbeiten + gleiche Menge Wasser extra
					$water_main += $amount;
					$items[] = array( 'name' => $e['name'] . __( ' (mit Mehl einarbeiten)', 'brotarchitekt' ), 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct( $amount ) );
					$items[] = array( 'name' => __( 'Wasser (extra)', 'brotarchitekt' ), 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct( $amount ) );
				}
			} elseif ( $ctx->bruehstueck_available ) {
				// ── Bruehstueck normal ──
				$water_extra = round( $amount * $e['ratio'], 0 );
				$water_main -= $water_extra;
				$items[] = array( 'name' => $e['name'], 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct( $amount ) );
				$items[] = array( 'name' => __( 'Wasser (heiß)', 'brotarchitekt' ), 'amount' => $water_extra, 'unit' => 'g', 'percent' => $this->pct( $water_extra ) );
			} else {
				// ── 6-8h + ST: Kein Bruehstueck (Fix 16) ──
				$items[] = array( 'name' => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ), 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct( $amount ) );
			}
		}

		return array( 'items' => $items, 'water_main' => $water_main );
	}

	/** Prozent bezogen auf Gesamtmehl. */
	private function pct( float $amount ): float {
		return $this->ctx->total_flour > 0
			? round( $amount / $this->ctx->total_flour * 100, 1 )
			: 0;
	}

	/** Mehlmenge von einem bestimmten Getreide abziehen. */
	private function subtract_flour_from_grain( array &$flour_amounts, string $grain, float $amount ): float {
		$remaining = $amount;
		foreach ( array_keys( $flour_amounts ) as $id ) {
			if ( $remaining <= 0 ) {
				break;
			}
			if ( explode( '_', $id, 2 )[0] !== $grain ) {
				continue;
			}
			$take = min( $remaining, (float) $flour_amounts[ $id ] );
			$flour_amounts[ $id ] -= $take;
			$remaining -= $take;
			if ( $flour_amounts[ $id ] <= 0 ) {
				unset( $flour_amounts[ $id ] );
			}
		}
		return $amount - $remaining;
	}

	/** Mehlmenge von Dinkel/Urkorn abziehen (fuer Kochstueck). */
	private function subtract_flour_from_ancient( array &$flour_amounts, float $amount ): void {
		$ancient   = array( 'spelt', 'emmer', 'einkorn', 'kamut' );
		$remaining = $amount;

		foreach ( array_keys( $flour_amounts ) as $id ) {
			if ( $remaining <= 0 ) {
				break;
			}
			if ( ! in_array( explode( '_', $id, 2 )[0], $ancient, true ) ) {
				continue;
			}
			$take = min( $remaining, (float) $flour_amounts[ $id ] );
			$flour_amounts[ $id ] -= $take;
			$remaining -= $take;
			if ( $flour_amounts[ $id ] <= 0 ) {
				unset( $flour_amounts[ $id ] );
			}
		}
	}
}
