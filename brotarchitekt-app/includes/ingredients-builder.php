<?php
declare(strict_types=1);

class IngredientsBuilder {

	private RecipeContext $ctx;

	public function build(RecipeContext $ctx): array {
		$this->ctx = $ctx;

		$flour_amounts = array();
		foreach ($ctx->flour_breakdown as $id => $pct) {
			$g = round($ctx->total_flour * $pct / 100, 0);
			if ($g > 0) $flour_amounts[$id] = $g;
		}

		$extras_weight = $this->get_extras_weight($ctx);
		$salt_base = $ctx->total_flour + $extras_weight;
		$salt = round($salt_base * 0.02, 0);
		$ctx->log('Ingredients', 'Salz', 'Salz: 2% von (' . $ctx->total_flour . 'g Mehl + ' . $extras_weight . 'g Extras = ' . $salt_base . 'g) = ' . $salt . 'g');
		$water_main = $ctx->water_total;
		$groups = array();

		// Sauerteig
		$sourdough_flour = 0;
		$sourdough_water = 0;
		$st_flour_grain = null;

		if ($ctx->sourdough_pct > 0) {
			$st_types = BrotarchitektData::get_sourdough_types();
			$st = $st_types[$ctx->input['sourdoughType']] ?? $st_types['rye'];
			$st_flour_grain = $st['flour_grain'];

			$sourdough_flour = round($ctx->total_flour * ($ctx->sourdough_pct / 100), 0);
			$sourdough_water = round($sourdough_flour * (($st['ta'] - 100) / 100), 0);
			$water_main -= $sourdough_water;

			$ctx->log('Ingredients', 'C.1: Sauerteig', 'ST-Mehl ' . $sourdough_flour . 'g (' . $ctx->sourdough_pct . '% von ' . $ctx->total_flour . 'g), ST-Wasser ' . $sourdough_water . 'g (TA ' . $st['ta'] . '), Hauptwasser -' . $sourdough_water . 'g');

			$st_total_g = $sourdough_flour + $sourdough_water;
			$groups['sourdough'] = array(
				'label' => 'Sauerteig',
				'items' => array(
					array('name' => 'Sauerteig (Anstellgut + Mehl + Wasser)', 'amount' => $st_total_g, 'unit' => 'g', 'percent' => $this->pct($st_total_g)),
				),
			);
		}

		// Kochstueck
		$kochstueck_mehl = 0;
		$kochstueck_water = 0;

		if ($ctx->has_kochstueck) {
			$kochstueck_mehl = round($ctx->total_flour * 0.04, 0);
			$kochstueck_water = $kochstueck_mehl * 5;
			$water_main -= $kochstueck_water;
			$this->subtract_flour_from_ancient($flour_amounts, $kochstueck_mehl);

			$ctx->log('Ingredients', 'E.1: Kochstueck', 'Mehl ' . $kochstueck_mehl . 'g (4%), Wasser ' . $kochstueck_water . 'g (1:5), Hauptwasser -' . $kochstueck_water . 'g');

			$groups['kochstueck'] = array(
				'label' => 'Kochstück (Tangzhong)',
				'items' => array(
					array('name' => 'Mehl', 'amount' => $kochstueck_mehl, 'unit' => 'g', 'percent' => $this->pct($kochstueck_mehl)),
					array('name' => 'Wasser', 'amount' => $kochstueck_water, 'unit' => 'g', 'percent' => $this->pct($kochstueck_water)),
				),
			);
		}

		// ST-Mehl abziehen
		if ($sourdough_flour > 0 && $st_flour_grain !== null) {
			$subtracted = $this->subtract_flour_from_grain($flour_amounts, $st_flour_grain, $sourdough_flour);
			if ($subtracted < $sourdough_flour && !empty($flour_amounts)) {
				$remaining = $sourdough_flour - $subtracted;
				foreach (array_keys($flour_amounts) as $id) {
					if ($remaining <= 0) break;
					$take = min($remaining, (float) $flour_amounts[$id]);
					$flour_amounts[$id] -= $take;
					$remaining -= $take;
					if ($flour_amounts[$id] <= 0) unset($flour_amounts[$id]);
				}
			}
		}

		// Extras / Bruehstueck
		$bruehstueck_items = $this->build_extras($ctx, $water_main);
		$water_main = $bruehstueck_items['water_main'];

		if (!empty($bruehstueck_items['items'])) {
			$groups['bruehstueck'] = array('label' => 'Brühstück', 'items' => $bruehstueck_items['items']);
		}

		// Hauptteig
		$water_main = max(0, round($water_main, 0));
		$yeast_total = $ctx->yeast_pct + $ctx->beginner_yeast_pct;
		$hefe_g = round($ctx->total_flour * $yeast_total / 100, 1);

		$ctx->log('Ingredients', 'Hauptteig', 'Restwasser ' . $water_main . 'g, Hefe ' . $hefe_g . 'g (' . $yeast_total . '%), Salz ' . $salt . 'g (2%)');

		$main_items = array();
		foreach ($flour_amounts as $id => $g) {
			if ($g <= 0) continue;
			$main_items[] = array('name' => BrotarchitektData::get_flour_label($id), 'amount' => $g, 'unit' => 'g', 'percent' => $this->pct($g));
		}
		$main_items[] = array('name' => 'Wasser', 'amount' => $water_main, 'unit' => 'g', 'percent' => $this->pct($water_main));
		if ($hefe_g > 0) {
			$main_items[] = array('name' => 'Hefe (frisch)', 'amount' => $hefe_g, 'unit' => 'g', 'percent' => $this->pct($hefe_g));
		}
		$main_items[] = array('name' => 'Salz', 'amount' => $salt, 'unit' => 'g', 'percent' => $this->pct($salt));

		$groups['main'] = array('label' => 'Hauptteig', 'items' => $main_items);

		return $groups;
	}

	public function get_extras_weight(RecipeContext $ctx): float {
		$extras = (array) $ctx->input['extras'];
		$extra_data = BrotarchitektData::get_extras();
		$kern_count = 0;
		$ta_raise_count = 0;
		foreach ($extras as $eid) {
			if (!isset($extra_data[$eid])) continue;
			if ($extra_data[$eid]['category'] === 'kern') $kern_count++;
			else $ta_raise_count++;
		}

		$max_kern = $ctx->level <= 3 ? 20 : 30;
		$weight = 0.0;
		foreach ($extras as $eid) {
			if (!isset($extra_data[$eid])) continue;
			$e = $extra_data[$eid];
			if ($e['category'] === 'kern') $pct = $kern_count === 1 ? 15 : $max_kern / $kern_count;
			else $pct = $ta_raise_count === 1 ? 10 : 5;
			$weight += $ctx->total_flour * $pct / 100;
		}
		$max_weight = $ctx->total_flour * 0.30;
		if ($weight > $max_weight) $weight = $max_weight;
		return $weight;
	}

	private function build_extras(RecipeContext $ctx, float $water_main): array {
		$extras = (array) $ctx->input['extras'];
		$extra_data = BrotarchitektData::get_extras();
		$items = array();
		if (empty($extras)) return array('items' => $items, 'water_main' => $water_main);

		$kern_count = 0;
		$ta_raise_count = 0;
		foreach ($extras as $eid) {
			if (!isset($extra_data[$eid])) continue;
			if ($extra_data[$eid]['category'] === 'kern') $kern_count++;
			else $ta_raise_count++;
		}

		$max_kern = $ctx->level <= 3 ? 20 : 30;
		$h = (int) $ctx->input['timeBudget'];
		$is_quick = $h <= 6;

		$total_pct = 0;
		foreach ($extras as $eid) {
			if (!isset($extra_data[$eid])) continue;
			if ($is_quick && $eid === 'grist') continue;
			$e = $extra_data[$eid];
			if ($e['category'] === 'kern') $total_pct += $kern_count === 1 ? 15 : $max_kern / $kern_count;
			else $total_pct += $ta_raise_count === 1 ? 10 : 5;
		}
		$extras_scale = $total_pct > 30 ? 30 / $total_pct : 1.0;

		foreach ($extras as $eid) {
			if (!isset($extra_data[$eid])) continue;
			$e = $extra_data[$eid];
			if ($is_quick && $eid === 'grist') continue;

			if ($e['category'] === 'kern') $pct = $kern_count === 1 ? 15 : $max_kern / $kern_count;
			else $pct = $ta_raise_count === 1 ? 10 : 5;
			$pct *= $extras_scale;
			$amount = round($ctx->total_flour * $pct / 100, 0);

			if ($is_quick) {
				if ($e['category'] === 'kern') {
					$items[] = array('name' => $e['name'] . ' (trocken einarbeiten)', 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct($amount));
				} else {
					$water_main += $amount;
					$items[] = array('name' => $e['name'] . ' (mit Mehl einarbeiten)', 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct($amount));
					$items[] = array('name' => 'Wasser (extra)', 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct($amount));
				}
			} elseif ($ctx->bruehstueck_available) {
				$water_extra = round($amount * $e['ratio'], 0);
				$water_main -= $water_extra;
				$items[] = array('name' => $e['name'], 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct($amount));
				$items[] = array('name' => 'Wasser (heiß)', 'amount' => $water_extra, 'unit' => 'g', 'percent' => $this->pct($water_extra));
			} else {
				$items[] = array('name' => $e['name'] . ' (trocken einarbeiten)', 'amount' => $amount, 'unit' => 'g', 'percent' => $this->pct($amount));
			}
		}

		return array('items' => $items, 'water_main' => $water_main);
	}

	private function pct(float $amount): float {
		return $this->ctx->total_flour > 0 ? round($amount / $this->ctx->total_flour * 100, 1) : 0;
	}

	private function subtract_flour_from_grain(array &$flour_amounts, string $grain, float $amount): float {
		$remaining = $amount;
		foreach (array_keys($flour_amounts) as $id) {
			if ($remaining <= 0) break;
			if (explode('_', $id, 2)[0] !== $grain) continue;
			$take = min($remaining, (float) $flour_amounts[$id]);
			$flour_amounts[$id] -= $take;
			$remaining -= $take;
			if ($flour_amounts[$id] <= 0) unset($flour_amounts[$id]);
		}
		return $amount - $remaining;
	}

	private function subtract_flour_from_ancient(array &$flour_amounts, float $amount): void {
		$ancient = array('spelt', 'emmer', 'einkorn', 'kamut');
		$remaining = $amount;
		foreach (array_keys($flour_amounts) as $id) {
			if ($remaining <= 0) break;
			if (!in_array(explode('_', $id, 2)[0], $ancient, true)) continue;
			$take = min($remaining, (float) $flour_amounts[$id]);
			$flour_amounts[$id] -= $take;
			$remaining -= $take;
			if ($flour_amounts[$id] <= 0) unset($flour_amounts[$id]);
		}
	}
}
