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
			$this->time_bucket = $h <= 4 ? '4-6h' : '6-8h';
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

		// Dinkel/Urkorn → automatisches Kochstück
		$ancient_grains = array( 'spelt', 'emmer', 'einkorn', 'kamut' );
		$main_flour_ids = array_keys( $this->flour_breakdown );
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
			'weight'   => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 ), // grob
		);
	}

	/**
	 * @return array<string, int|float>
	 */
	protected function get_recipe_teaser(): array {
		return array(
			'ta'     => $this->ta,
			'weight' => round( $this->total_flour + $this->water_total + $this->total_flour * 0.02 ),
		);
	}

	/**
	 * @return array<string, array{label: string, items: array<int, array{name: string, amount: int|float, unit: string}>}>
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

		// Sauerteig
		$sourdough_flour = 0;
		$sourdough_water = 0;
		if ( $this->sourdough_pct > 0 ) {
			$st_type = Brotarchitekt_Data::get_sourdough_types();
			$st = isset( $st_type[ $this->input['sourdoughType'] ] ) ? $st_type[ $this->input['sourdoughType'] ] : $st_type['rye'];
			$st_ta = $st['ta'] / 100;
			// ST = Mehl + Wasser, ST_Anteil in % vom Hauptteig-Mehl
			$st_total = $this->total_flour * ( $this->sourdough_pct / 100 );
			$st_mehl = $st_total / ( 1 + $st_ta );
			$st_water = $st_total - $st_mehl;
			$sourdough_flour = round( $st_mehl, 0 );
			$sourdough_water = round( $st_water, 0 );
			$water_main -= $sourdough_water;
			// Mehl vom entsprechenden Typ abziehen (vereinfacht: erstmal nur Mengen ausweisen)
		}

		// Kochstück (Dinkel/Urkorn)
		$kochstueck_mehl = 0;
		$kochstueck_water = 0;
		if ( $this->has_kochstueck ) {
			$kochstueck_mehl = round( $this->total_flour * 0.04, 0 );
			$kochstueck_water = $kochstueck_mehl * 5;
			$water_main -= $kochstueck_water;
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

		foreach ( $extras as $eid ) {
			if ( ! isset( $extra_data[ $eid ] ) ) {
				continue;
			}
			$e = $extra_data[ $eid ];
			$amount = $e['category'] === 'kern'
				? round( $this->total_flour * ( $kern_count === 1 ? $first_kern : $max_kern / $kern_count ) / 100, 0 )
				: round( $this->total_flour * ( $ta_raise_count === 1 ? $ta_raise_single : $ta_raise_multi ) / 100, 0 );
			$water_extra = round( $amount * $e['ratio'], 0 );
			$water_main -= $water_extra;
			$bruehstueck[] = array(
				'name'   => $e['name'],
				'amount' => $amount,
				'water'  => $water_extra,
			);
		}

		$water_main = max( 0, round( $water_main, 0 ) );

		// Hefe
		$yeast_total = $this->yeast_pct + $this->beginner_yeast_pct;
		$hefe_g = round( $this->total_flour * $yeast_total / 100, 1 );

		$groups = array();

		if ( $this->sourdough_pct > 0 ) {
			$groups['sourdough'] = array(
				'label' => __( 'Sauerteig', 'brotarchitekt' ),
				'items'  => array(
					array( 'name' => __( 'Sauerteig (Anstellgut + Mehl + Wasser)', 'brotarchitekt' ), 'amount' => $sourdough_flour + $sourdough_water, 'unit' => 'g' ),
				),
			);
		}

		if ( $kochstueck_mehl > 0 ) {
			$groups['kochstueck'] = array(
				'label' => __( 'Kochstück (Tangzhong)', 'brotarchitekt' ),
				'items' => array(
					array( 'name' => __( 'Mehl', 'brotarchitekt' ), 'amount' => $kochstueck_mehl, 'unit' => 'g' ),
					array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $kochstueck_water, 'unit' => 'g' ),
				),
			);
		}

		if ( ! empty( $bruehstueck ) ) {
			$items = array();
			foreach ( $bruehstueck as $b ) {
				$items[] = array( 'name' => $b['name'], 'amount' => $b['amount'], 'unit' => 'g' );
				$items[] = array( 'name' => __( 'Wasser (heiß)', 'brotarchitekt' ), 'amount' => $b['water'], 'unit' => 'g' );
			}
			$groups['bruehstueck'] = array( 'label' => __( 'Brühstück', 'brotarchitekt' ), 'items' => $items );
		}

		$main_items = array();
		foreach ( $flour_amounts as $id => $g ) {
			$main_items[] = array( 'name' => Brotarchitekt_Data::get_flour_label( $id ), 'amount' => $g, 'unit' => 'g' );
		}
		$main_items[] = array( 'name' => __( 'Wasser', 'brotarchitekt' ), 'amount' => $water_main, 'unit' => 'g' );
		if ( $hefe_g > 0 ) {
			$main_items[] = array( 'name' => __( 'Hefe (frisch)', 'brotarchitekt' ), 'amount' => $hefe_g, 'unit' => 'g' );
		}
		$main_items[] = array( 'name' => __( 'Salz', 'brotarchitekt' ), 'amount' => $salt, 'unit' => 'g' );
		$groups['main'] = array( 'label' => __( 'Hauptteig', 'brotarchitekt' ), 'items' => $main_items );

		return $groups;
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

		// Sauerteig ansetzen
		if ( $leavening !== 'yeast' ) {
			$st_duration = 360; // 6h default
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
			$t += $st_duration * 60;
		}

		// Brühstück (parallel möglich)
		$extras = (array) $this->input['extras'];
		if ( ! empty( $extras ) && $time_budget_h >= 8 ) {
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Brühstück ansetzen', 'brotarchitekt' ),
				'duration' => 60,
				'desc'     => __( 'Extras mit kochendem Wasser übergießen, 1 Stunde quellen lassen, dann abkühlen.', 'brotarchitekt' ),
			);
			$t += 60 * 60;
		}

		// Fermentolyse (Dinkel/Urkorn/VK)
		if ( $this->has_kochstueck || $this->rye_share < 50 ) {
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

		// Stretch & Fold (nicht bei Roggen >= 75%)
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
		}

		// Stockgare
		$stockgare_min = 90;
		if ( $from_fridge ) {
			$stockgare_min = 8 * 60; // 8h Kühlschrank
		} elseif ( $this->sourdough_pct > 0 ) {
			$stockgare_min = 150;
		}
		$steps[] = array(
			'time'     => $t,
			'label'    => $from_fridge ? __( 'Stockgare (Kühlschrank)', 'brotarchitekt' ) : __( 'Stockgare', 'brotarchitekt' ),
			'duration' => $stockgare_min,
			'desc'     => $from_fridge ? __( 'Geformtes Brot abgedeckt mind. 8 Stunden im Kühlschrank lassen.', 'brotarchitekt' ) : __( 'Teig abdecken und gehen lassen.', 'brotarchitekt' ),
		);
		$t += $stockgare_min * 60;

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

		// Stückgare
		$stueckgare_min = $this->rye_share >= 75 ? 150 : 90;
		if ( $from_fridge ) {
			$stueckgare_min = 0;
		}
		if ( $stueckgare_min > 0 ) {
			$steps[] = array(
				'time'     => $t,
				'label'    => __( 'Stückgare', 'brotarchitekt' ),
				'duration' => $stueckgare_min,
				'desc'     => __( 'Geformtes Brot abdecken und gehen lassen. Fingertest: Delle soll langsam zurückgehen.', 'brotarchitekt' ),
			);
			$t += $stueckgare_min * 60;
		}

		// Ofen vorheizen
		$preheat = $this->input['backMethod'] === 'pot' ? 40 : 50;
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
			$text = sprintf(
				__( 'Mit Schwaden/Dampf %d°C: 10 Min. Dann Dampf ablassen, %d°C: weitere %d Min.', 'brotarchitekt' ),
				$temp1,
				$temp2,
				$duration - 10
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
