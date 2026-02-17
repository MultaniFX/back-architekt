<?php
/**
 * Plugin Name: Brotarchitekt – Brot-Konfigurator
 * Plugin URI: https://github.com/brotarchitekt/brotarchitekt
 * Description: Geführter Wizard zum Erstellen individueller Brotrezepte mit grammgenauen Mengen und Zeitplan.
 * Version: 1.3.0
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

	public static function enqueue_assets( $design = 'v1' ) {
		wp_register_style(
			'brotarchitekt',
			BROTARCHITEKT_PLUGIN_URL . 'assets/css/style.css',
			array(),
			BROTARCHITEKT_VERSION
		);
		wp_enqueue_style( 'brotarchitekt' );

		if ( 'v2' === $design ) {
			wp_register_style(
				'brotarchitekt-v2',
				BROTARCHITEKT_PLUGIN_URL . 'assets/css/style-v2.css',
				array( 'brotarchitekt' ),
				BROTARCHITEKT_VERSION
			);
			wp_enqueue_style( 'brotarchitekt-v2' );
		}

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

	public static function shortcode( $atts = array() ) {
		$atts   = shortcode_atts( array( 'design' => 'v1' ), $atts, 'brotarchitekt' );
		$design = in_array( $atts['design'], array( 'v1', 'v2' ), true ) ? $atts['design'] : 'v1';
		self::enqueue_assets( $design );
		ob_start();
		?>
		<div id="brotarchitekt-app" class="brotarchitekt" data-state="wizard"<?php if ( 'v2' === $design ) echo ' data-design="v2"'; ?>>
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
									<span class="brotarchitekt-setting-icon" aria-hidden="true">🕐</span>
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
									<span class="brotarchitekt-setting-icon" aria-hidden="true">🧊</span>
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
							<div class="brotarchitekt-setting-card">
								<div class="brotarchitekt-setting-card-head">
									<span class="brotarchitekt-setting-icon" aria-hidden="true">⭐</span>
									<div>
										<h3 class="brotarchitekt-setting-title"><?php esc_html_e( 'Erfahrungslevel', 'brotarchitekt' ); ?></h3>
										<p class="brotarchitekt-setting-desc"><?php esc_html_e( 'Beeinflusst die verfügbaren Optionen', 'brotarchitekt' ); ?></p>
									</div>
								</div>
								<input type="range" id="ba-level" name="experienceLevel" min="1" max="5" value="2" step="1">
								<div class="brotarchitekt-level-info" data-level-info>
									<strong class="brotarchitekt-level-name" data-level-name><?php esc_html_e( 'Grundkenntnisse', 'brotarchitekt' ); ?></strong>
									<p class="brotarchitekt-level-desc" data-level-desc><?php esc_html_e( 'Einige Brote gebacken', 'brotarchitekt' ); ?></p>
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
								<button type="button" class="brotarchitekt-card" data-value="yeast"><span class="brotarchitekt-card-icon">☁️</span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Hefe', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Einfach & zuverlässig', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-card" data-value="sourdough"><span class="brotarchitekt-card-icon">🫙</span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Sauerteig', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Mehr Aroma & Bekömmlichkeit', 'brotarchitekt' ); ?></span></button>
								<button type="button" class="brotarchitekt-card" data-value="hybrid"><span class="brotarchitekt-card-icon">🫙☁️</span><span class="brotarchitekt-card-title"><?php esc_html_e( 'Beides', 'brotarchitekt' ); ?></span><span class="brotarchitekt-card-sub"><?php esc_html_e( 'Das Beste aus beiden Welten', 'brotarchitekt' ); ?></span></button>
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
								<span class="brotarchitekt-card-icon">🍲</span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Topf / Dutch Oven', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Gusseisen-Topf mit Deckel. Beste Kruste für Anfänger, verzeiht Fehler.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="pot" hidden><?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
							<button type="button" class="brotarchitekt-card brotarchitekt-card--back" data-value="stone">
								<span class="brotarchitekt-card-icon">🪨</span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Pizzastein', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Steinplatte im Ofen. Gleichmäßige Hitze von unten, gutes Ofentriebverhalten.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="stone" hidden><?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
							<button type="button" class="brotarchitekt-card brotarchitekt-card--back" data-value="steel">
								<span class="brotarchitekt-card-icon">⬛</span>
								<span class="brotarchitekt-card-title"><?php esc_html_e( 'Backstahl', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-card-sub"><?php esc_html_e( 'Stahlplatte im Ofen. Schnellste Hitzeübertragung, profihafte Kruste.', 'brotarchitekt' ); ?></span>
								<span class="brotarchitekt-recommended" data-recommended="steel" hidden><?php esc_html_e( 'Empfohlen', 'brotarchitekt' ); ?></span>
							</button>
						</div>
						<p class="brotarchitekt-back-hint">🔥 <?php esc_html_e( 'Alle Methoden funktionieren gut – die Empfehlung basiert auf deinem Erfahrungslevel. Backe so, wie du dich am wohlsten fühlst!', 'brotarchitekt' ); ?></p>
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
								<button type="button" class="brotarchitekt-btn brotarchitekt-btn--outline" data-action="new-recipe"><span aria-hidden="true">↻</span> <?php esc_html_e( 'Neues Rezept', 'brotarchitekt' ); ?></button>
								<button type="button" class="brotarchitekt-btn brotarchitekt-btn--footer-print" data-action="print"><span aria-hidden="true">🖨</span> <?php esc_html_e( 'Drucken', 'brotarchitekt' ); ?></button>
							</footer>
						</div>
					</section>
				</div>

				<div class="brotarchitekt-wizard-nav">
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--secondary" data-action="prev" hidden><span aria-hidden="true">←</span> <?php esc_html_e( 'Zurück', 'brotarchitekt' ); ?></button>
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--primary" data-action="next"><?php esc_html_e( 'Weiter', 'brotarchitekt' ); ?> <span aria-hidden="true">→</span></button>
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
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--outline" data-action="new-recipe"><span aria-hidden="true">↻</span> <?php esc_html_e( 'Neues Rezept', 'brotarchitekt' ); ?></button>
					<button type="button" class="brotarchitekt-btn brotarchitekt-btn--footer-print" data-action="print"><span aria-hidden="true">🖨</span> <?php esc_html_e( 'Drucken', 'brotarchitekt' ); ?></button>
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
