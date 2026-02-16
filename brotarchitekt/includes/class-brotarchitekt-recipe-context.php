<?php
/**
 * Recipe Context: Gemeinsamer Zustand fuer alle Calculator-Module.
 *
 * Wird von Leaven_Calculator und Flour_Calculator befuellt,
 * dann von Ingredients_Builder, Timeline_Builder und Baking_Profile gelesen.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brotarchitekt_Recipe_Context {

	/* ── Input (readonly nach Konstruktion) ── */

	/** @var array<string, mixed> Normalisierter User-Input */
	public readonly array $input;

	/** Gesamtmehlmenge in Gramm (50g-Schritte, 250-1000g) */
	public readonly int $total_flour;

	/** @var array<string, mixed> Level-spezifische Konfiguration */
	public readonly array $level_info;

	/** Erfahrungslevel 1-5 */
	public readonly int $level;

	/* ── Mehl (gesetzt von Flour_Calculator) ── */

	/** @var array<string, float> Anteile pro Mehl-ID (z.B. wheat_1050 => 40.0) */
	public array $flour_breakdown = [];

	/** Roggenanteil in Prozent (0-100) */
	public float $rye_share = 0;

	/** Nur Semola/Hartweizen im Rezept */
	public bool $is_semola_only = false;

	/* ── TA & Wasser (gesetzt von Flour_Calculator) ── */

	/** Teigausbeute */
	public int $ta = 168;

	/** Gesamt-Wassermenge in Gramm (aus TA berechnet) */
	public float $water_total = 0;

	/** Automatisches Kochstueck (Dinkel/Urkorn als Hauptmehl) */
	public bool $has_kochstueck = false;

	/** Mindestens ein TA-erhoehender Bruehstueck-Extra vorhanden */
	public bool $has_ta_raise_bruehstueck = false;

	/** Ist ein Bruehstueck zeitlich moeglich? (EINE Wahrheitsquelle) */
	public bool $bruehstueck_available = true;

	/* ── Triebmittel (gesetzt von Leaven_Calculator) ── */

	/** Zeitkategorie (4-6h, 6-8h, 8-12h, 12-24h, 24-36h, 36-48h) */
	public string $time_bucket = '4-6h';

	/** Sauerteig-Mehlanteil als % vom Gesamtmehl */
	public float $sourdough_pct = 0;

	/** Hefe als % vom Gesamtmehl */
	public float $yeast_pct = 0;

	/** Zusaetzliche Anfaenger-Hefe (0.1% bei Level 1-2 + reinem ST) */
	public float $beginner_yeast_pct = 0;

	/* ── Kuehlschrank (gesetzt von Leaven_Calculator) ── */

	/** Kuehlschrank wird benutzt (>=12h und Roggen <75%) */
	public bool $uses_fridge = false;

	public function __construct( array $input ) {
		$this->input = wp_parse_args( $input, array(
			'timeBudget'      => 12,
			'experienceLevel' => 2,
			'bakeFromFridge'  => false,
			'leavening'       => 'yeast',
			'sourdoughType'   => 'rye',
			'sourdoughReady'  => 'yes',
			'flourAmount'     => 500,
			'mainFlours'      => array(),
			'sideFlours'      => array(),
			'extras'          => array(),
			'backMethod'      => 'pot',
		) );

		// Mehlmenge normalisieren (250-1000g, 50g-Schritte)
		$flour = max( 250, min( 1000, (int) $this->input['flourAmount'] ) );
		if ( $flour % 50 !== 0 ) {
			$flour = (int) ( round( $flour / 50 ) * 50 );
		}
		$this->total_flour = $flour;

		// Level normalisieren
		$level = (int) $this->input['experienceLevel'];
		$this->level = ( $level >= 1 && $level <= 5 ) ? $level : 2;

		// Level-Info laden
		$all_levels = Brotarchitekt_Data::get_level_info();
		$this->level_info = $all_levels[ $this->level ];
	}
}
