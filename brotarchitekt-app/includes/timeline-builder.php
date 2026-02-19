<?php
declare(strict_types=1);

class TimelineBuilder {

	private RecipeContext $ctx;
	private BakingProfile $baking;
	private array $steps = [];
	private int $t;

	public function build(RecipeContext $ctx): array {
		$this->ctx = $ctx;
		$this->baking = new BakingProfile();
		$this->steps = array();
		$this->t = 0;

		$leavening = $ctx->input['leavening'];
		$from_fridge = !empty($ctx->input['bakeFromFridge']);
		$time_budget_h = (int) $ctx->input['timeBudget'];

		$ctx->log('Timeline', Lang::get('summary_time'), $time_budget_h . 'h gesamt (inkl. ST-Vorbereitung)');

		$this->add_sourdough_steps();
		$this->add_parallel_steps();
		$this->add_fermentolyse();
		$this->add_kneading();
		$sf_minutes = $this->add_stretch_fold();

		$stockgare_total = $this->compute_stockgare_minutes();

		$ctx->log('Timeline', 'F.3: Stretch & Fold', 'S&F ' . $sf_minutes . ' min verbraucht');
		$ctx->log('Timeline', 'F.4: ' . Lang::get('tl_stockgare') . ' gesamt', $stockgare_total . ' min (davon S&F ' . $sf_minutes . ' min, Rest ' . max(0, $stockgare_total - $sf_minutes) . ' min)');

		if ($ctx->uses_fridge && !$from_fridge) {
			$ctx->log('Timeline', 'F.6: Gaervariante', 'Kalte Stockgare (Normal)');
			$this->build_cold_stock($stockgare_total, $sf_minutes, $time_budget_h);
		} elseif ($ctx->uses_fridge && $from_fridge) {
			$ctx->log('Timeline', 'F.6: Gaervariante', 'Kalte Stueckgare (Direkt)');
			$this->build_cold_proof($stockgare_total, $sf_minutes, $time_budget_h);
		} else {
			$ctx->log('Timeline', 'F.6: Gaervariante', 'Warm only');
			$this->build_warm_only($stockgare_total, $sf_minutes);
		}

		$this->add_baking();

		foreach ($this->steps as &$s) {
			$elapsed_h = floor($s['time'] / 3600);
			$elapsed_m = floor(($s['time'] % 3600) / 60);
			$s['time_formatted'] = sprintf('%d:%02d', $elapsed_h, $elapsed_m);
			$s['duration_formatted'] = $s['duration'] >= 60
				? (floor($s['duration'] / 60) . ' h ' . ($s['duration'] % 60 ? $s['duration'] % 60 . ' min' : ''))
				: ($s['duration'] . ' min');
		}
		unset($s);
		return $this->steps;
	}

	private function add_sourdough_steps(): void {
		$ctx = $this->ctx;
		$leavening = $ctx->input['leavening'];
		$st_ready = $ctx->input['sourdoughReady'] === 'yes';
		$time_budget_h = (int) $ctx->input['timeBudget'];

		if ($leavening === 'yeast') return;
		if ($st_ready) { $ctx->log('Timeline', 'C.6: ST bereit', Lang::get('tl_sourdough_ready')); return; }

		if ($time_budget_h >= 8) {
			$this->step(Lang::get('tl_sourdough_refresh'), 240, Lang::get('tl_sourdough_refresh_desc'));
		}

		if ($time_budget_h >= 24) $st_duration = 720;
		elseif ($time_budget_h >= 12) $st_duration = 480;
		elseif ($time_budget_h >= 8) $st_duration = 240;
		else $st_duration = 360;

		$this->step(Lang::get('tl_sourdough_set'), $st_duration, Lang::get('tl_sourdough_set_desc'), false);
	}

	private function add_parallel_steps(): void {
		$ctx = $this->ctx;
		$leavening = $ctx->input['leavening'];
		$extras = (array) $ctx->input['extras'];
		$has_st_step = $leavening !== 'yeast' && $ctx->input['sourdoughReady'] !== 'yes';
		$has_vorteig = false;

		if (!empty($extras) && $ctx->bruehstueck_available) {
			$has_vorteig = true;
			if ($has_st_step) {
				$this->step_parallel(Lang::get('tl_bruehstueck'), 120, Lang::get('tl_bruehstueck_desc'));
			} else {
				$this->step(Lang::get('tl_bruehstueck'), 120, Lang::get('tl_bruehstueck_desc'));
			}
		}

		if ($ctx->has_kochstueck) {
			if ($has_st_step) {
				$this->step_parallel(Lang::get('tl_kochstueck'), 120, Lang::get('tl_kochstueck_desc'));
			} elseif (!$has_vorteig) {
				$this->step(Lang::get('tl_kochstueck'), 120, Lang::get('tl_kochstueck_desc'));
			} else {
				$this->step_parallel(Lang::get('tl_kochstueck'), 120, Lang::get('tl_kochstueck_desc'));
			}
			$has_vorteig = true;
		}

		if ($has_st_step) $this->advance_past_parallel();
	}

	private function add_fermentolyse(): void {
		if (!$this->needs_fermentolyse()) return;
		$this->step(Lang::get('tl_fermentolyse'), 15, Lang::get('tl_fermentolyse_desc'));
	}

	private function add_kneading(): void {
		if ($this->ctx->rye_share >= 75) {
			$this->step(Lang::get('tl_knead_rye'), 4, Lang::get('tl_knead_rye_desc'));
		} else {
			$this->step(Lang::get('tl_knead'), 12, Lang::get('tl_knead_desc'));
		}
	}

	private function add_stretch_fold(): int {
		if ($this->ctx->rye_share >= 75) return 0;
		for ($i = 1; $i <= 3; $i++) {
			$this->step(Lang::get('tl_stretch_fold', $i), 15, Lang::get('tl_stretch_fold_desc'));
		}
		return 45;
	}

	private function build_warm_only(int $stockgare_total, int $sf_minutes): void {
		$ctx = $this->ctx;
		$rest = max(0, $stockgare_total - $sf_minutes);
		if ($rest > 0) {
			$label = $sf_minutes > 0 ? Lang::get('tl_stockgare_rest') : Lang::get('tl_stockgare');
			$this->step($label, $rest, Lang::get('tl_stockgare_desc'));
		}
		$this->add_forming();
		$stueck_min = $ctx->rye_share >= 75 ? 150 : 90;
		$desc = $ctx->rye_share >= 75
			? Lang::get('tl_stueckgare_rye_desc')
			: Lang::get('tl_stueckgare_desc');
		$this->step(Lang::get('tl_stueckgare'), $stueck_min, $desc);
	}

	private function build_cold_stock(int $stockgare_total, int $sf_minutes, int $time_budget_h): void {
		$ctx = $this->ctx;
		$elapsed_min = (int) ($this->t / 60);
		$formen_min = 10;
		$akklim_min = 30;
		$stueckgare_min = 120;
		$preheat_min = $this->baking->get_preheat($ctx);
		$bake_min = $this->baking->get_duration($ctx);
		$preheat_extra = max(0, $preheat_min - $stueckgare_min);
		$budget_min = $time_budget_h * 60;
		$fixed_after = $formen_min + $akklim_min + $stueckgare_min + $preheat_extra + $bake_min;
		$cold_estimate = $budget_min - $elapsed_min - $fixed_after - 120;
		$cold_hours_raw = max(8, (int) floor($cold_estimate / 60));
		$anspring_min = $this->compute_anspringzeit($time_budget_h, $cold_hours_raw);
		$anspring_rest = max(0, $anspring_min - $sf_minutes);
		$available_for_cold = $budget_min - $elapsed_min - $anspring_rest - $fixed_after;
		$cold_hours = max(8, (int) floor($available_for_cold / 60));

		if ($anspring_rest > 0) {
			$this->step(Lang::get('tl_anspring'), $anspring_rest, Lang::get('tl_anspring_desc'));
		}
		$this->step(Lang::get('tl_cold_stock'), $cold_hours * 60, Lang::get('tl_cold_stock_desc', $cold_hours));
		$this->step(Lang::get('tl_acclimatize'), $akklim_min, Lang::get('tl_acclimatize_desc'));
		$this->add_forming();
		$this->step(Lang::get('tl_stueckgare_warm'), $stueckgare_min, Lang::get('tl_stueckgare_warm_desc'));
	}

	private function build_cold_proof(int $stockgare_total, int $sf_minutes, int $time_budget_h): void {
		$rest = max(0, $stockgare_total - $sf_minutes);
		if ($rest > 0) {
			$this->step(Lang::get('tl_stockgare_rest'), $rest, Lang::get('tl_stockgare_desc'));
		}
		$this->step(Lang::get('tl_form'), 10, Lang::get('tl_form_cold_desc'));
		$cold_hours = max(8, $time_budget_h - 6);
		$this->step(Lang::get('tl_cold_proof'), $cold_hours * 60, Lang::get('tl_cold_proof_desc', $cold_hours));
	}

	private function add_baking(): void {
		$ctx = $this->ctx;
		$preheat = $this->baking->get_preheat($ctx);
		$preheat_start = max(0, $this->t - $preheat * 60);
		$method = $ctx->input['backMethod'];
		$preheat_desc = $method === 'pot'
			? Lang::get('tl_preheat_pot_desc')
			: ($method === 'tray' ? Lang::get('tl_preheat_tray_desc') : Lang::get('tl_preheat_other_desc'));

		$this->steps[] = array('time' => $preheat_start, 'label' => Lang::get('tl_preheat'), 'duration' => $preheat, 'desc' => $preheat_desc);
		$bake_min = $this->baking->get_duration($ctx);
		$this->step(Lang::get('tl_bake'), $bake_min, Lang::get('tl_bake_desc'));
		$this->steps[] = array(
			'time' => $this->t, 'label' => Lang::get('tl_cool'), 'duration' => 45,
			'desc' => $ctx->rye_share >= 75
				? Lang::get('tl_cool_rye_desc')
				: Lang::get('tl_cool_desc'),
		);
	}

	private function compute_stockgare_minutes(): int {
		$ctx = $this->ctx;
		$result = 0;

		if ($ctx->rye_share >= 75) {
			if ($ctx->sourdough_pct >= 40) $result = 30;
			elseif ($ctx->sourdough_pct >= 25) $result = 60;
			else $result = 120;
		} else {
			$has_st = $ctx->sourdough_pct > 0;
			$has_hefe = $ctx->yeast_pct > 0 || $ctx->beginner_yeast_pct > 0;

			if ($has_st && !$has_hefe) {
				if ($ctx->sourdough_pct >= 20) $result = 150;
				elseif ($ctx->sourdough_pct >= 15) $result = 210;
				elseif ($ctx->sourdough_pct >= 10) $result = 270;
				else $result = 330;
			} elseif ($has_st && $has_hefe) {
				if ($ctx->sourdough_pct >= 20) $result = 120;
				elseif ($ctx->sourdough_pct >= 15) $result = 180;
				elseif ($ctx->sourdough_pct >= 10) $result = 240;
				elseif ($ctx->sourdough_pct >= 7.5) $result = 300;
				else $result = 360;
			} else {
				if ($ctx->yeast_pct >= 1.0) $result = 105;
				elseif ($ctx->yeast_pct >= 0.3) $result = 180;
				else $result = 300;
			}
		}
		return $result;
	}

	private function compute_anspringzeit(int $time_budget_h, int $cold_hours): int {
		if ($cold_hours >= 16) return 60;
		if ($cold_hours >= 12) return 90;
		return 120;
	}

	private function needs_fermentolyse(): bool {
		$ancient_share = 0;
		$vollkorn_share = 0;
		foreach ($this->ctx->flour_breakdown as $id => $pct) {
			$grain = explode('_', $id, 2)[0];
			if (in_array($grain, array('spelt', 'emmer', 'einkorn', 'kamut'), true)) $ancient_share += $pct;
			if (strpos($id, '_Vollkorn') !== false) $vollkorn_share += $pct;
		}
		return $ancient_share >= 60 || $vollkorn_share >= 60;
	}

	private function step(string $label, int $duration_min, string $desc, bool $advance = true): void {
		$this->steps[] = array('time' => $this->t, 'label' => $label, 'duration' => $duration_min, 'desc' => $desc);
		if ($advance) $this->t += $duration_min * 60;
	}

	private function step_parallel(string $label, int $duration_min, string $desc): void {
		$this->step($label, $duration_min, $desc, false);
	}

	private function advance_past_parallel(): void {
		if (empty($this->steps)) return;
		$max_end = $this->t;
		foreach ($this->steps as $s) {
			$end = $s['time'] + $s['duration'] * 60;
			if ($end > $max_end) $max_end = $end;
		}
		$this->t = $max_end;
	}

	private function add_forming(): void {
		$desc = $this->ctx->rye_share >= 75
			? Lang::get('tl_form_rye_desc')
			: Lang::get('tl_form_desc');
		$this->step(Lang::get('tl_form'), 10, $desc);
	}
}
