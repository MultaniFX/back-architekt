<?php
declare(strict_types=1);

class FlourCalculator {

	private const ANCIENT_GRAINS = array('spelt', 'emmer', 'einkorn', 'kamut');
	private const TA_RAISE_EXTRAS = array('linseed', 'oatmeal', 'old_bread', 'grist');

	public function compute(RecipeContext $ctx): void {
		$this->compute_flour_breakdown($ctx);
		$this->compute_rye_share($ctx);
		$this->finalize_fridge($ctx);
		$this->compute_ta($ctx);
	}

	private function compute_flour_breakdown(RecipeContext $ctx): void {
		$main = array_filter((array) $ctx->input['mainFlours']);
		$side = array_filter((array) $ctx->input['sideFlours']);
		if (empty($main)) $main = array('wheat_1050');

		$main_count = count($main);
		$side_count = count($side);
		$ctx->flour_breakdown = array();

		if ($side_count === 0) {
			$pct = 100 / $main_count;
			foreach ($main as $id) $ctx->flour_breakdown[$id] = $pct;
		} else {
			$main_pct = 80 / $main_count;
			$side_pct = 20 / $side_count;
			foreach ($main as $id) $ctx->flour_breakdown[$id] = $main_pct;
			foreach ($side as $id) $ctx->flour_breakdown[$id] = $side_pct;
		}

		$grains = array();
		foreach (array_keys($ctx->flour_breakdown) as $id) {
			$grains[$this->get_grain($id)] = true;
		}
		$ctx->is_semola_only = count($grains) === 1 && isset($grains['semola']);

		$ctx->log('Flour', 'B.1: Mehlverteilung', implode(', ', array_map(fn($id, $pct) => $id . ' ' . round($pct, 1) . '%', array_keys($ctx->flour_breakdown), $ctx->flour_breakdown)));
	}

	private function compute_rye_share(RecipeContext $ctx): void {
		$ctx->rye_share = 0;
		foreach ($ctx->flour_breakdown as $id => $pct) {
			if ($this->get_grain($id) === 'rye') $ctx->rye_share += $pct;
		}
		$ctx->log('Flour', 'Roggenanteil', $ctx->rye_share . '%');
	}

	private function finalize_fridge(RecipeContext $ctx): void {
		if ($ctx->rye_share >= 75) {
			$ctx->uses_fridge = false;
			$ctx->log('Flour', 'F.6: Roggen >= 75%', 'Kuehlschrank deaktiviert (Roggen ' . $ctx->rye_share . '%)');
		}
	}

	private function compute_ta(RecipeContext $ctx): void {
		$ta_base = $ctx->level_info['ta_base'];
		$ta_max  = $ctx->level_info['ta_max'];

		if ($ctx->is_semola_only) {
			$ctx->ta = 172;
			$ctx->has_kochstueck = false;
			$ctx->has_ta_raise_bruehstueck = false;
			$ctx->bruehstueck_available = true;
			$ctx->water_total = $ctx->total_flour * (($ctx->ta - 100) / 100);
			$ctx->log('Flour', 'A.4: Semola-Sonderregel', 'TA fix 172, Wasser ' . round($ctx->water_total) . 'g');
			return;
		}

		$vk_share = 0;
		foreach ($ctx->flour_breakdown as $id => $pct) {
			if (strpos($id, '_Vollkorn') !== false) $vk_share += $pct;
		}
		if ($vk_share > 70) {
			$ta_base += 2;
			$ta_max  += 2;
			$ctx->log('Flour', 'A.3: Vollkorn > 70%', 'VK-Anteil ' . $vk_share . '% → TA-Basis +2 → ' . $ta_base);
		}

		$ctx->has_kochstueck = false;
		$main_flour_ids = array_filter((array) $ctx->input['mainFlours']);
		foreach ($main_flour_ids as $id) {
			if (in_array($this->get_grain($id), self::ANCIENT_GRAINS, true)) {
				$ctx->has_kochstueck = true;
				break;
			}
		}

		$ctx->has_ta_raise_bruehstueck = false;
		$extras = (array) $ctx->input['extras'];
		foreach ($extras as $e) {
			if (in_array($e, self::TA_RAISE_EXTRAS, true)) {
				$ctx->has_ta_raise_bruehstueck = true;
				break;
			}
		}

		$h = (int) $ctx->input['timeBudget'];
		$leavening = $ctx->input['leavening'];
		$ctx->bruehstueck_available = true;

		if ($h <= 6) {
			$ctx->bruehstueck_available = false;
			$ctx->log('Flour', 'E.3: Bruehstueck-Verfuegbarkeit', $h . 'h <= 6 → kein Bruehstueck');
		} elseif ($h <= 8 && $leavening !== 'yeast') {
			$ctx->bruehstueck_available = false;
			$ctx->log('Flour', 'E.3: Bruehstueck-Verfuegbarkeit', $h . 'h + ' . $leavening . ' → kein Bruehstueck (ST braucht Zeit)');
		} else {
			$ctx->log('Flour', 'E.3: Bruehstueck-Verfuegbarkeit', $h . 'h + ' . $leavening . ' → Bruehstueck verfuegbar');
		}

		if (!$ctx->bruehstueck_available) $ctx->has_ta_raise_bruehstueck = false;

		if ($ctx->level <= 3 && $ctx->has_ta_raise_bruehstueck) $ctx->has_kochstueck = false;

		$ctx->ta = $ta_base;
		if ($ctx->has_kochstueck) $ctx->ta += 5;
		if ($ctx->has_ta_raise_bruehstueck) $ctx->ta += 5;
		$ctx->ta = min($ctx->ta, $ta_max);

		$ctx->water_total = $ctx->total_flour * (($ctx->ta - 100) / 100);

		$ctx->log('Flour', 'A.1: TA-Berechnung', 'Basis ' . $ta_base . ' + Kochstueck ' . ($ctx->has_kochstueck ? '+5' : '0') . ' + Bruehstueck ' . ($ctx->has_ta_raise_bruehstueck ? '+5' : '0') . ' = ' . $ctx->ta . ' (max ' . $ta_max . ')');
		$ctx->log('Flour', 'Wasser-Berechnung', $ctx->total_flour . 'g * (' . $ctx->ta . ' - 100) / 100 = ' . round($ctx->water_total) . 'g');
	}

	private function get_grain(string $flour_id): string {
		return explode('_', $flour_id, 2)[0];
	}
}
