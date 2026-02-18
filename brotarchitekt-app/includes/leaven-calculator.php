<?php
declare(strict_types=1);

class LeavenCalculator {

	private const YEAST_PCT = array(
		'4-6h' => 1.25, '6-8h' => 0.85, '8-12h' => 0.5,
		'12-24h' => 0.2, '24-36h' => 0.075, '36-48h' => 0.04,
	);

	private const ST_PCT = array(
		'4-6h' => 17, '6-8h' => 20, '8-12h' => 12,
		'12-24h' => 9, '24-36h' => 7, '36-48h' => 6,
	);

	public function compute(RecipeContext $ctx): void {
		$this->compute_time_bucket($ctx);
		$this->compute_triebmittel($ctx);
		$this->compute_fridge($ctx);
	}

	private function compute_time_bucket(RecipeContext $ctx): void {
		$h = (int) $ctx->input['timeBudget'];
		if ($h <= 6) $ctx->time_bucket = '4-6h';
		elseif ($h <= 8) $ctx->time_bucket = '6-8h';
		elseif ($h <= 12) $ctx->time_bucket = '8-12h';
		elseif ($h <= 24) $ctx->time_bucket = '12-24h';
		elseif ($h <= 36) $ctx->time_bucket = '24-36h';
		else $ctx->time_bucket = '36-48h';
		$ctx->log('Leaven', 'D.1: Time-Bucket', $h . 'h → Bucket ' . $ctx->time_bucket);
	}

	private function compute_triebmittel(RecipeContext $ctx): void {
		$leavening = $ctx->input['leavening'];
		$bucket = $ctx->time_bucket;
		$ctx->sourdough_pct = 0;
		$ctx->yeast_pct = 0;
		$ctx->beginner_yeast_pct = 0;

		if ($leavening === 'yeast') {
			$ctx->yeast_pct = self::YEAST_PCT[$bucket];
			$ctx->log('Leaven', 'D.1: Nur Hefe', 'Hefe ' . $ctx->yeast_pct . '% (Bucket ' . $bucket . ')');
		} elseif ($leavening === 'sourdough' || $leavening === 'hybrid') {
			$ctx->sourdough_pct = self::ST_PCT[$bucket];
			if ($leavening === 'hybrid') {
				$ctx->sourdough_pct /= 2;
				$ctx->yeast_pct = self::YEAST_PCT[$bucket] / 2;
				$ctx->log('Leaven', 'D.2: Hybrid', 'ST ' . $ctx->sourdough_pct . '%, Hefe ' . $ctx->yeast_pct . '% (halbiert)');
			} else {
				$ctx->log('Leaven', 'D.1: Nur Sauerteig', 'ST ' . $ctx->sourdough_pct . '% (Bucket ' . $bucket . ')');
			}
			if ($ctx->level <= 2 && $leavening === 'sourdough') {
				$ctx->beginner_yeast_pct = 0.1;
				$ctx->log('Leaven', 'D.3: Anfaenger-Hefe', 'Level ' . $ctx->level . ' + ST pur → +0.1% Hefe');
			}
		}
	}

	private function compute_fridge(RecipeContext $ctx): void {
		$h = (int) $ctx->input['timeBudget'];
		$ctx->uses_fridge = $h >= 12;
		$ctx->log('Leaven', 'F.6: Kuehlschrank vorlaeufig', $h . 'h >= 12 → ' . ($ctx->uses_fridge ? 'Ja' : 'Nein') . ' (Roggen-Check folgt)');
	}
}
