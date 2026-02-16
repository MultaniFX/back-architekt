<?php
/**
 * Rezept-Engine: Orchestriert die Berechnung von TA, Zutaten, Zeitplan und Backprofil.
 *
 * Delegiert an spezialisierte Klassen:
 * - Recipe_Context:       Gemeinsamer Zustand
 * - Leaven_Calculator:    Triebmittel + Time-Bucket
 * - Flour_Calculator:     Mehl, TA, Wasser
 * - Ingredients_Builder:  Zutatenliste
 * - Timeline_Builder:     Zeitplan
 * - Baking_Profile:       Backprofil
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Calculator {

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public function calculate( array $input ): array {
		$ctx = new Brotarchitekt_Recipe_Context( $input );

		// 1. Triebmittel ZUERST (time_bucket wird von Flour_Calculator fuer Bruehstueck-Verfuegbarkeit gebraucht)
		( new Brotarchitekt_Leaven_Calculator() )->compute( $ctx );

		// 2. Mehl + TA (braucht time_bucket)
		( new Brotarchitekt_Flour_Calculator() )->compute( $ctx );

		// 3. Outputs generieren
		$ingredients_builder = new Brotarchitekt_Ingredients_Builder();
		$ingredients = $ingredients_builder->build( $ctx );
		$timeline    = ( new Brotarchitekt_Timeline_Builder() )->build( $ctx );
		$baking      = new Brotarchitekt_Baking_Profile();

		return array(
			'name'        => $this->get_recipe_name( $ctx ),
			'meta'        => $this->get_recipe_meta( $ctx, $ingredients_builder ),
			'teaser'      => $this->get_recipe_teaser( $ctx, $ingredients_builder ),
			'ingredients' => $ingredients,
			'timeline'    => $timeline,
			'baking'      => $baking->get_instructions( $ctx ),
			'warnings'    => $this->get_warnings( $ctx ),
		);
	}

	/**
	 * Dynamischer Rezeptname (Regelwerk Abschnitt 8).
	 *
	 * Schema: [Geschwindigkeit] [Hauptmehl][-Mischbrot/brot] [mit Extras]
	 */
	private function get_recipe_name( Brotarchitekt_Recipe_Context $ctx ): string {
		$h = (int) $ctx->input['timeBudget'];
		$speed = '';
		if ( $h < 8 ) {
			$speed = __( 'Schnelles', 'brotarchitekt' );
		} elseif ( $h > 16 ) {
			$speed = __( 'Langsam geführtes', 'brotarchitekt' );
		}

		$main = array_filter( (array) $ctx->input['mainFlours'] );
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

		$name = trim( $speed . ' ' . $flour_part . '-' . __( 'brot', 'brotarchitekt' ) );

		$extras = (array) $ctx->input['extras'];
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
	 * Rezept-Meta (Level, Zeit, Backmethode, TA, Gewicht).
	 *
	 * @return array<string, mixed>
	 */
	private function get_recipe_meta( Brotarchitekt_Recipe_Context $ctx, Brotarchitekt_Ingredients_Builder $ib ): array {
		$back_labels = array(
			'pot'   => __( 'Topf', 'brotarchitekt' ),
			'stone' => __( 'Pizzastein', 'brotarchitekt' ),
			'steel' => __( 'Backstahl', 'brotarchitekt' ),
		);
		$back = $ctx->input['backMethod'];

		return array(
			'level'  => $ctx->level_info['label'],
			'time'   => $ctx->input['timeBudget'] . ' h',
			'back'   => $back_labels[ $back ] ?? $back,
			'ta'     => $ctx->ta,
			'weight' => round( $ctx->total_flour + $ctx->water_total + $ctx->total_flour * 0.02 + $ib->get_extras_weight( $ctx ) ),
		);
	}

	/**
	 * @return array<string, int|float>
	 */
	private function get_recipe_teaser( Brotarchitekt_Recipe_Context $ctx, Brotarchitekt_Ingredients_Builder $ib ): array {
		return array(
			'ta'     => $ctx->ta,
			'weight' => round( $ctx->total_flour + $ctx->water_total + $ctx->total_flour * 0.02 + $ib->get_extras_weight( $ctx ) ),
		);
	}

	/**
	 * Warnungen.
	 *
	 * @return list<string>
	 */
	private function get_warnings( Brotarchitekt_Recipe_Context $ctx ): array {
		$w = array();

		if ( (int) $ctx->input['timeBudget'] < 8 && $ctx->input['leavening'] !== 'yeast' ) {
			$w[] = __( 'Dein Sauerteig muss bereits einsatzbereit sein.', 'brotarchitekt' );
		}
		if ( $ctx->rye_share >= 75 ) {
			$w[] = __( 'Roggenbrot mind. 24 Stunden vor dem Anschneiden ruhen lassen.', 'brotarchitekt' );
		}

		return $w;
	}
}
