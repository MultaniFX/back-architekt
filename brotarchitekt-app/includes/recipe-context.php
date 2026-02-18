<?php
declare(strict_types=1);

class RecipeContext {

	public readonly array $input;
	public readonly int $total_flour;
	public readonly array $level_info;
	public readonly int $level;

	public array $flour_breakdown = [];
	public float $rye_share = 0;
	public bool $is_semola_only = false;

	public int $ta = 168;
	public float $water_total = 0;
	public bool $has_kochstueck = false;
	public bool $has_ta_raise_bruehstueck = false;
	public bool $bruehstueck_available = true;

	public string $time_bucket = '4-6h';
	public float $sourdough_pct = 0;
	public float $yeast_pct = 0;
	public float $beginner_yeast_pct = 0;

	public bool $uses_fridge = false;

	public array $decisions = [];

	public function log(string $source, string $rule, string $result): void {
		$this->decisions[] = array('source' => $source, 'rule' => $rule, 'result' => $result);
	}

	public function get_input_summary(): array {
		$leavening_labels = array('yeast' => 'Nur Hefe', 'sourdough' => 'Nur Sauerteig', 'hybrid' => 'Hybrid (Hefe + Sauerteig)');
		$st_labels = array('rye' => 'Roggensauer', 'wheat' => 'Weizensauer', 'spelt' => 'Dinkelsauer', 'lievito_madre' => 'Lievito Madre');
		$method_labels = array('pot' => 'Topf', 'stone' => 'Pizzastein', 'steel' => 'Backstahl');

		$main = array_filter((array) $this->input['mainFlours']);
		$side = array_filter((array) $this->input['sideFlours']);
		$extras = (array) $this->input['extras'];

		$summary = array();
		$summary['Level']       = $this->level . ' (' . $this->level_info['label'] . ')';
		$summary['Zeitbudget']  = $this->input['timeBudget'] . 'h';
		$summary['Mehlmenge']   = $this->total_flour . 'g';
		$summary['Hauptmehle']  = !empty($main) ? implode(', ', array_map(fn($id) => BrotarchitektData::get_flour_label($id), $main)) : '(keine)';
		$summary['Nebenmehle']  = !empty($side) ? implode(', ', array_map(fn($id) => BrotarchitektData::get_flour_label($id), $side)) : '(keine)';
		$summary['Triebmittel'] = $leavening_labels[$this->input['leavening']] ?? $this->input['leavening'];
		if ($this->input['leavening'] !== 'yeast') {
			$summary['ST-Typ']    = $st_labels[$this->input['sourdoughType']] ?? $this->input['sourdoughType'];
			$summary['ST bereit'] = $this->input['sourdoughReady'] === 'yes' ? 'Ja' : 'Nein';
		}
		$summary['Extras']      = !empty($extras) ? implode(', ', $extras) : '(keine)';
		$summary['Backmethode'] = $method_labels[$this->input['backMethod']] ?? $this->input['backMethod'];
		if (!empty($this->input['bakeFromFridge'])) {
			$summary['Kühlschrank'] = 'Direkt aus Kühlschrank backen';
		}
		return $summary;
	}

	public function __construct(array $input) {
		$defaults = array(
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
		);
		$this->input = array_merge($defaults, $input);

		$flour = max(250, min(1000, (int) $this->input['flourAmount']));
		if ($flour % 50 !== 0) {
			$flour = (int) (round($flour / 50) * 50);
		}
		$this->total_flour = $flour;

		$level = (int) $this->input['experienceLevel'];
		$this->level = ($level >= 1 && $level <= 5) ? $level : 2;

		$all_levels = BrotarchitektData::get_level_info();
		$this->level_info = isset($all_levels[$this->level]) ? $all_levels[$this->level] : $all_levels[2];
	}
}
