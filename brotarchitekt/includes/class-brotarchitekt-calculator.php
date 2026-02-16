<?php
/**
 * Rezept-Engine: Berechnung von TA, Zutaten, Zeitplan und Backprofil
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Calculator {

	/** @var array<string, mixed> */
	protected array $input = [];

	/** @var array<string, mixed> */
	protected array $level_info = [];

	/** @var array<string, float> Anteile pro Mehl (grain_type => Prozent) */
	protected array $flour_breakdown = [];

	protected int $total_flour = 500;
	protected int $ta = 168;
	protected float $water_total = 0;
	protected bool $is_semola_only = false;
	protected float $rye_share = 0;
	protected bool $has_kochstueck = false;
	protected bool $has_ta_raise_bruehstueck = false;
	protected string $time_bucket = '8-12h';
	protected bool $uses_fridge = false;
	protected float $sourdough_pct = 0;
	protected float $yeast_pct = 0;
	protected float $beginner_yeast_pct = 0;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public function calculate( array $input ): array {
		$this->input = wp_parse_args( $input, array(
			'timeBudget'      => 12,
			'experienceLevel' => 2,
			'bakeFromFridge'  => false,
			'leavening'       => 'yeast',
			'sourdoughType'   => 'rye',
			'sourdoughReady'   => 'yes',
			'flourAmount'     => 500,
			'mainFlours'      => array(),
			'sideFlours'      => array(),
			'extras'          => array(),
			'backMethod'      => 'pot',
		) );

		$this->total_flour = max( 250, min( 1000, (int) $this->input['flourAmount'] ) );
		if ( $this->total_flour % 50 !== 0 ) {
			$this->total_flour = (int) ( round( $this->total_flour / 50 ) * 50 );
		}

		$this->level_info = Brotarchitekt_Data::get_level_info();
		$level = (int) $this->input['experienceLevel'];
		if ( $level < 1 || $level > 5 ) {
			$level = 2;
		}
		$this->level_info = $this->level_info[ $level ];

		$this->compute_flour_breakdown();
		$this->compute_rye_share();
		$this->compute_ta();
		$this->compute_triebmittel();
		$this->compute_time_bucket();

		$h = (int) $this->input['timeBudget'];
		$this->uses_fridge = $h >= 12 && $this->rye_share < 75;

		$recipe = array(
			'name'        => $this->get_recipe_name(),
			'meta'        => $this->get_recipe_meta(),
			'teaser'      => $this->get_recipe_teaser(),
			'ingredients' => $this->get_ingredients(),
			'timeline'    => $this->get_timeline(),
			'baking'      => $this->get_baking_instructions(),
			'warnings'    => $this->get_warnings(),
		);

		return $recipe;
	}

	protected function compute_flour_breakdown(): void {
		$main = array_filter( (array) $this->input['mainFlours'] );
		$side = array_filter( (array) $this->input['sideFlours'] );
		if ( empty( $main ) ) {
			$main = array( 'wheat_1050' );
		}
		$main_count = count( $main );
		$side_count = count( $side );

		$this->flour_breakdown = array();
		if ( $side_count === 0 ) {
			$pct = $main_count > 0 ? 100 / $main_count : 100;
			foreach ( $main as $id ) {
				$this->flour_breakdown[ $id ] = $pct;
			}
		} else {
			$main_pct = 80 / max( 1, $main_count );
			$side_pct = 20 / max( 1, $side_count );
			foreach ( $main as $id ) {
				$this->flour_breakdown[ $id ] = $main_pct;
			}
			foreach ( $side as $id ) {
				$this->flour_breakdown[ $id ] = $side_pct;
			}
		}

		// Semola-only check
		$grains = array();
		foreach ( array_keys( $this->flour_breakdown ) as $id ) {
			$g = explode( '_', $id, 2 );
			$grains[ $g[0] ] = true;
		}
		$this->is_semola_only = count( $grains ) === 1 && isset( $grains['semola'] );
	}

	protected function compute_rye_share(): void {
		$this->rye_share = 0;
		foreach ( $this->flour_breakdown as $id => $pct ) {
			if ( strpos( $id, 'rye' ) === 0 ) {
				$this->rye_share += $pct;
			}
		}
	}

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

	protected function compute_ta(): void {
		$ta_base = $this->level_info['ta_base'];
		$ta_max  = $this->level_info['ta_max'];

		if ( $this->is_semola_only ) {
			$this->ta = 172;
			$this->has_kochstueck = false;
			$this->has_ta_raise_bruehstueck = false;
			$this->water_total = $this->total_flour * ( ( $this->ta - 100 ) / 100 );
			return;
		}

		// Vollkorn-Zuschlag
		$vk_share = 0;
		foreach ( $this->flour_breakdown as $id => $pct ) {
			if ( strpos( $id, '_Vollkorn' ) !== false ) {
				$vk_share += $pct;
			}
		}
		if ( $vk_share > 70 ) {
			$ta_base += 2;
			$ta_max += 2;
		}

		// Dinkel/Urkorn → automatisches Kochstück (nur Hauptmehle prüfen)
		$ancient_grains = array( 'spelt', 'emmer', 'einkorn', 'kamut' );
		$main_flour_ids = array_filter( (array) $this->input['mainFlours'] );
		foreach ( $main_flour_ids as $id ) {
			$g = explode( '_', $id, 2 )[0];
			if ( in_array( $g, $ancient_grains, true ) ) {
				$this->has_kochstueck = true;
				break;
			}
		}

		$extras = (array) $this->input['extras'];
		$ta_raise_extras = array( 'linseed', 'oatmeal', 'old_bread', 'grist' );
		foreach ( $extras as $e ) {
			if ( in_array( $e, $ta_raise_extras, true ) ) {
				$this->has_ta_raise_bruehstueck = true;
				break;
			}
		}

		// Brühstück-Verfügbarkeit: nicht bei 4-6h, nicht bei 6-8h + Sauerteig
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

		// Level 1-3: Kochstück entfällt wenn TA-erhöhender Brühstück
		if ( $this->input['experienceLevel'] <= 3 && $this->has_ta_raise_bruehstueck ) {
			$this->has_kochstueck = false;
		}

		$this->ta = $ta_base;
		if ( $this->has_kochstueck ) {
			$this->ta += 5;
		}
		if ( $this->has_ta_raise_bruehstueck ) {
			$this->ta += 5;
		}
		$this->ta = min( $this->ta, $ta_max );

		$this->water_total = $this->total_flour * ( ( $this->ta - 100 ) / 100 );
	}

	protected function compute_triebmittel(): void {
		$leavening = $this->input['leavening'];
		$level = (int) $this->input['experienceLevel'];

		$hefe_pct = array(
			'4-6h'   => 1.25,
			'6-8h'   => 0.85,
			'8-12h'  => 0.5,
			'12-24h' => 0.2,
			'24-36h' => 0.075,
			'36-48h' => 0.04,
		);
		$st_pct = array(
			'4-6h'   => 17,
			'6-8h'   => 20,
			'8-12h'  => 12,
			'12-24h' => 9,
			'24-36h' => 7,
			'36-48h' => 6,
		);

		$this->sourdough_pct = 0;
		$this->yeast_pct = 0;
		$this->beginner_yeast_pct = 0;

		if ( $leavening === 'yeast' ) {
			$this->yeast_pct = $hefe_pct[ $this->time_bucket ];
		} elseif ( $leavening === 'sourdough' || $leavening === 'hybrid' ) {
			$this->sourdough_pct = $st_pct[ $this->time_bucket ];
			if ( $leavening === 'hybrid' ) {
				$this->sourdough_pct /= 2;
				$this->yeast_pct = $hefe_pct[ $this->time_bucket ] / 2;
			}
			if ( $level <= 2 && $leavening === 'sourdough' ) {
				$this->beginner_yeast_pct = 0.1;
			}
		}
	}

	protected function get_recipe_name(): string {
		$h = (int) $this->input['timeBudget'];
		$speed = '';
		if ( $h < 8 ) {
			$speed = __( 'Schnelles', 'brotarchitekt' );
		} elseif ( $h > 16 ) {
			$speed = __( 'Langsam geführtes', 'brotarchitekt' );
		}

		$main = array_filter( (array) $this->input['mainFlours'] );
		$flour_names = array();
		$flours_js = Brotarchitekt_Data::get_flours_for_js();
		foreach ( $main as $id ) {
			foreach ( $flours_js as $f ) {
				if ( $f['id'] === $id ) {
					$flour_names[] = $f['label'];
					break;
				}
			}
		}
		$flour_part = count( $flour_names ) === 1
			? preg_replace( '/\s+\d+$/', '', $flour_names[0] )
			: __( 'Mischbrot', 'brotarchitekt' );

		$suffix = $this->rye_share > 50 ? __( 'brot', 'brotarchitekt' ) : __( 'brot', 'brotarchitekt' );
		$name = trim( $speed . ' ' . $flour_part . '-' . $suffix );

		$extras = (array) $this->input['extras'];
		if ( ! empty( $extras ) ) {
			$extra_names = array();
			foreach ( Brotarchitekt_Data::get_extras() as $key => $data ) {
				if ( in_array( $key, $extras, true ) ) {
					$extra_names[] = $data['name'];
				}
			}
			$name .= ' ' . __( 'mit', 'brotarchitekt' ) . ' ' . implode( __( ' und ', 'brotarchitekt' ), $extra_names );
		}

		return $name;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_recipe_meta(): array {
		$level = (int) $this->input['experienceLevel'];
		$level_info = Brotarchitekt_Data::get_level_info();
		$label = isset( $level_info[ $level ] ) ? $level_info[ $level ]['label'] : '';
		$back = $this->input['backMethod'];
		$back_labels = array(
			'pot'   => __( 'Topf', 'brotarchitekt' ),
			'stone' => __( 'Pizzastein', 'brotarchitekt' ),
			'steel' => __( 'Backstahl', 'brotarchitekt' ),
		);
		return array(
			'level'    => $label,
			'time'     => $this->input['timeBudget'] . ' h',
			'back'     => isset( $back_labels[ $back ] ) ? $back_labels[ $back ] : $back,
			'ta'       => $this->ta,
			'weight'   => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 + $this->get_extras_weight() ),
		);
	}

	/**
	 * @return array<string, int|float>
	 */
	protected function get_recipe_teaser(): array {
		return array(
			'ta'     => $this->ta,
			'weight' => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 + $this->get_extras_weight() ),
		);
	}

	/** Gesamtgewicht aller Extras (ohne Brühstück-Wasser, das ist bereits in water_total). */
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

	/** Getreide aus Mehl-ID (z. B. rye, wheat). */
	private function get_grain_from_flour_id( string $flour_id ): string {
		$parts = explode( '_', $flour_id, 2 );
		return $parts[0];
	}

	/** Von flour_amounts einen Betrag vom passenden Getreide abziehen. Gibt abgezogenen Betrag zurück. */
	private function subtract_flour_from_grain( array &$flour_amounts, string $grain, float $amount ): float {
		$remaining = $amount;
		foreach ( array_keys( $flour_amounts ) as $id ) {
			if ( $remaining <= 0 ) {
				break;
			}
			if ( $this->get_grain_from_flour_id( $id ) !== $grain ) {
				continue;
			}
			$take = min( $remaining, (float) $flour_amounts[ $id ] );
			$flour_amounts[ $id ] = $flour_amounts[ $id ] - $take;
			$remaining -= $take;
			if ( $flour_amounts[ $id ] <= 0 ) {
				unset( $flour_amounts[ $id ] );
			}
		}
		return $amount - $remaining;
	}

	/** Von flour_amounts einen Betrag von Urkorn/Dinkel abziehen (Kochstück). */
	private function subtract_flour_from_ancient( array &$flour_amounts, float $amount ): float {
		$ancient = array( 'spelt', 'emmer', 'einkorn', 'kamut' );
		$remaining = $amount;
		foreach ( array_keys( $flour_amounts ) as $id ) {
			if ( $remaining <= 0 ) {
				break;
			}
			$g = $this->get_grain_from_flour_id( $id );
			if ( ! in_array( $g, $ancient, true ) ) {
				continue;
			}
			$take = min( $remaining, (float) $flour_amounts[ $id ] );
			$flour_amounts[ $id ] = $flour_amounts[ $id ] - $take;
			$remaining -= $take;
			if ( $flour_amounts[ $id ] <= 0 ) {
				unset( $flour_amounts[ $id ] );
			}
		}
		return $amount - $remaining;
	}

	/** Prozent bezogen auf Gesamtmehl (für Anzeige in Klammern). */
	private function percent_of_flour( $amount ): float {
		return $this->total_flour > 0 ? round( ( (float) $amount / $this->total_flour ) * 100, 1 ) : 0;
	}

	/**
	 * @return array<string, array{label: string, items: array<int, array{name: string, amount: int|float, unit: string, percent?: float}>}>
	 */
	protected function get_ingredients(): array {
		$flour_amounts = array();
		foreach ( $this->flour_breakdown as $id => $pct ) {
			$g = round( $this->total_flour * $pct / 100, 0 );
			if ( $g > 0 ) {
				$flour_amounts[ $id ] = $g;
			}
		}

		$salt = round( $this->total_flour * 0.02, 0 );
		$water_main = $this->water_total;

		// Sauerteig: Menge berechnen; Wasser abziehen; Mehl später vom passenden Getreide abziehen
		$sourdough_flour = 0;
		$sourdough_water = 0;
		$st_flour_grain = null;
		if ( $this->sourdough_pct > 0 ) {
			$st_type = Brotarchitekt_Data::get_sourdough_types();
			$st = isset( $st_type[ $this->input['sourdoughType'] ] ) ? $st_type[ $this->input['sourdoughType'] ] : $st_type['rye'];
			$st_flour_grain = $st['flour_grain'];
			$sourdough_flour = round( $this->total_flour * ( $this->sourdough_pct / 100 ), 0 );
			$sourdough_water = round( $sourdough_flour * ( ( $st['ta'] - 100 ) / 100 ), 0 );
			$water_main -= $sourdough_water;
		}

		// Kochstück: Mehl vom Dinkel/Urkorn-Anteil abziehen
		$kochstueck_mehl = 0;
		$kochstueck_water = 0;
		if ( $this->has_kochstueck ) {
			$kochstueck_mehl = round( $this->total_flour * 0.04, 0 );
			$kochstueck_water = $kochstueck_mehl * 5;
			$water_main -= $kochstueck_water;
			$this->subtract_flour_from_ancient( $flour_amounts, $kochstueck_mehl );
		}

		// Sauerteig-Mehl vom passenden Getreide abziehen (sonst von erstem Hauptmehl)
		if ( $sourdough_flour > 0 && $st_flour_grain !== null ) {
			$subtracted = $this->subtract_flour_from_grain( $flour_amounts, $st_flour_grain, $sourdough_flour );
			if ( $subtracted < $sourdough_flour && ! empty( $flour_amounts ) ) {
				$remaining = $sourdough_flour - $subtracted;
				foreach ( array_keys( $flour_amounts ) as $id ) {
					if ( $remaining <= 0 ) {
						break;
					}
					$take = min( $remaining, (float) $flour_amounts[ $id ] );
					$flour_amounts[ $id ] = $flour_amounts[ $id ] - $take;
					$remaining -= $take;
					if ( $flour_amounts[ $id ] <= 0 ) {
						unset( $flour_amounts[ $id ] );
					}
				}
			}
		}

		// Brühstück (Extras)
		$bruehstueck = array();
		$extras = (array) $this->input['extras'];
		$extra_data = Brotarchitekt_Data::get_extras();
		$kern_count = 0;
		$ta_raise_count = 0;
		foreach ( $extras as $eid ) {
			if ( ! isset( $extra_data[ $eid ] ) ) {
				continue;
			}
			$e = $extra_data[ $eid ];
			if ( $e['category'] === 'kern' ) {
				$kern_count++;
			} else {
				$ta_raise_count++;
			}
		}
		$max_kern = $this->input['experienceLevel'] <= 3 ? 20 : 30;
		$max_ta_raise = $this->input['experienceLevel'] <= 3 ? 20 : 30;
		$first_kern = 15;
		$kern_total = min( $kern_count * 5, $max_kern );
		if ( $kern_count === 1 ) {
			$kern_total = $first_kern;
		} elseif ( $kern_count > 1 ) {
			$kern_total = min( $max_kern, $kern_count * ( 20 / max( 1, $kern_count ) ) );
		}
		$ta_raise_single = 10;
		$ta_raise_multi = 5;
		$ta_raise_pct = $ta_raise_count === 1 ? $ta_raise_single : min( $max_ta_raise, $ta_raise_count * $ta_raise_multi );

		$h = (int) $this->input['timeBudget'];
		$is_quick = $h <= 6; // 4-6h Bucket = kein Bruehstueck

		$bruehstueck_possible = true;
		if ( $h <= 6 ) {
			// is_quick — wird separat behandelt
		} elseif ( $h <= 8 && in_array( $this->input['leavening'], array( 'sourdough', 'hybrid' ), true ) ) {
			$bruehstueck_possible = false;
		}

		foreach ( $extras as $eid ) {
			if ( ! isset( $extra_data[ $eid ] ) ) {
				continue;
			}
			$e = $extra_data[ $eid ];

			if ( $is_quick && $eid === 'grist' ) {
				continue;
			}

			$amount = $e['category'] === 'kern'
				? round( $this->total_flour * ( $kern_count === 1 ? $first_kern : $max_kern / $kern_count ) / 100, 0 )
				: round( $this->total_flour * ( $ta_raise_count === 1 ? $ta_raise_single : $ta_raise_multi ) / 100, 0 );

			if ( $is_quick ) {
				if ( $e['category'] === 'kern' ) {
					$bruehstueck[] = array(
						'name'   => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ),
						'amount' => $amount,
						'water'  => 0,
					);
				} else {
					$water_main += $amount;
					$bruehstueck[] = array(
						'name'   => $e['name'] . __( ' (mit Mehl einarbeiten)', 'brotarchitekt' ),
						'amount' => $amount,
						'water'  => $amount,
					);
				}
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
					$bruehstueck[] = array(
						'name'   => $e['name'] . __( ' (trocken einarbeiten)', 'brotarchitekt' ),
						'amount' => $amount,
						'water'  => 0,
					);
				}
			}
		}

		$water_main = max( 0, round( $water_main, 0 ) );

		// Hefe
		$yeast_total = $this->yeast_pct + $this->beginner_yeast_pct;
		$hefe_g = round( $this->total_flour * $yeast_total / 100, 1 );

		$groups = array();

		if ( $this->sourdough_pct > 0 ) {
			$st_total_g = $sourdough_flour + $sourdough_water;
			$groups['sourdough'] = array(
				'label' => __( 'Sauerteig', 'brotarchitekt' ),
				'items'  => array(
					array(
						'name'    => __( 'Sauerteig (Anstellgut + Mehl + Wasser)', 'brotarchitekt' ),
						'amount'  => $st_total_g,
						'unit'    => 'g',
						'percent' => $this->percent_of_flour( $st_total_g ),
					),
				),
			);
		}

		if ( $kochstueck_mehl > 0 ) {
			$groups['kochstueck'] = array(
				'label' => __( 'Kochstück (Tangzhong)', 'brotarchitekt' ),
				'items' => array(
					array( 'name' => __( 'Mehl', 'brotarchitekt' ), 'amount' => $kochstueck_mehl, 'unit' => 'g', 'percent' => $this->percent_of_flour( $kochstueck_mehl ) ),
					array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $kochstueck_water, 'unit' => 'g', 'percent' => $this->percent_of_flour( $kochstueck_water ) ),
				),
			);
		}

		if ( ! empty( $bruehstueck ) ) {
			$items = array();
			foreach ( $bruehstueck as $b ) {
				$items[] = array( 'name' => $b['name'], 'amount' => $b['amount'], 'unit' => 'g', 'percent' => $this->percent_of_flour( $b['amount'] ) );
				$items[] = array( 'name' => __( 'Wasser (heiß)', 'brotarchitekt' ), 'amount' => $b['water'], 'unit' => 'g', 'percent' => $this->percent_of_flour( $b['water'] ) );
			}
			$groups['bruehstueck'] = array( 'label' => __( 'Brühstück', 'brotarchitekt' ), 'items' => $items );
		}

		$main_items = array();
		foreach ( $flour_amounts as $id => $g ) {
			if ( $g <= 0 ) {
				continue;
			}
			$main_items[] = array(
				'name'    => Brotarchitekt_Data::get_flour_label( $id ),
				'amount'  => $g,
				'unit'    => 'g',
				'percent' => $this->percent_of_flour( $g ),
			);
		}
		$main_items[] = array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $water_main, 'unit' => 'g', 'percent' => $this->percent_of_flour( $water_main ) );
		if ( $hefe_g > 0 ) {
			$main_items[] = array( 'name' => __( 'Hefe (frisch)', 'brotarchitekt' ), 'amount' => $hefe_g, 'unit' => 'g', 'percent' => $this->percent_of_flour( $hefe_g ) );
		}
		$main_items[] = array( 'name' => __( 'Salz', 'brotarchitekt' ), 'amount' => $salt, 'unit' => 'g', 'percent' => $this->percent_of_flour( $salt ) );
		$groups['main'] = array( 'label' => __( 'Hauptteig', 'brotarchitekt' ), 'items' => $main_items );

		return $groups;
	}

	/** Prüft ob Fermentolyse nötig ist (Dinkel/Urkorn >= 60% oder Vollkorn >= 60%). */
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

	/**
	 * @return list<array{time: int, label: string, duration: int, desc: string, time_formatted: string, duration_formatted: string}>
	 */
	protected function get_timeline(): array {
		$steps = array();
		$now = current_time( 'timestamp' );
		$t = $now;
		$time_budget_h = (int) $this->input['timeBudget'];
		$from_fridge = ! empty( $this->input['bakeFromFridge'] );
		$leavening = $this->input['leavening'];
		$st_ready = $this->input['sourdoughReady'] === 'yes';

		// Sauerteig Auffrischung (wenn nötig)
		if ( $leavening !== 'yeast' && ! $st_ready && $time_budget_h >= 8 ) {
			$steps[] = array(
				'time'    => $t,
				'label'   => __( 'Sauerteig auffrischen', 'brotarchitekt' ),
				'duration' => 240,
				'desc'    => __( 'Anstellgut mit Mehl und Wasser im Verhältnis 1:3:3 (bzw. 1:2:1 bei Lievito Madre) mischen. 4 Stunden bei Raumtemperatur reifen lassen.', 'brotarchitekt' ),
			);
			$t += 240 * 60;
		}

		$bruehstueck_in_timeline = $time_budget_h >= 8
			|| ( $time_budget_h > 6 && $leavening === 'yeast' );
		$extras = (array) $this->input['extras'];

		// Sauerteig ansetzen (+ Brühstück/Kochstück parallel)
		if ( $leavening !== 'yeast' ) {
			$st_start = $t;
			$st_duration = 360;
			if ( $time_budget_h >= 24 ) {
				$st_duration = 720;
			} elseif ( $time_budget_h >= 12 ) {
				$st_duration = 480;
			} elseif ( $time_budget_h >= 8 ) {
				$st_duration = 240;
			}
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Sauerteig ansetzen', 'brotarchitekt' ),
				'duration' => $st_duration,
				'desc'     => __( 'Sauerteig mit Mehl und Wasser mischen. Reifen lassen bis er deutlich aufgeht.', 'brotarchitekt' ),
			);

			if ( ! empty( $extras ) && $bruehstueck_in_timeline ) {
				$steps[] = array(
					'time'     => $st_start,
					'label'    => __( 'Brühstück ansetzen', 'brotarchitekt' ),
					'duration' => 60,
					'desc'     => __( 'Extras mit kochendem Wasser übergießen, 1 Stunde quellen lassen, dann abkühlen.', 'brotarchitekt' ),
				);
			}
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
				// Kein $t += — Kochstück kann parallel zum Brühstück-Quellen geschehen
			}
		}

		// Fermentolyse (Dinkel/Urkorn >= 60% oder Vollkorn >= 60%)
		if ( $this->needs_fermentolyse() ) {
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Fermentolyse (15 min)', 'brotarchitekt' ),
				'duration' => 15,
				'desc'     => __( 'Mehl und Wasser mit Triebmittel grob vermischen. Noch kein Salz! 15 Minuten ruhen lassen.', 'brotarchitekt' ),
			);
			$t += 15 * 60;
		}

		// Kneten / Mischen
		$knet_min = $this->rye_share >= 75 ? 4 : 12;
		$knet_label = $this->rye_share >= 75 ? __( 'Teig mischen (Roggen)', 'brotarchitekt' ) : __( 'Kneten', 'brotarchitekt' );
		$knet_desc = $this->rye_share >= 75
			? __( 'Alle Zutaten in einer Schüssel 3–5 Minuten kräftig zusammenrühren. Roggenteig nicht kneten wie Weizen.', 'brotarchitekt' )
			: __( 'Teig auf bemehlte Fläche geben. 2–3 min langsam, dann 2–3 min kräftiger kneten. Salz zugeben, weitere 4–8 min kneten bis glatt und elastisch.', 'brotarchitekt' );
		$steps[] = array(
			'time'     => $t,
			'label'    => $knet_label,
			'duration' => $knet_min,
			'desc'     => $knet_desc,
		);
		$t += $knet_min * 60;

		// Stretch & Fold (innerhalb der Stockgare, nicht bei Roggen >= 75%)
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

		// Stockgare (Gesamtdauer nach Regelwerk)
		$stockgare_min = 90;
		if ( $this->rye_share >= 75 ) {
			if ( $this->sourdough_pct >= 40 ) {
				$stockgare_min = 30;
			} elseif ( $this->sourdough_pct >= 25 ) {
				$stockgare_min = 60;
			} else {
				$stockgare_min = 120;
			}
		} elseif ( $this->sourdough_pct > 0 && $this->yeast_pct <= 0 && $this->beginner_yeast_pct <= 0 ) {
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
			if ( $this->yeast_pct >= 1.0 ) {
				$stockgare_min = 105;
			} elseif ( $this->yeast_pct >= 0.3 ) {
				$stockgare_min = 180;
			} else {
				$stockgare_min = 300;
			}
		}

		$h = $time_budget_h;

		if ( $this->uses_fridge && ! $from_fridge ) {
			// Variante 1: Kalte Stockgare — Anspringzeit → Kühlschrank → Formen → Warme Stückgare
			$fridge_hours = $h - 4;
			if ( $fridge_hours >= 16 ) {
				$anspring_min = 60;
			} elseif ( $fridge_hours >= 12 ) {
				$anspring_min = 90;
			} else {
				$anspring_min = 120;
			}
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
			$cold_hours = max( 8, $fridge_hours );
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Stockgare im Kühlschrank', 'brotarchitekt' ),
				'duration' => $cold_hours * 60,
				'desc'     => sprintf( __( 'Teig abgedeckt %d Stunden im Kühlschrank (4–5°C) gehen lassen.', 'brotarchitekt' ), $cold_hours ),
			);
			$t += $cold_hours * 60 * 60;
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Formen', 'brotarchitekt' ),
				'duration' => 10,
				'desc'     => __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.', 'brotarchitekt' ),
			);
			$t += 10 * 60;
			$stueckgare_min = 90;
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Stückgare (warm)', 'brotarchitekt' ),
				'duration' => $stueckgare_min,
				'desc'     => __( 'Geformtes Brot abdecken und bei Raumtemperatur gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' ),
			);
			$t += $stueckgare_min * 60;

		} elseif ( $this->uses_fridge && $from_fridge ) {
			// Variante 2: Direkt aus Kühlschrank — Warme Stockgare → Formen → Kalte Stückgare
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
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Formen', 'brotarchitekt' ),
				'duration' => 10,
				'desc'     => __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' ),
			);
			$t += 10 * 60;
			$cold_stueck_hours = max( 8, $h - 6 );
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Stückgare im Kühlschrank', 'brotarchitekt' ),
				'duration' => $cold_stueck_hours * 60,
				'desc'     => sprintf( __( 'Geformtes Brot abgedeckt mind. %d Stunden im Kühlschrank lassen. Direkt aus dem Kühlschrank backen.', 'brotarchitekt' ), $cold_stueck_hours ),
			);
			$t += $cold_stueck_hours * 60 * 60;

		} else {
			// Variante 3: Kein Kühlschrank — Restliche Stockgare → Formen → Warme Stückgare
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
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Formen', 'brotarchitekt' ),
				'duration' => 10,
				'desc'     => $this->rye_share >= 75
					? __( 'Hände und Fläche anfeuchten. Teig vorsichtig zu Laib oder Kugel formen. In bemehltes Gärkörbchen legen.', 'brotarchitekt' )
					: __( 'Teig zur Mitte falten, umdrehen, zu Kugel formen. Spannung auf der Oberfläche aufbauen.', 'brotarchitekt' ),
			);
			$t += 10 * 60;
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

		// Ofen vorheizen
		$method = $this->input['backMethod'];
		if ( $method === 'pot' ) {
			$preheat = 40;
		} elseif ( $method === 'steel' ) {
			$preheat = 35;
		} else {
			$preheat = 50; // Pizzastein
		}
		$steps[] = array(
			'time'     => $t,
			'label'    => __( 'Ofen vorheizen', 'brotarchitekt' ),
			'duration' => $preheat,
			'desc'     => $this->input['backMethod'] === 'pot' ? __( 'Topf mit im Ofen mit aufheizen (30–45 Min).', 'brotarchitekt' ) : __( 'Pizzastein/Backstahl 45–60 Min vorheizen.', 'brotarchitekt' ),
		);
		$t += $preheat * 60;

		// Backen
		$bake_min = $this->get_bake_duration();
		$steps[] = array(
			'time'     => $t,
			'label'    => __( 'Backen', 'brotarchitekt' ),
			'duration' => $bake_min,
			'desc'     => __( 'Brot einschießen. Mit Schwaden/Dampf starten, dann Temperatur reduzieren.', 'brotarchitekt' ),
		);
		$t += $bake_min * 60;

		// Auskühlen
		$steps[] = array(
			'time'     => $t,
			'label'    => __( 'Auskühlen', 'brotarchitekt' ),
			'duration' => 45,
			'desc'     => $this->rye_share >= 75
				? __( 'Mind. 24 Stunden liegen lassen, bevor angeschnitten wird!', 'brotarchitekt' )
				: __( '30–60 Min auf Gitter auskühlen lassen.', 'brotarchitekt' ),
		);

		foreach ( $steps as &$s ) {
			$s['time_formatted'] = date_i18n( 'H:i', $s['time'] );
			$s['duration_formatted'] = $s['duration'] >= 60 ? ( floor( $s['duration'] / 60 ) . ' h ' . ( $s['duration'] % 60 ? $s['duration'] % 60 . ' min' : '' ) ) : ( $s['duration'] . ' min' );
		}
		unset( $s );

		return $steps;
	}

	protected function get_bake_duration(): int {
		$method = $this->input['backMethod'];
		$is_rye = $this->rye_share >= 50;
		$w = $this->total_flour;
		if ( $w <= 600 ) {
			$slot = 0;
		} elseif ( $w <= 800 ) {
			$slot = 1;
		} else {
			$slot = 2;
		}
		$durations = array(
			'pot'   => $is_rye ? array( 45, 55, 65 ) : array( 40, 50, 60 ),
			'stone' => $is_rye ? array( 45, 55, 65 ) : array( 35, 45, 55 ),
			'steel' => $is_rye ? array( 45, 55, 65 ) : array( 35, 45, 55 ),
		);
		$key = $method === 'steel' ? 'stone' : $method;
		return isset( $durations[ $key ][ $slot ] ) ? $durations[ $key ][ $slot ] : 45;
	}

	protected function get_baking_instructions(): string {
		$method = $this->input['backMethod'];
		$is_rye = $this->rye_share >= 50;
		$temp1 = $is_rye ? 230 : 250;
		$temp2 = $is_rye ? 215 : 230;
		$duration = $this->get_bake_duration();

		$text = '';
		if ( $method === 'pot' ) {
			$text = sprintf(
				__( 'Topf mit Deckel %d°C: 25 Min. Dann Deckel abnehmen, %d°C: weitere %d Min. (je nach Mehlmenge).', 'brotarchitekt' ),
				$temp1,
				$temp2,
				$duration - 25
			);
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
		if ( $this->rye_share >= 75 ) {
			$text .= ' ' . __( 'Roggenbrot mind. 24 Stunden ruhen lassen vor dem Anschneiden!', 'brotarchitekt' );
		}
		return $text;
	}

	/**
	 * @return list<string>
	 */
	protected function get_warnings(): array {
		$w = array();
		if ( (int) $this->input['timeBudget'] < 8 && $this->input['leavening'] !== 'yeast' ) {
			$w[] = __( 'Dein Sauerteig muss bereits einsatzbereit sein.', 'brotarchitekt' );
		}
		if ( $this->rye_share >= 75 ) {
			$w[] = __( 'Roggenbrot mind. 24 Stunden vor dem Anschneiden ruhen lassen.', 'brotarchitekt' );
		}
		return $w;
	}
}
