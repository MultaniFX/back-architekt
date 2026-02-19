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
		$leavening_labels = array(
			'yeast'     => Lang::get('summary_leav_yeast'),
			'sourdough' => Lang::get('summary_leav_sourdough'),
			'hybrid'    => Lang::get('summary_leav_hybrid'),
		);
		$st_labels = array(
			'rye'           => Lang::get('sourdough_rye'),
			'wheat'         => Lang::get('sourdough_wheat'),
			'spelt'         => Lang::get('sourdough_spelt'),
			'lievito_madre' => Lang::get('sourdough_lievito'),
		);
		$method_labels = array(
			'pot'   => Lang::get('method_pot_short'),
			'stone' => Lang::get('method_stone_short'),
			'steel' => Lang::get('method_steel_short'),
			'tray'  => Lang::get('method_tray_short'),
		);

		$main = array_filter((array) $this->input['mainFlours']);
		$side = array_filter((array) $this->input['sideFlours']);
		$extras = (array) $this->input['extras'];

		$summary = array();
		$summary[Lang::get('summary_level')]       = $this->level . ' (' . $this->level_info['label'] . ')';
		$summary[Lang::get('summary_time')]         = $this->input['timeBudget'] . 'h';
		$summary[Lang::get('summary_flour_amount')] = $this->total_flour . 'g';
		$summary[Lang::get('summary_main_flours')]  = !empty($main) ? implode(', ', array_map(fn($id) => BrotarchitektData::get_flour_label($id), $main)) : Lang::get('summary_none');
		$summary[Lang::get('summary_side_flours')]  = !empty($side) ? implode(', ', array_map(fn($id) => BrotarchitektData::get_flour_label($id), $side)) : Lang::get('summary_none');
		$summary[Lang::get('summary_leavening')]    = $leavening_labels[$this->input['leavening']] ?? $this->input['leavening'];
		if ($this->input['leavening'] !== 'yeast') {
			$summary[Lang::get('summary_st_type')]  = $st_labels[$this->input['sourdoughType']] ?? $this->input['sourdoughType'];
			$summary[Lang::get('summary_st_ready')] = $this->input['sourdoughReady'] === 'yes' ? Lang::get('summary_yes') : Lang::get('summary_no');
		}
		$summary[Lang::get('summary_extras')]       = !empty($extras) ? implode(', ', $extras) : Lang::get('summary_none');
		$summary[Lang::get('summary_method')]       = $method_labels[$this->input['backMethod']] ?? $this->input['backMethod'];
		if (!empty($this->input['bakeFromFridge'])) {
			$summary[Lang::get('summary_fridge')]   = Lang::get('summary_fridge_direct');
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
