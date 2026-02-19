<?php
declare(strict_types=1);

class BakingProfile {

	public function get_duration(RecipeContext $ctx): int {
		$is_rye = $ctx->rye_share >= 50;
		$slot = $this->get_weight_slot($ctx->total_flour);
		$durations = array(
			'pot'   => $is_rye ? array(45, 55, 65) : array(40, 50, 60),
			'stone' => $is_rye ? array(45, 55, 65) : array(35, 45, 55),
			'steel' => $is_rye ? array(45, 55, 65) : array(35, 45, 55),
			'tray'  => $is_rye ? array(50, 60, 70) : array(45, 55, 65),
		);
		$method = $ctx->input['backMethod'];
		$key = isset($durations[$method]) ? $method : 'pot';
		$duration = $durations[$key][$slot];
		$ctx->log('Baking', 'F.8: Backdauer', 'Methode ' . $key . ', Roggen ' . ($is_rye ? '>=50%' : '<50%') . ', ' . Lang::get('ing_flour') . ' ' . $ctx->total_flour . 'g (Slot ' . $slot . ') → ' . $duration . ' min');
		return $duration;
	}

	public function get_preheat(RecipeContext $ctx): int {
		$method = $ctx->input['backMethod'];
		if ($method === 'pot') { $ctx->log('Baking', 'F.7: Vorheizzeit', Lang::get('method_pot_short') . ' → 40 min'); return 40; }
		if ($method === 'steel') { $ctx->log('Baking', 'F.7: Vorheizzeit', Lang::get('method_steel_short') . ' → 35 min'); return 35; }
		if ($method === 'tray') { $ctx->log('Baking', 'F.7: Vorheizzeit', Lang::get('method_tray_short') . ' → 20 min'); return 20; }
		$ctx->log('Baking', 'F.7: Vorheizzeit', Lang::get('method_stone_short') . ' → 50 min');
		return 50;
	}

	public function get_instructions(RecipeContext $ctx): string {
		$method = $ctx->input['backMethod'];
		$is_rye = $ctx->rye_share >= 50;
		$temp1 = $is_rye ? 230 : 250;
		$temp2 = $is_rye ? 215 : 230;
		$duration = $this->get_duration($ctx);

		if ($method === 'pot') {
			$text = Lang::get('bake_pot_template', $temp1, $temp2, $duration - 25);
		} elseif ($method === 'tray') {
			$schwaden_min = $is_rye ? 5 : 10;
			$text = Lang::get('bake_tray_template', $temp1, $schwaden_min, $temp2, $duration - $schwaden_min);
		} else {
			$schwaden_min = $is_rye ? 5 : 10;
			$text = Lang::get('bake_other_template', $temp1, $schwaden_min, $temp2, $duration - $schwaden_min);
		}

		if ($ctx->rye_share >= 75) {
			$text .= Lang::get('bake_rye_note');
		}
		return $text;
	}

	private function get_weight_slot(int $flour_g): int {
		if ($flour_g <= 600) return 0;
		if ($flour_g <= 800) return 1;
		return 2;
	}
}
