<?php
declare(strict_types=1);

class Calculator {

	public function calculate(array $input): array {
		$ctx = new RecipeContext($input);

		(new LeavenCalculator())->compute($ctx);
		(new FlourCalculator())->compute($ctx);

		$ingredients_builder = new IngredientsBuilder();
		$ingredients = $ingredients_builder->build($ctx);
		$timeline = (new TimelineBuilder())->build($ctx);
		$baking = new BakingProfile();

		return array(
			'name'        => $this->get_recipe_name($ctx),
			'meta'        => $this->get_recipe_meta($ctx, $ingredients_builder),
			'teaser'      => $this->get_recipe_teaser($ctx, $ingredients_builder),
			'ingredients' => $ingredients,
			'timeline'    => $timeline,
			'baking'      => $baking->get_instructions($ctx),
			'warnings'    => $this->get_warnings($ctx),
			'debug'       => array(
				'input'     => $ctx->get_input_summary(),
				'decisions' => $ctx->decisions,
			),
		);
	}

	private function get_recipe_name(RecipeContext $ctx): string {
		$h = (int) $ctx->input['timeBudget'];
		$speed = '';
		if ($h < 8) $speed = 'Schnelles';
		elseif ($h > 16) $speed = 'Langsam geführtes';

		$main = array_filter((array) $ctx->input['mainFlours']);
		$flour_names = array();
		$flours_js = BrotarchitektData::get_flours_for_js();
		foreach ($main as $id) {
			foreach ($flours_js as $f) {
				if ($f['id'] === $id) { $flour_names[] = $f['label']; break; }
			}
		}

		$flour_part = count($flour_names) === 1
			? preg_replace('/\s+\d+$/', '', $flour_names[0])
			: 'Mischbrot';

		$name = trim($speed . ' ' . $flour_part . '-brot');

		$extras = (array) $ctx->input['extras'];
		if (!empty($extras)) {
			$extra_names = array();
			foreach (BrotarchitektData::get_extras() as $key => $data) {
				if (in_array($key, $extras, true)) $extra_names[] = $data['name'];
			}
			$name .= ' mit ' . implode(' und ', $extra_names);
		}
		return $name;
	}

	private function get_recipe_meta(RecipeContext $ctx, IngredientsBuilder $ib): array {
		$back_labels = array('pot' => 'Topf', 'stone' => 'Pizzastein', 'steel' => 'Backstahl');
		$back = $ctx->input['backMethod'];
		return array(
			'level'  => $ctx->level_info['label'],
			'time'   => $ctx->input['timeBudget'] . ' h',
			'back'   => $back_labels[$back] ?? $back,
			'ta'     => $ctx->ta,
			'weight' => round($ctx->total_flour + $ctx->water_total + $ctx->total_flour * 0.02 + $ib->get_extras_weight($ctx)),
		);
	}

	private function get_recipe_teaser(RecipeContext $ctx, IngredientsBuilder $ib): array {
		return array(
			'ta'     => $ctx->ta,
			'weight' => round($ctx->total_flour + $ctx->water_total + $ctx->total_flour * 0.02 + $ib->get_extras_weight($ctx)),
		);
	}

	private function get_warnings(RecipeContext $ctx): array {
		$w = array();
		if ((int) $ctx->input['timeBudget'] < 8 && $ctx->input['leavening'] !== 'yeast') {
			$w[] = 'Dein Sauerteig muss bereits einsatzbereit sein.';
		}
		if ($ctx->rye_share >= 75) {
			$w[] = 'Roggenbrot mind. 24 Stunden vor dem Anschneiden ruhen lassen.';
		}
		return $w;
	}
}
