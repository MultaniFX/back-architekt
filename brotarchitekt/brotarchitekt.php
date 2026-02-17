<?php
/**
 * Plugin Name: Brotarchitekt – Brot-Konfigurator
 * Plugin URI: https://github.com/brotarchitekt/brotarchitekt
 * Description: Geführter Wizard zum Erstellen individueller Brotrezepte mit grammgenauen Mengen und Zeitplan.
 * Version: 1.0.0
 * Author: Brotarchitekt
 * Text Domain: brotarchitekt
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BROTARCHITEKT_VERSION', '1.3.0' );
define( 'BROTARCHITEKT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BROTARCHITEKT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-data.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-recipe-context.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-leaven-calculator.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-flour-calculator.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-baking-profile.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-ingredients-builder.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-timeline-builder.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-calculator.php';
require_once BROTARCHITEKT_PLUGIN_DIR . 'includes/class-brotarchitekt-rest.php';

/**
 * Plugin-Klasse: Assets registrieren, Shortcode
 */
final class Brotarchitekt_Plugin {

	public static function init() {
		add_shortcode( 'brotarchitekt', array( __CLASS__, 'shortcode' ) );
		add_action( 'rest_api_init', array( 'Brotarchitekt_REST', 'register_routes' ) );
	}

	public static function enqueue_assets() {
		wp_register_style(
			'brotarchitekt',
			BROTARCHITEKT_PLUGIN_URL . 'assets/css/style.css',
			array(),
			BROTARCHITEKT_VERSION
		);
		wp_enqueue_style( 'brotarchitekt' );

		wp_register_script(
			'brotarchitekt',
			BROTARCHITEKT_PLUGIN_URL . 'assets/js/wizard.js',
			array(),
			BROTARCHITEKT_VERSION,
			true
		);
		wp_enqueue_script( 'brotarchitekt' );

		wp_localize_script( 'brotarchitekt', 'brotarchitektData', array(
			'restUrl'   => rest_url( 'brotarchitekt/v1' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'labels'    => self::get_i18n_labels(),
			'flours'    => Brotarchitekt_Data::get_flours_for_js(),
			'extras'    => Brotarchitekt_Data::get_extras_for_js(),
			'levelInfo' => Brotarchitekt_Data::get_level_info_for_js(),
		) );
	}

	private static function get_i18n_labels() {
		return array(
			'start'           => __( 'Rezept erstellen', 'brotarchitekt' ),
			'back'            => __( 'Zurück', 'brotarchitekt' ),
			'next'            => __( 'Weiter', 'brotarchitekt' ),
			'noExtras'        => __( 'Keine Extras', 'brotarchitekt' ),
			'step1Title'      => __( 'Zeit & Erfahrung', 'brotarchitekt' ),
			'step2Title'      => __( 'Triebmittel & Mehl', 'brotarchitekt' ),
			'step3Title'      => __( 'Extras', 'brotarchitekt' ),
			'step4Title'      => __( 'Backmethode', 'brotarchitekt' ),
			'step5Title'      => __( 'Rezept', 'brotarchitekt' ),
			'newRecipe'       => __( 'Neues Rezept', 'brotarchitekt' ),
			'print'           => __( 'Drucken', 'brotarchitekt' ),
			'calculate'       => __( 'Rezept berechnen', 'brotarchitekt' ),
			'hours'           => __( 'Stunden', 'brotarchitekt' ),
			'mainFlourHint'   => __( 'Wähle bis zu', 'brotarchitekt' ),
			'mainFloursLabel' => __( 'Hauptmehle', 'brotarchitekt' ),
			'extrasCounter'   => __( 'Extras ausgewählt — Brühstück wird automatisch berechnet', 'brotarchitekt' ),
			'error'           => __( 'Es ist ein Fehler aufgetreten.', 'brotarchitekt' ),
			'timeBudget'      => __( 'Zeitbudget (Stunden)', 'brotarchitekt' ),
			'experience'      => __( 'Erfahrungslevel', 'brotarchitekt' ),
			'fromFridge'      => __( 'Direkt aus Kühlschrank backen?', 'brotarchitekt' ),
			'leavening'       => __( 'Triebmittel', 'brotarchitekt' ),
			'yeast'           => __( 'Hefe', 'brotarchitekt' ),
			'sourdough'       => __( 'Sauerteig', 'brotarchitekt' ),
			'hybrid'          => __( 'Beides (Hybrid)', 'brotarchitekt' ),
			'sourdoughType'   => __( 'Sauerteig-Typ', 'brotarchitekt' ),
			'sourdoughReady'  => __( 'Ist dein Sauerteig einsatzbereit?', 'brotarchitekt' ),
			'mainFlour'       => __( 'Hauptmehl(e)', 'brotarchitekt' ),
			'sideFlour'       => __( 'Weitere Mehle', 'brotarchitekt' ),
			'flourAmount'     => __( 'Mehlmenge (g)', 'brotarchitekt' ),
			'backMethod'      => __( 'Backmethode', 'brotarchitekt' ),
			'pot'             => __( 'Topf (Gusseisen/Dutch Oven)', 'brotarchitekt' ),
			'stone'           => __( 'Pizzastein', 'brotarchitekt' ),
			'steel'           => __( 'Backstahl', 'brotarchitekt' ),
			'recommended'     => __( 'Empfohlen', 'brotarchitekt' ),
			'level'           => array(
				1 => __( 'Einsteiger', 'brotarchitekt' ),
				2 => __( 'Grundkenntnisse', 'brotarchitekt' ),
				3 => __( 'Fortgeschritten', 'brotarchitekt' ),
				4 => __( 'Erfahren', 'brotarchitekt' ),
				5 => __( 'Profi', 'brotarchitekt' ),
			),
			'levelDesc'       => array(
				1 => __( 'Erste Gehversuche am Backen', 'brotarchitekt' ),
				2 => __( 'Einige Brote gebacken', 'brotarchitekt' ),
				3 => __( 'Routine mit verschiedenen Mehlen', 'brotarchitekt' ),
				4 => __( 'Viele Brote, auch Sauerteig', 'brotarchitekt' ),
				5 => __( 'Erfahren mit allen Techniken', 'brotarchitekt' ),
			),
		);
	}

	private static function icon( string $name ): string {
		$icons = array(
			'clock'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
			'snowflake'        => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/><line x1="20" y1="16" x2="4" y2="8"/><line x1="20" y1="8" x2="4" y2="16"/><line x1="16" y1="20" x2="8" y2="4"/><line x1="16" y1="4" x2="8" y2="20"/></svg>',
			'egg'              => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c6.23-.05 7.87-5.57 7.5-10-.36-4.34-3.95-9.96-7.5-10-3.55.04-7.14 5.66-7.5 10-.37 4.43 1.27 9.95 7.5 10z"/></svg>',
			'chef-hat'         => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z"/><path d="M6 17h12"/></svg>',
			'wheat'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/><path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/><path d="M15.47 13.47 17 15l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L9 15l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/><path d="M19.47 9.47 21 11l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L13 11l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/></svg>',
			'award'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
			'crown'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>',
			'flask-conical'    => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16.5h10"/></svg>',
			'cloud'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>',
			'jar'              => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v2.343"/><path d="M14 2v2.343"/><path d="M4.264 6.343c.594-.574 1.022-1.07 2.736-1.343h10c1.714.273 2.142.77 2.736 1.343C20.33 6.934 21 8.231 21 10v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-8c0-1.769.671-3.066 1.264-3.657Z"/><path d="M8 14c.22-.582 1.1-1 2-1 1.1 0 2 .672 2 1.5S11.1 16 10 16c-.9 0-1.78-.418-2-1"/><path d="M14 14c.22-.582 1.1-1 2-1 1.1 0 2 .672 2 1.5s-.9 1.5-2 1.5c-.9 0-1.78-.418-2-1"/></svg>',
			'sprout'           => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></svg>',
			'cookie'           => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/></svg>',
			'check-circle-2'   => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
			'soup'             => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 0 0 9-9H3a9 9 0 0 0 9 9Z"/><path d="M7 21h10"/><path d="M19.5 12 22 6"/><path d="M16.25 3c.27.1.8.53.75 1.36-.06.83-.93 1.2-1 2.02-.05.78.34 1.24.73 1.62"/><path d="M11.25 3c.27.1.8.53.74 1.36-.05.83-.93 1.2-.98 2.02-.06.78.33 1.24.72 1.62"/><path d="M6.25 3c.27.1.8.53.75 1.36-.06.83-.93 1.2-1 2.02-.05.78.34 1.24.73 1.62"/></svg>',
			'square'           => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>',
			'flame'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
			'badge-check'      => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>',
			'droplets'         => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>',
			'weight'           => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"/><path d="M6.5 8a2 2 0 0 0-1.905 1.46L2.1 18.23A2 2 0 0 0 4 21h16a2 2 0 0 0 1.925-2.54L19.4 9.46A2 2 0 0 0 17.48 8Z"/></svg>',
			'timer'            => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>',
			'utensils-crossed' => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 2-2.3 2.3a3 3 0 0 0 0 4.2l1.8 1.8a3 3 0 0 0 4.2 0L22 8"/><path d="M15 15 3.3 3.3a4.2 4.2 0 0 0 0 6l7.3 7.3c.7.7 2 .7 2.8 0L15 15Zm0 0 7 7"/><path d="m2.1 21.8 6.4-6.3"/><path d="m19 5-7 7"/></svg>',
			'thermometer'      => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>',
			'oven'             => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/></svg>',
			'hand'             => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2v0"/><path d="M14 10V4a2 2 0 0 0-2-2a2 2 0 0 0-2 2v2"/><path d="M10 10.5V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 13"/></svg>',
			'arrow-left'       => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
			'arrow-right'      => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
			'printer'          => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3h12v6"/><rect width="12" height="8" x="6" y="14"/></svg>',
			'refresh-cw'       => '<svg class="brotarchitekt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>',
		);
		return $icons[ $name ] ?? '';
	}

	public static function shortcode() {
		self::enqueue_assets();
		wp_enqueue_style( 'brotarchitekt' );
		wp_enqueue_script( 'brotarchitekt' );
		ob_start();
		?>
		<div id="brotarchitekt-app" class="brotarchitekt" data-state="wizard">
			<div class="brotarchitekt-landing" data-view="landing" hidden>
				<h1 class="brotarchitekt-hero-title"><?php esc_html_e( 'Der Brot-Architekt', 'brotarchitekt' ); ?></h1>
				<p class="brotarchitekt-hero-sub"><?php esc_html_e( 'Bau dir dein eigenes Brot — Schritt für Schritt. Grammgenaue Rezepte mit Zeitplan, perfekt abgestimmt auf deine Erfahrung.', 'brotarchitekt' ); ?></p>
				<button type="button" class="brotarchitekt-cta" data-action="start-wizard"><?php esc_html_e( 'Los geht\'s', 'brotarchitekt' ); ?></button>
			</div>

			<div class="brotarchitekt-wizard" data-view="wizard" hidden>
				<header class="brotarchitekt-wizard-header">
					<h1 class="brotarchitekt-wizard-title"><?php esc_html_e( 'Der Brot-Architekt', 'brotarchitekt' ); ?></h1>
					<p class="brotarchitekt-wizard-sub"><?php esc_html_e( 'Bau dir dein eigenes Brot — Schritt für Schritt.', 'brotarchitekt' ); ?></p>
				</header>
				<div class="brotarchitekt-progress" role="tablist">
					<div class="brotarchitekt-progress-track">
						<button type="button" class="brotarchitekt-progress-step is-active" data-step="1" aria-selected="true"><span class="brotarchitekt-progress-num">1</span><span class="brotarchitekt-progress-label"><?php esc_html_e( 'Zeit & Erfahrung', 'brotarchitekt' ); ?></span></button>
						<button type="button" class="brotarchitekt-progress-step" data-step="2" aria-selected="false"><span class="brotarchitekt-progress-num">2</span><span class="brotarchitekt-progress-label"><?php esc_html_e( 'Mehl & Triebmittel', 'brotarchitekt' ); ?></span></button>
						<button type="button" class="brotarchitekt-progress-step" data-step="3" aria-selected="false"><span class="brotarchitekt-progress-num">3</span><span class="brotarchitekt-progress-label"><?php esc_html_e( 'Extras', 'brotarchitekt' ); ?></span></button>
						<button type="button" class="brotarchitekt-progress-step" data-step="4" aria-selected="false"><span class="brotarchitekt-progress-num">4</span><span class="brotarchitekt-progress-label"><?php esc_html_e( 'Backmethode', 'brotarchitekt' ); ?></span></button>
						<button type="button" class="brotarchitekt-progress-step" data-step="5" aria-selected="false"><span class="brotarchitekt-progress-num">5</span><span class="brotarchitekt-progress-label"><?php esc_html_e( 'Rezept', 'brotarchitekt' ); ?></span></button>
					</div>
				</div>
				<div class="brotarchitekt-summary-tags" data-summary-tags aria-hidden="true"></div>

				<div class="brotarchitekt-steps">
					<section class="brotarchitekt-step" data-step="1" aria-hidden="false">
						<h2 class="brotarchitekt-step-title"><?php esc_html_e( 'Zeit & Erfahrung', 'brotarchitekt' ); ?></h2>
						<p class="brotarchitekt-step-sub"><?php esc_html_e( 'Wie viel Zeit hast du und wie erfahren bist du?', 'brotarchitekt' ); ?></p>
						<div class="brotarchitekt-setting-cards">
							<div class="brotarchitekt-setting-card">
								<div class="brotarchitekt-setting-card-head">
									<span class="brotarchitekt-setting-icon" aria-hidden="true"><?php echo self::icon( 'clock' ); ?></span>
									<div>
										<h3 class="brotarchitekt-setting-title"><?php esc_html_e( 'Zeitbudget', 'brotarchitekt' ); ?></h3>
										<p class="brotarchitekt-setting-desc"><?php esc_html_e( 'Von Teig bis fertiges Brot', 'brotarchitekt' ); ?></p>
									</div>
								</div>
								<input type="range" id="ba-time" name="timeBudget" min="4" max="48" value="12" step="1" list="ba-time-ticks">
								<datalist id="ba-time-ticks"><option value="4" label="4h"><option value="6" label="6h"><option value="8" label="8h"><option value="12" label="12h"><option value="16" label="16h"><option value="24" label="24h"><option value="36" label="36h"><option value="48" label="48h"></datalist>
								<output id="ba-time-value" class="brotarchitekt-setting-value" aria-live="polite">12 <?php esc_html_e( 'Stunden', 'brotarchitekt' ); ?></output>
								<span class="brotarchitekt-time-vibe" data-time-vibe></span>
							</div>
							<div class="brotarchitekt-setting-card" data-fridge-wrap hidden>
								<div class="brotarchitekt-setting-card-head">
									<span class="brotarchitekt-setting-icon" aria-hidden="true"><?php echo self::icon( 'snowflake' ); ?></span>
									<div>
										<h3 class="brotarchitekt-setting-title"><?php esc_html_e( 'Direkt aus dem Kühlschrank backen?', 'brotarchitekt' ); ?></h3>
										<p class="brotarchitekt-setting-desc"><?php esc_html_e( 'Brot formen, über Nacht kühlen, am nächsten Tag direkt backen', 'brotarchitekt' ); ?></p>
									</div>
								</div>
								<label class="brotarchitekt-toggle">
									<input type="checkbox" name="bakeFromFridge" value="1">
									<span class="brotarchitekt-toggle-slider"></span>
								</label>
							</div>
							<div class="brotarchitekt-setting-card brotarchitekt-setting-card--level">
								<h3 class="brotarchitekt-setting-title"><?php esc_html_e( 'Erfahrungslevel', 'brotarchitekt' ); ?></h3>
								<p class="brotarchitekt-setting-desc"><?php esc_html_e( 'Beeinflusst die verfügbaren Optionen', 'brotarchitekt' ); ?></p>
								<div class="brotarchitekt-level-cards" data-level-cards>
									<button type="button" class="brotarchitekt-level-card" data-level="1">
										<span class="brotarchitekt-level-card-icon"><?php echo self::icon( 'egg' ); ?></span>
										<span class="brotarchitekt-level-card-name"><?php esc_html_e( 'Einsteiger', 'brotarchitekt' ); ?></span>
										<span class="brotarchitekt-level-card-desc"><?php esc_html_e( 'Erste Gehversuche', 'brotarchitekt' ); ?></span>
									</button>
									<button type="button" class="brotarchitekt-level-card is-selected" data-level="2">
										<span class="brotarchitekt-level-card-icon"><?php echo self::icon( 'chef-hat' ); ?></span>
										<span class="brotarchitekt-level-card-name"><?php esc_html_e( 'Grundkenntnisse', 'brotarchitekt' ); ?></span>
										<span class="brotarchitekt-level-card-desc"><?php esc_html_e( 'Einige Brote gebacken', 'brotarchitekt' ); ?></span>
									</button>
									<button type="button" class="brotarchitekt-level-card" data-level="3">
										<span class="brotarchitekt-level-card-icon"><?php echo self::icon( 'wheat' ); ?></span>
										<span class="brotarchitekt-level-card-name"><?php esc_html_e( 'Fortgeschritten', 'brotarchitekt' ); ?></span>
										<span class="brotarchitekt-level-card-desc"><?php esc_html_e( 'Routine mit Mehlen', 'brotarchitekt' ); ?></span>
									</button>
									<button type="button" class="brotarchitekt-level-card" data-level="4">
										<span class="brotarchitekt-level-card-icon"><?php echo self::icon( 'award' ); ?></span>
										<span class="brotarchitekt-level-card-name"><?php esc_html_e( 'Erfahren', 'brotarchitekt' ); ?></span>
										<span class="brotarchitekt-level-card-desc"><?php esc_html_e( 'Auch Sauerteig', 'brotarchitekt' ); ?></span>
									</button>
									<button type="button" class="brotarchitekt-level-card" data-level="5">
										<span class="brotarchitekt-level-card-icon"><?php echo self::icon( 'crown' ); ?></span>
										<span class="brotarchitekt-level-card-name"><?php esc_html_e( 'Profi', 'brotarchitekt' ); ?></span>
										<span class="brotarchitekt-level-card-desc"><?php esc_html_e( 'Alle Techniken', 'brotarchitekt' ); ?></span>
									</button>
								</div>
							</div>
						</div>
					</section>

					<section class="brotarchitekt-step" data-step="2" aria-hidden="true">
						<h2 class="brotarchitekt-step-title"><?php esc_html_e( 'Mehl & Triebmittel', 'brotarchitekt' ); ?></h2>
						<p class="brotarchitekt-step-sub"><?php esc_html_e( 'Dein Teig: Triebmittel & Mehlauswahl', 'brotarchitekt' ); ?></p>
						<div class="brotarchitekt-block">
							<label class="brotarchitekt-block-label"><?php esc_html_e( 'Triebmittel', 'brotarchitekt' ); ?></label>
							<div class="brotarchitekt-cards" data-leavening>
								<button type="button" class="brotarchitekt-card" data-value="yeast"><span class="brotarchitekt-card-icon"><?php echo self::icon( 'cloud' ); ?></span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Hefe', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Einfach & zuverlässig', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-card" data-value="sourdough"><span class="brotarchitekt-card-icon"><?php echo self::icon( 'jar' ); ?></span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Sauerteig', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Mehr Aroma & Bekömmlichkeit', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-card" data-value="hybrid"><span class="brotarchitekt-card-icon"><?php echo self::icon( 'jar' ); ?><?php echo self::icon( 'cloud' ); ?></span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Beides', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Das Beste aus beiden Welten', 'brotarchitekt' ); ?></span></button>
							</div>
						</div>
						<div class="brotarchitekt-sourdough-options brotarchitekt-block" data-sourdough-options hidden>
							<label class="brotarchitekt-block-label"><?php esc_html_e( 'Sauerteig-Typ', 'brotarchitekt' ); ?></label>
							<div class="brotarchitekt-chips brotarchitekt-chips--sourdough" data-sourdough-type>
								<button type="button" class="brotarchitekt-chip" data-value="rye"><span class="brotarchitekt-chip-title"><?php esc_html_e( 'Roggen-ST', 'brotarchitekt' ); ?></span><span class="brotarchitekt-chip-sub"><?php esc_html_e( 'Kräftig, säurebetont', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-chip" data-value="wheat"><span class="brotarchitekt-chip-title"><?php esc_html_e( 'Weizen-ST', 'brotarchitekt' ); ?></span><span class="brotarchitekt-chip-sub"><?php esc_html_e( 'Mild, triebstark', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-chip" data-value="spelt"><span class="brotarchitekt-chip-title"><?php esc_html_e( 'Dinkel-ST', 'brotarchitekt' ); ?></span><span class="brotarchitekt-chip-sub"><?php esc_html_e( 'Nussig, aromatisch', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-chip" data-value="lievito_madre"><span class="brotarchitekt-chip-title"><?php esc_html_e( 'Lievito Madre', 'brotarchitekt' ); ?></span><span class="brotarchitekt-chip-sub"><?php esc_html_e( 'Italienisch, mild-süß', 'brotarchitekt' ); ?></span></button>
							</div>
							<div class="brotarchitekt-field brotarchitekt-field--toggle-row">
								<label class="brotarchitekt-toggle-row">
									<span><?php esc_html_e( 'Ist dein Sauerteig einsatzbereit?', 'brotarchitekt' ); ?></span>
									<input type="checkbox" name="sourdoughReadyToggle" value="1" checked>
									<span class="brotarchitekt-toggle-slider"></span>
								</label>
								<p class="brotarchitekt-hint" data-sourdough-ready-hint><?php esc_html_e( 'Aktiv und innerhalb der letzten 12h gefüttert', 'brotarchitekt' ); ?></p>
							</div>
							<div class="brotarchitekt-notice brotarchitekt-notice--warning" data-sourdough-warning hidden></div>
							<div class="brotarchitekt-notice brotarchitekt-notice--info" data-beginner-st-hint hidden><?php esc_html_e( 'Wir empfehlen Back-Anfängern ein wenig Hefe zur Gelingsicherheit. Wir fügen automatisch eine kleine Menge hinzu.', 'brotarchitekt' ); ?></div>
						</div>
						<div class="brotarchitekt-block">
							<label class="brotarchitekt-block-label"><?php esc_html_e( 'Hauptmehle', 'brotarchitekt' ); ?></label>
							<p class="brotarchitekt-block-hint" data-main-flour-hint><?php esc_html_e( 'Wähle bis zu 1 Hauptmehl', 'brotarchitekt' ); ?></p>
							<div class="brotarchitekt-notice brotarchitekt-notice--error" data-step-2-required hidden role="alert"><?php esc_html_e( 'Bitte wähle mindestens ein Hauptmehl, um fortzufahren.', 'brotarchitekt' ); ?></div>
							<div class="brotarchitekt-flour-selectors" data-main-flours></div>
						</div>
						<div class="brotarchitekt-block" data-side-flours-wrap hidden>
							<label class="brotarchitekt-block-label"><?php esc_html_e( 'Weitere Mehle', 'brotarchitekt' ); ?> <span class="brotarchitekt-optional">(<?php esc_html_e( 'optional', 'brotarchitekt' ); ?>)</span></label>
							<div class="brotarchitekt-flour-selectors" data-side-flours></div>
						</div>
						<div class="brotarchitekt-block">
							<label class="brotarchitekt-block-label"><?php esc_html_e( 'Mehlmenge', 'brotarchitekt' ); ?></label>
							<div class="brotarchitekt-stepper">
								<button type="button" class="brotarchitekt-stepper-btn" data-action="flour-minus" aria-label="<?php esc_attr_e( 'Weniger', 'brotarchitekt' ); ?>">−</button>
								<span class="brotarchitekt-stepper-value" data-flour-amount>500</span><span class="brotarchitekt-stepper-unit">g</span>
								<button type="button" class="brotarchitekt-stepper-btn" data-action="flour-plus" aria-label="<?php esc_attr_e( 'Mehr', 'brotarchitekt' ); ?>">+</button>
							</div>
							<input type="hidden" name="flourAmount" value="500" data-flour-amount-input>
							<p class="brotarchitekt-hint"><?php esc_html_e( 'Basis für alle Berechnungen (in 50g-Schritten, max 1000g)', 'brotarchitekt' ); ?></p>
						</div>
						<div class="brotarchitekt-notice brotarchitekt-notice--warning" data-rye-hint hidden></div>
					</section>

					<section class="brotarchitekt-step" data-step="3" aria-hidden="true">
						<h2 class="brotarchitekt-step-title"><?php esc_html_e( 'Extras', 'brotarchitekt' ); ?></h2>
						<p class="brotarchitekt-step-sub"><?php esc_html_e( 'Möchtest du Saaten oder Körner einarbeiten?', 'brotarchitekt' ); ?></p>
						<div class="brotarchitekt-notice brotarchitekt-notice--warning" data-extras-warning hidden></div>
						<div class="brotarchitekt-extras-grid" data-extras></div>
						<p class="brotarchitekt-extras-counter" data-extras-counter></p>
						<p class="brotarchitekt-skip-hint"><?php esc_html_e( 'Keine Extras? Einfach weiter zum nächsten Schritt.', 'brotarchitekt' ); ?></p>
					</section>

					<section class="brotarchitekt-step" data-step="4" aria-hidden="true">
						<h2 class="brotarchitekt-step-title"><?php esc_html_e( 'Backmethode', 'brotarchitekt' ); ?></h2>
						<p class="brotarchitekt-step-sub"><?php esc_html_e( 'Wie möchtest du dein Brot backen?', 'brotarchitekt' ); ?></p>
						<div class="brotarchitekt-cards brotarchitekt-cards--back" data-back-method>
							<button type="button" class="brotarchitekt-card brotarchitekt-card--back" data-value="pot">
								<span class="brotarchitekt-card-icon"><?php echo self::icon( 'soup' ); ?></span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Topf / Dutch Oven', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Gusseisen-Topf mit Deckel. Beste Kruste für Anfänger, verzeiht Fehler.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="pot" hidden><?php echo self::icon( 'badge-check' ); ?> <?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
							<button type="button" class="brotarchitekt-card brotarchitekt-card--back" data-value="stone">
								<span class="brotarchitekt-card-icon"><?php echo self::icon( 'square' ); ?></span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Pizzastein', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Steinplatte im Ofen. Gleichmäßige Hitze von unten, gutes Ofentriebverhalten.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="stone" hidden><?php echo self::icon( 'badge-check' ); ?> <?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
							<button type="button" class="brotarchitekt-card brotarchitekt-card--back" data-value="steel">
								<span class="brotarchitekt-card-icon"><?php echo self::icon( 'flame' ); ?></span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Backstahl', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Stahlplatte im Ofen. Schnellste Hitzeübertragung, profihafte Kruste.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="steel" hidden><?php echo self::icon( 'badge-check' ); ?> <?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
						</div>
						<p class="brotarchitekt-back-hint"><?php echo self::icon( 'flame' ); ?> <?php esc_html_e( 'Alle Methoden funktionieren gut – die Empfehlung basiert auf deinem Erfahrungslevel. Backe so, wie du dich am wohlsten fühlst!', 'brotarchitekt' ); ?></p>
					</section>

					<section class="brotarchitekt-step brotarchitekt-step--recipe" data-step="5" aria-hidden="true">
						<h2 class="brotarchitekt-step-title"><?php esc_html_e( 'Rezept', 'brotarchitekt' ); ?></h2>
						<p class="brotarchitekt-step-sub"><?php esc_html_e( 'Dein persönliches Brotrezept mit Mengen und Zeitplan.', 'brotarchitekt' ); ?></p>
						<div class="brotarchitekt-step-5-empty" data-step-5-empty hidden>
							<div class="brotarchitekt-notice brotarchitekt-notice--error" data-step-5-error hidden role="alert" aria-live="assertive"></div>
							<p class="brotarchitekt-step-5-cta"><?php esc_html_e( 'Alle Angaben sind erfasst. Erstelle jetzt dein Rezept.', 'brotarchitekt' ); ?></p>
							<button type="button" class="brotarchitekt-btn brotarchitekt-btn--primary brotarchitekt-btn--cta" data-action="calculate"><?php esc_html_e( 'Rezept erstellen', 'brotarchitekt' ); ?></button>
						</div>
						<div class="brotarchitekt-step-5-result" data-step-5-result hidden>
							<div class="brotarchitekt-result-header">
								<h3 class="brotarchitekt-recipe-title" data-recipe-title></h3>
								<div class="brotarchitekt-recipe-tags" data-recipe-meta></div>
								<div class="brotarchitekt-metric-cards" data-recipe-teaser></div>
							</div>
							<div class="brotarchitekt-result-body">
								<section class="brotarchitekt-ingredients" data-ingredients></section>
								<section class="brotarchitekt-timeline" data-timeline></section>
								<section class="brotarchitekt-baking" data-baking></section>
							</div>
							<section class="brotarchitekt-debug" data-debug hidden>
								<details>
									<summary class="brotarchitekt-debug-toggle"><?php esc_html_e( 'Debug: Berechnungsdetails', 'brotarchitekt' ); ?></summary>
									<div class="brotarchitekt-debug-content">
										<h4><?php esc_html_e( 'Eingabeparameter', 'brotarchitekt' ); ?></h4>
										<table class="brotarchitekt-debug-table" data-debug-input></table>
										<h4><?php esc_html_e( 'Entscheidungsprotokoll', 'brotarchitekt' ); ?></h4>
										<table class="brotarchitekt-debug-table" data-debug-decisions>
											<thead><tr><th><?php esc_html_e( 'Modul', 'brotarchitekt' ); ?></th><th><?php esc_html_e( 'Regel', 'brotarchitekt' ); ?></th><th><?php esc_html_e( 'Ergebnis', 'brotarchitekt' ); ?></th></tr></thead>
											<tbody></tbody>
										</table>
									</div>
								</details>
							</section>
							<footer class="brotarchitekt-result-footer">
								<button type="button" class="brotarchitekt-btn brotarchitekt-btn--outline" data-action="new-recipe"><?php echo self::icon( 'refresh-cw' ); ?> <?php esc_html_e( 'Neues Rezept', 'brotarchitekt' ); ?></button>
								<button type="button" class="brotarchitekt-btn brotarchitekt-btn--footer-print" data-action="print"><?php echo self::icon( 'printer' ); ?> <?php esc_html_e( 'Drucken', 'brotarchitekt' ); ?></button>
							</footer>
						</div>
					</section>
				</div>

				<div class="brotarchitekt-wizard-nav">
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--secondary" data-action="prev" hidden><?php echo self::icon( 'arrow-left' ); ?> <?php esc_html_e( 'Zurück', 'brotarchitekt' ); ?></button>
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--primary" data-action="next"><?php esc_html_e( 'Weiter', 'brotarchitekt' ); ?> <?php echo self::icon( 'arrow-right' ); ?></button>
				</div>
			</div>

			<div class="brotarchitekt-result" data-view="result" hidden>
				<div class="brotarchitekt-result-header">
					<h1 class="brotarchitekt-recipe-title" data-recipe-title></h1>
					<div class="brotarchitekt-recipe-tags" data-recipe-meta></div>
					<div class="brotarchitekt-metric-cards" data-recipe-teaser></div>
				</div>
				<div class="brotarchitekt-result-body">
					<section class="brotarchitekt-ingredients" data-ingredients></section>
					<section class="brotarchitekt-timeline" data-timeline></section>
					<section class="brotarchitekt-baking" data-baking></section>
				</div>
				<section class="brotarchitekt-debug" data-debug hidden>
					<details>
						<summary class="brotarchitekt-debug-toggle"><?php esc_html_e( 'Debug: Berechnungsdetails', 'brotarchitekt' ); ?></summary>
						<div class="brotarchitekt-debug-content">
							<h4><?php esc_html_e( 'Eingabeparameter', 'brotarchitekt' ); ?></h4>
							<table class="brotarchitekt-debug-table" data-debug-input></table>
							<h4><?php esc_html_e( 'Entscheidungsprotokoll', 'brotarchitekt' ); ?></h4>
							<table class="brotarchitekt-debug-table" data-debug-decisions>
								<thead><tr><th><?php esc_html_e( 'Modul', 'brotarchitekt' ); ?></th><th><?php esc_html_e( 'Regel', 'brotarchitekt' ); ?></th><th><?php esc_html_e( 'Ergebnis', 'brotarchitekt' ); ?></th></tr></thead>
								<tbody></tbody>
							</table>
						</div>
					</details>
				</section>
				<footer class="brotarchitekt-result-footer">
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--outline" data-action="new-recipe"><?php echo self::icon( 'refresh-cw' ); ?> <?php esc_html_e( 'Neues Rezept', 'brotarchitekt' ); ?></button>
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--footer-print" data-action="print"><?php echo self::icon( 'printer' ); ?> <?php esc_html_e( 'Drucken', 'brotarchitekt' ); ?></button>
				</footer>
			</div>

			<div class="brotarchitekt-loading" data-view="loading" hidden aria-live="polite">
				<p><?php esc_html_e( 'Rezept wird berechnet…', 'brotarchitekt' ); ?></p>
			</div>
			<div class="brotarchitekt-error" data-view="error" hidden aria-live="assertive"></div>
		</div>
		<?php
		return ob_get_clean();
	}
}

add_action( 'init', array( 'Brotarchitekt_Plugin', 'init' ) );
