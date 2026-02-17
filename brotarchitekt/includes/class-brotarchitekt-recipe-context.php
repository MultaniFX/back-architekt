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

	/* ── Decision Log (Debug-Ausgabe) ── */

	/** @var list<array{source: string, rule: string, result: string}> */
	public array $decisions = [];

	/**
	 * Entscheidung protokollieren.
	 *
	 * @param string $source  Modul (z.B. 'Flour', 'Leaven', 'Timeline')
	 * @param string $rule    Welche Regel greift (z.B. 'Regelwerk A.3: Vollkorn-Zuschlag')
	 * @param string $result  Was wurde entschieden (z.B. '+2 TA → Basis jetzt 175')
	 */
	public function log( string $source, string $rule, string $result ): void {
		$this->decisions[] = array(
			'source' => $source,
			'rule'   => $rule,
			'result' => $result,
		);
	}

	/**
	 * Debug-Zusammenfassung: Alle Input-Parameter lesbar.
	 *
	 * @return array<string, string>
	 */
	public function get_input_summary(): array {
		$leavening_labels = array(
			'yeast'     => 'Nur Hefe',
			'sourdough' => 'Nur Sauerteig',
			'hybrid'    => 'Hybrid (Hefe + Sauerteig)',
		);
		$st_labels = array(
			'rye'           => 'Roggensauer',
			'wheat'         => 'Weizensauer',
			'spelt'         => 'Dinkelsauer',
			'lievito_madre' => 'Lievito Madre',
		);
		$method_labels = array(
			'pot'   => 'Topf',
			'stone' => 'Pizzastein',
			'steel' => 'Backstahl',
		);

		$main = array_filter( (array) $this->input['mainFlours'] );
		$side = array_filter( (array) $this->input['sideFlours'] );
		$extras = (array) $this->input['extras'];

		$summary = array();
		$summary['Level']         = $this->level . ' (' . $this->level_info['label'] . ')';
		$summary['Zeitbudget']    = $this->input['timeBudget'] . 'h';
		$summary['Mehlmenge']     = $this->total_flour . 'g';
		$summary['Hauptmehle']    = ! empty( $main ) ? implode( ', ', array_map( fn( $id ) => Brotarchitekt_Data::get_flour_label( $id ), $main ) ) : '(keine)';
		$summary['Nebenmehle']    = ! empty( $side ) ? implode( ', ', array_map( fn( $id ) => Brotarchitekt_Data::get_flour_label( $id ), $side ) ) : '(keine)';
		$summary['Triebmittel']   = $leavening_labels[ $this->input['leavening'] ] ?? $this->input['leavening'];
		if ( $this->input['leavening'] !== 'yeast' ) {
			$summary['ST-Typ']    = $st_labels[ $this->input['sourdoughType'] ] ?? $this->input['sourdoughType'];
			$summary['ST bereit'] = $this->input['sourdoughReady'] === 'yes' ? 'Ja' : 'Nein';
		}
		$summary['Extras']        = ! empty( $extras ) ? implode( ', ', $extras ) : '(keine)';
		$summary['Backmethode']   = $method_labels[ $this->input['backMethod'] ] ?? $this->input['backMethod'];
		if ( ! empty( $this->input['bakeFromFridge'] ) ) {
			$summary['Kühlschrank'] = 'Direkt aus Kühlschrank backen';
		}

		return $summary;
	}

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

		// Level-Info laden (Fallback auf Level 2 falls Key fehlt)
		$all_levels = Brotarchitekt_Data::get_level_info();
		$this->level_info = isset( $all_levels[ $this->level ] ) && is_array( $all_levels[ $this->level ] )
			? $all_levels[ $this->level ]
			: $all_levels[2];
	}
}
