<?php
/**
 * REST API: Rezept berechnen
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_REST {

	public static function register_routes(): void {
		register_rest_route( 'brotarchitekt/v1', '/recipe', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'calculate_recipe' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'timeBudget'      => array( 'required' => true, 'type' => 'integer', 'minimum' => 4, 'maximum' => 48 ),
				'experienceLevel' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'default' => 2 ),
				'bakeFromFridge'  => array( 'type' => 'boolean', 'default' => false ),
				'leavening'       => array( 'type' => 'string', 'enum' => array( 'yeast', 'sourdough', 'hybrid' ), 'default' => 'yeast' ),
				'sourdoughType'   => array( 'type' => 'string', 'default' => 'rye' ),
				'sourdoughReady'  => array( 'type' => 'string', 'enum' => array( 'yes', 'no' ), 'default' => 'yes' ),
				'flourAmount'     => array( 'type' => 'integer', 'minimum' => 250, 'maximum' => 1000, 'default' => 500 ),
				'mainFlours'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array() ),
				'sideFlours'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array() ),
				'extras'          => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array() ),
				'backMethod'      => array( 'type' => 'string', 'enum' => array( 'pot', 'stone', 'steel' ), 'default' => 'pot' ),
			),
		) );
	}

	public static function calculate_recipe( WP_REST_Request $request ) {
		try {
			$input = array(
				'timeBudget'      => $request->get_param( 'timeBudget' ),
				'experienceLevel' => $request->get_param( 'experienceLevel' ),
				'bakeFromFridge'  => $request->get_param( 'bakeFromFridge' ),
				'leavening'       => $request->get_param( 'leavening' ),
				'sourdoughType'   => $request->get_param( 'sourdoughType' ),
				'sourdoughReady'  => $request->get_param( 'sourdoughReady' ),
				'flourAmount'     => $request->get_param( 'flourAmount' ),
				'mainFlours'      => $request->get_param( 'mainFlours' ),
				'sideFlours'      => $request->get_param( 'sideFlours' ),
				'extras'          => $request->get_param( 'extras' ),
				'backMethod'      => $request->get_param( 'backMethod' ),
			);

			// Sicherstellen, dass Arrays vorliegen (z. B. wenn JSON-Body nicht geparst wurde)
			$input['mainFlours'] = is_array( $input['mainFlours'] ) ? $input['mainFlours'] : array();
			$input['sideFlours'] = is_array( $input['sideFlours'] ) ? $input['sideFlours'] : array();
			$input['extras']     = is_array( $input['extras'] ) ? $input['extras'] : array();
			if ( empty( $input['timeBudget'] ) ) {
				$input['timeBudget'] = 12;
			}

			$calc   = new Brotarchitekt_Calculator();
			$recipe = $calc->calculate( $input );

			return new WP_REST_Response( $recipe, 200 );
		} catch ( \Throwable $e ) {
			return new WP_REST_Response(
				array(
					'code'    => 'recipe_error',
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}
}
