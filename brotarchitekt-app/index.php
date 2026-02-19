<?php
/**
 * Brotarchitekt — Standalone App
 * Startbar mit: php -S localhost:8000 -t brotarchitekt-app/
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/data.php';

Lang::load('de');

$flours   = json_encode(BrotarchitektData::get_flours_for_js(), JSON_UNESCAPED_UNICODE);
$extras   = json_encode(BrotarchitektData::get_extras_for_js(), JSON_UNESCAPED_UNICODE);
$levelInfo = json_encode(BrotarchitektData::get_level_info_for_js(), JSON_UNESCAPED_UNICODE);

// JS-Labels: alle UI-relevanten Strings für das Frontend
$jsLabels = json_encode(array(
	'level'     => array(
		1 => Lang::get('level_1'), 2 => Lang::get('level_2'),
		3 => Lang::get('level_3'), 4 => Lang::get('level_4'), 5 => Lang::get('level_5'),
	),
	'levelDesc' => array(
		1 => Lang::get('level_desc_1'), 2 => Lang::get('level_desc_2'),
		3 => Lang::get('level_desc_3'), 4 => Lang::get('level_desc_4'), 5 => Lang::get('level_desc_5'),
	),
	'vibe'      => array(
		'fast' => Lang::get('vibe_fast'), 'relaxed' => Lang::get('vibe_relaxed'),
		'cozy' => Lang::get('vibe_cozy'), 'overnight' => Lang::get('vibe_overnight'),
		'slow' => Lang::get('vibe_slow'),
	),
	'timeUnit'  => Lang::get('step1_time_unit'),
	'navNext'   => Lang::get('nav_next'),
	'navCreate' => Lang::get('nav_create_recipe'),
	'mainFlourHint'  => Lang::get('step2_main_flour_hint'),
	'mainFlourN'     => Lang::get('step2_main_flour_n'),
	'mainSelect'     => Lang::get('step2_main_select'),
	'sideFlourN'     => Lang::get('step2_side_flour_n'),
	'sideSelect'     => Lang::get('step2_side_select'),
	'extrasCounter'  => Lang::get('step3_counter'),
	'extrasCounterBs' => Lang::get('step3_counter_bs'),
	'warnQuick'      => Lang::get('step3_warn_quick'),
	'warnNoBs'       => Lang::get('step3_warn_no_bs'),
	'errorDefault'   => Lang::get('step5_error_default'),
	'tagFlour'       => Lang::get('tag_flour'),
	'tags' => array(
		'yeast' => Lang::get('tag_yeast'), 'sourdough' => Lang::get('tag_sourdough'), 'hybrid' => Lang::get('tag_hybrid'),
		'pot' => Lang::get('tag_pot'), 'stone' => Lang::get('tag_stone'), 'steel' => Lang::get('tag_steel'), 'tray' => Lang::get('tag_tray'),
	),
	'helpTa'              => Lang::get('help_ta'),
	'helpKochstueckTitle' => Lang::get('help_kochstueck_title'),
	'helpKochstueckText'  => Lang::get('help_kochstueck_text'),
	'helpKnead'           => Lang::get('help_knead'),
	'helpStockgare'       => Lang::get('help_stockgare'),
	'recipeMetricTa'     => Lang::get('recipe_metric_ta'),
	'recipeMetricWeight' => Lang::get('recipe_metric_weight'),
	'recipeMetricBake'   => Lang::get('recipe_metric_bake'),
	'recipeIngredients'  => Lang::get('recipe_ingredients'),
	'recipeTimeline'     => Lang::get('recipe_timeline'),
	'recipeBaking'       => Lang::get('recipe_baking'),
	'groupTitles' => array(
		'sourdough'   => Lang::get('group_sourdough'),
		'kochstueck'  => Lang::get('group_kochstueck'),
		'bruehstueck' => Lang::get('group_bruehstueck'),
		'main'        => Lang::get('group_main'),
	),
	'debugTitle'      => Lang::get('debug_title'),
	'debugInput'      => Lang::get('debug_input'),
	'debugDecisions'  => Lang::get('debug_decisions'),
	'debugColModule'  => Lang::get('debug_col_module'),
	'debugColRule'    => Lang::get('debug_col_rule'),
	'debugColResult'  => Lang::get('debug_col_result'),
), JSON_UNESCAPED_UNICODE);

$L = fn(string $key, mixed ...$args) => Lang::get($key, ...$args);
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $L('app_title') ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script src="assets/js/tailwind.config.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-bread-50 min-h-screen font-serif text-gray-800">

<div id="app" class="max-w-2xl mx-auto px-4 py-8">

	<!-- Header -->
	<header class="text-center mb-8">
		<h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?= $L('app_title') ?></h1>
		<p class="text-gray-600 text-lg"><?= $L('app_subtitle') ?></p>
	</header>

	<!-- Progress Steps -->
	<div class="flex items-center justify-center gap-0 mb-6 no-print" id="progress">
		<button data-progress="1" class="progress-step flex flex-col items-center relative" onclick="goStep(1)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-crust text-white">1</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block"><?= $L('step_zeit') ?></span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="1"></div>
		<button data-progress="2" class="progress-step flex flex-col items-center relative" onclick="goStep(2)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">2</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block"><?= $L('step_mehl') ?></span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="2"></div>
		<button data-progress="3" class="progress-step flex flex-col items-center relative" onclick="goStep(3)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">3</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block"><?= $L('step_extras') ?></span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="3"></div>
		<button data-progress="4" class="progress-step flex flex-col items-center relative" onclick="goStep(4)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">4</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block"><?= $L('step_backen') ?></span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="4"></div>
		<button data-progress="5" class="progress-step flex flex-col items-center relative" onclick="goStep(5)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">5</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block"><?= $L('step_rezept') ?></span>
		</button>
	</div>

	<!-- Summary Tags -->
	<div id="summary-tags" class="flex flex-wrap justify-center gap-2 mb-6 no-print"></div>

	<!-- ===================== STEP 1: Zeit & Erfahrung ===================== -->
	<section id="step-1" class="step-section">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1"><?= $L('step1_title') ?></h2>
		<p class="text-center text-gray-600 mb-4"><?= $L('step1_subtitle') ?></p>
		<p class="text-center text-sm text-gray-400 italic mb-6"><?= $L('step1_hero') ?></p>

		<div class="space-y-4">
			<!-- Zeitbudget -->
			<div class="bg-white rounded-xl p-5 shadow-sm border border-bread-200">
				<div class="flex items-center gap-3 mb-4">
					<span class="text-3xl">🕐</span>
					<div>
						<h3 class="font-bold text-gray-900"><?= $L('step1_time_title') ?></h3>
						<p class="text-sm text-gray-500"><?= $L('step1_time_subtitle') ?></p>
					</div>
				</div>
				<input type="range" id="time-slider" min="0" max="10" value="4" step="1">
				<div class="flex justify-between text-xs text-gray-400 mt-1 px-1" id="time-ticks"></div>
				<p class="text-center text-xl font-bold text-crust mt-3" id="time-value">12 <?= $L('step1_time_unit') ?></p>
				<p class="text-center text-sm text-gray-500 italic mt-1" id="time-vibe"><?= $L('vibe_cozy') ?></p>
				<p class="text-center text-xs text-gray-400 mt-2 hidden" id="cold-gare-hint"><?= $L('step1_cold_gare_hint') ?></p>
			</div>

			<!-- Kühlschrank -->
			<div id="fridge-card" class="bg-white rounded-xl p-5 shadow-sm border border-bread-200 hidden">
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-3">
						<span class="text-3xl">🧊</span>
						<div>
							<h3 class="font-bold text-gray-900"><?= $L('step1_fridge_title') ?></h3>
							<p class="text-sm text-gray-500"><?= $L('step1_fridge_desc') ?></p>
						</div>
					</div>
					<label class="toggle-switch flex-shrink-0 ml-4">
						<input type="checkbox" id="fridge-toggle">
						<span class="toggle-slider"></span>
					</label>
				</div>
			</div>

			<!-- Erfahrungslevel -->
			<div class="bg-white rounded-xl p-5 shadow-sm border border-bread-200">
				<div class="flex items-center gap-3 mb-4">
					<span class="text-3xl">⭐</span>
					<div>
						<h3 class="font-bold text-gray-900"><?= $L('step1_level_title') ?></h3>
						<p class="text-sm text-gray-500"><?= $L('step1_level_subtitle') ?></p>
					</div>
				</div>
				<input type="range" id="level-slider" min="1" max="5" value="2" step="1">
				<div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
					<span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
				</div>
				<div class="bg-bread-50 rounded-lg p-4 mt-3 text-center">
					<p class="font-bold text-gray-900" id="level-name"><?= $L('level_2') ?></p>
					<p class="text-sm text-gray-500" id="level-desc"><?= $L('level_desc_2') ?></p>
				</div>
			</div>
		</div>
	</section>

	<!-- ===================== STEP 2: Mehl & Triebmittel ===================== -->
	<section id="step-2" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1"><?= $L('step2_title') ?></h2>
		<p class="text-center text-gray-600 mb-6"><?= $L('step2_subtitle') ?></p>

		<!-- Triebmittel -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 block"><?= $L('step2_leavening_label') ?></label>
			<div class="grid grid-cols-3 gap-3" id="leavening-cards">
				<button type="button" data-value="yeast" class="card-transition bg-white rounded-xl p-4 border-2 border-crust shadow-sm text-center is-selected">
					<span class="text-3xl block mb-2">☁️</span>
					<span class="font-bold text-sm block"><?= $L('leav_yeast') ?></span>
					<span class="text-xs text-gray-500"><?= $L('leav_yeast_desc') ?></span>
				</button>
				<button type="button" data-value="sourdough" class="card-transition bg-white rounded-xl p-4 border-2 border-transparent hover:border-bread-300 shadow-sm text-center">
					<span class="text-3xl block mb-2">🫙</span>
					<span class="font-bold text-sm block"><?= $L('leav_sourdough') ?></span>
					<span class="text-xs text-gray-500"><?= $L('leav_sourdough_desc') ?></span>
				</button>
				<button type="button" data-value="hybrid" class="card-transition bg-white rounded-xl p-4 border-2 border-transparent hover:border-bread-300 shadow-sm text-center">
					<span class="text-3xl block mb-2">🫙☁️</span>
					<span class="font-bold text-sm block"><?= $L('leav_hybrid') ?></span>
					<span class="text-xs text-gray-500"><?= $L('leav_hybrid_desc') ?></span>
				</button>
			</div>
		</div>

		<!-- Sauerteig-Optionen -->
		<div id="sourdough-options" class="mb-6 hidden">
			<div class="bg-white rounded-xl p-5 shadow-sm border border-bread-200 mb-4">
				<h3 class="font-bold text-gray-900 mb-1"><?= $L('st_section_label') ?></h3>
				<p class="text-sm text-gray-500 mb-4"><?= $L('st_type_label') ?></p>
				<div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4" id="sourdough-type-chips">
					<button type="button" data-value="rye" class="card-transition rounded-xl px-3 py-3 text-center bg-crust text-white is-selected">
						<span class="font-bold text-sm block"><?= $L('st_rye') ?></span>
						<span class="text-xs opacity-80"><?= $L('st_rye_desc') ?></span>
					</button>
					<button type="button" data-value="wheat" class="card-transition rounded-xl px-3 py-3 text-center bg-bread-100 text-gray-700 hover:bg-bread-200">
						<span class="font-bold text-sm block"><?= $L('st_wheat') ?></span>
						<span class="text-xs text-gray-500"><?= $L('st_wheat_desc') ?></span>
					</button>
					<button type="button" data-value="spelt" class="card-transition rounded-xl px-3 py-3 text-center bg-bread-100 text-gray-700 hover:bg-bread-200">
						<span class="font-bold text-sm block"><?= $L('st_spelt') ?></span>
						<span class="text-xs text-gray-500"><?= $L('st_spelt_desc') ?></span>
					</button>
					<button type="button" data-value="lievito_madre" class="card-transition rounded-xl px-3 py-3 text-center bg-bread-100 text-gray-700 hover:bg-bread-200">
						<span class="font-bold text-sm block"><?= $L('st_lievito') ?></span>
						<span class="text-xs text-gray-500"><?= $L('st_lievito_desc') ?></span>
					</button>
				</div>
				<div class="flex items-center justify-between pt-3 border-t border-bread-100">
					<div>
						<span class="text-sm text-gray-700"><?= $L('st_ready_question') ?></span>
						<p class="text-xs text-gray-500 italic"><?= $L('st_ready_hint') ?></p>
					</div>
					<label class="toggle-switch flex-shrink-0 ml-4">
						<input type="checkbox" id="sourdough-ready" checked>
						<span class="toggle-slider"></span>
					</label>
				</div>
			</div>
			<div id="sourdough-warning" class="hidden mb-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>
			<div id="beginner-st-hint" class="hidden mb-3 bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg p-3"><?= $L('st_beginner_hint') ?></div>
		</div>

		<!-- Mehlverhältnis-Hinweis -->
		<div class="mb-6">
			<p class="text-sm text-gray-500 mb-2"><?= $L('step2_flour_ratio') ?></p>
			<button type="button" id="flour-modal-btn" class="inline-flex items-center gap-1 text-xs text-crust hover:text-crust-dark font-medium">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
				<?= $L('step2_flour_modal_title') ?>
			</button>
		</div>

		<!-- Hauptmehle -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2 block"><?= $L('step2_main_flour_label') ?></label>
			<p class="text-sm text-gray-500 mb-3" id="main-flour-hint"><?= $L('step2_main_flour_hint', 1, '') ?></p>
			<div id="main-flour-error" class="hidden mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3"><?= $L('step2_main_flour_error') ?></div>
			<div id="main-flours" class="space-y-3"></div>
		</div>

		<!-- Nebenmehle -->
		<div id="side-flours-wrap" class="mb-6 hidden">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2 block"><?= $L('step2_side_flour_label') ?> <span class="text-gray-400 normal-case"><?= $L('step2_side_flour_opt') ?></span></label>
			<div id="side-flours" class="space-y-3"></div>
		</div>

		<!-- Mehlmenge -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 block"><?= $L('step2_flour_amount') ?></label>
			<div class="flex items-center justify-center gap-6">
				<button type="button" id="flour-minus" class="w-12 h-12 rounded-full bg-bread-100 hover:bg-bread-200 text-xl font-bold text-gray-700 card-transition">−</button>
				<span class="text-3xl font-bold text-crust" id="flour-amount">500g</span>
				<button type="button" id="flour-plus" class="w-12 h-12 rounded-full bg-bread-100 hover:bg-bread-200 text-xl font-bold text-gray-700 card-transition">+</button>
			</div>
			<p class="text-center text-xs text-gray-500 mt-2"><?= $L('step2_flour_hint') ?></p>
		</div>

		<div id="rye-hint" class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>
	</section>

	<!-- Modal: Mischverhältnisse -->
	<div id="flour-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
		<div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl relative">
			<button type="button" id="flour-modal-close" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
			<h3 class="font-bold text-lg text-gray-900 mb-4"><?= $L('step2_flour_modal_title') ?></h3>
			<ul class="space-y-3 text-sm text-gray-700">
				<li class="flex gap-2"><span class="text-crust font-bold">1</span> <?= $L('step2_flour_modal_beginner') ?></li>
				<li class="flex gap-2"><span class="text-crust font-bold">2</span> <?= $L('step2_flour_modal_advanced') ?></li>
				<li class="flex gap-2"><span class="text-crust font-bold">3</span> <?= $L('step2_flour_modal_pro') ?></li>
			</ul>
			<p class="text-xs text-gray-500 mt-4 italic"><?= $L('step2_flour_modal_note') ?></p>
		</div>
	</div>

	<!-- ===================== STEP 3: Extras ===================== -->
	<section id="step-3" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1"><?= $L('step3_title') ?></h2>
		<p class="text-center text-gray-600 mb-6"><?= $L('step3_subtitle') ?></p>

		<div id="extras-warning" class="hidden mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>

		<div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="extras-grid"></div>

		<p class="text-center text-sm text-gray-500 mt-4" id="extras-counter"><?= $L('step3_counter', 0) ?></p>
		<p class="text-center text-sm text-gray-400 italic mt-2"><?= $L('step3_none_hint') ?></p>
		<p class="text-xs text-gray-400 mt-6 px-2 leading-relaxed hidden" id="extras-footer"><?= $L('step3_footer') ?></p>
	</section>

	<!-- ===================== STEP 4: Backmethode ===================== -->
	<section id="step-4" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1"><?= $L('step4_title') ?></h2>
		<p class="text-center text-gray-600 mb-6"><?= $L('step4_subtitle') ?></p>

		<div class="space-y-3" id="method-cards">
			<button type="button" data-value="pot" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-crust shadow-sm text-left flex items-start gap-4 relative is-selected">
				<span class="text-3xl">🍲</span>
				<div class="flex-1">
					<span class="font-bold block"><?= $L('method_pot') ?></span>
					<span class="text-sm text-gray-500"><?= $L('method_pot_desc') ?></span>
				</div>
				<span data-recommended="pot" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase"><?= $L('step4_recommended') ?></span>
			</button>
			<button type="button" data-value="stone" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-transparent hover:border-bread-300 shadow-sm text-left flex items-start gap-4 relative">
				<span class="text-3xl">🪨</span>
				<div class="flex-1">
					<span class="font-bold block"><?= $L('method_stone') ?></span>
					<span class="text-sm text-gray-500"><?= $L('method_stone_desc') ?></span>
				</div>
				<span data-recommended="stone" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase"><?= $L('step4_recommended') ?></span>
			</button>
			<button type="button" data-value="steel" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-transparent hover:border-bread-300 shadow-sm text-left flex items-start gap-4 relative">
				<span class="text-3xl">⬛</span>
				<div class="flex-1">
					<span class="font-bold block"><?= $L('method_steel') ?></span>
					<span class="text-sm text-gray-500"><?= $L('method_steel_desc') ?></span>
				</div>
				<span data-recommended="steel" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase"><?= $L('step4_recommended') ?></span>
			</button>
			<button type="button" data-value="tray" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-transparent hover:border-bread-300 shadow-sm text-left flex items-start gap-4 relative">
				<span class="text-3xl">🍳</span>
				<div class="flex-1">
					<span class="font-bold block"><?= $L('method_tray') ?></span>
					<span class="text-sm text-gray-500"><?= $L('method_tray_desc') ?></span>
				</div>
				<span data-recommended="tray" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase"><?= $L('step4_recommended') ?></span>
			</button>
		</div>

		<div class="bg-bread-50 rounded-lg p-4 mt-4 text-sm text-gray-700">
			🔥 <?= $L('step4_hint') ?>
		</div>
	</section>

	<!-- ===================== STEP 5: Rezept ===================== -->
	<section id="step-5" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1"><?= $L('step5_title') ?></h2>
		<p class="text-center text-gray-600 mb-6"><?= $L('step5_subtitle') ?></p>

		<!-- Empty state -->
		<div id="step-5-empty">
			<div id="step-5-error" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3"></div>
			<div class="bg-bread-50 rounded-xl p-8 text-center">
				<p class="text-gray-600 mb-4"><?= $L('step5_empty') ?></p>
				<button type="button" id="calculate-btn" class="bg-crust hover:bg-crust-dark text-white font-bold py-3 px-8 rounded-xl text-lg card-transition uppercase tracking-wide"><?= $L('step5_btn_create') ?></button>
			</div>
		</div>

		<!-- Result -->
		<div id="step-5-result" class="hidden">
			<h3 class="text-xl font-bold text-center text-gray-900 mb-3" id="recipe-title"></h3>
			<div class="flex flex-wrap justify-center gap-2 mb-4" id="recipe-tags"></div>

			<!-- Metric cards -->
			<div class="grid grid-cols-3 gap-3 mb-6" id="recipe-metrics"></div>

			<!-- Zutaten -->
			<div id="recipe-ingredients" class="mb-6"></div>

			<!-- Zeitplan -->
			<div id="recipe-timeline" class="mb-6"></div>

			<!-- Backhinweise -->
			<div id="recipe-baking" class="mb-6"></div>

			<!-- Debug -->
			<details class="mb-6" id="recipe-debug">
				<summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700"><?= $L('debug_title') ?></summary>
				<div class="mt-3 bg-gray-50 rounded-lg p-4 text-xs">
					<h4 class="font-bold mb-2"><?= $L('debug_input') ?></h4>
					<table class="w-full mb-4" id="debug-input"></table>
					<h4 class="font-bold mb-2"><?= $L('debug_decisions') ?></h4>
					<table class="w-full text-xs" id="debug-decisions">
						<thead><tr class="border-b"><th class="text-left py-1"><?= $L('debug_col_module') ?></th><th class="text-left py-1"><?= $L('debug_col_rule') ?></th><th class="text-left py-1"><?= $L('debug_col_result') ?></th></tr></thead>
						<tbody></tbody>
					</table>
				</div>
			</details>

			<!-- Actions -->
			<div class="flex gap-3 no-print">
				<button type="button" onclick="newRecipe()" class="flex-1 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition"><?= $L('nav_new_recipe') ?></button>
				<button type="button" onclick="window.print()" class="flex-1 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition"><?= $L('nav_print') ?></button>
			</div>
		</div>
	</section>

	<!-- Loading -->
	<div id="loading" class="hidden text-center py-12">
		<div class="inline-block w-8 h-8 border-4 border-bread-300 border-t-crust rounded-full animate-spin mb-4"></div>
		<p class="text-gray-600"><?= $L('step5_loading') ?></p>
	</div>

	<!-- Navigation -->
	<div class="flex gap-3 mt-8 no-print" id="nav-buttons">
		<button type="button" id="prev-btn" class="hidden flex-1 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition" onclick="prevStep()"><?= $L('nav_back') ?></button>
		<button type="button" id="next-btn" class="flex-1 py-3 rounded-xl bg-crust hover:bg-crust-dark text-white font-bold card-transition" onclick="nextStep()"><?= $L('nav_next') ?></button>
	</div>

</div>

<script>
const FLOURS = <?= $flours ?>;
const EXTRAS = <?= $extras ?>;
const LEVEL_INFO = <?= $levelInfo ?>;
const LABELS = <?= $jsLabels ?>;
const API_URL = 'api.php';

const TIME_STEPS = [4, 6, 8, 10, 12, 16, 20, 24, 30, 36, 48];

const EXTRA_ICONS = { sunflower: '🌻', pumpkin: '🎃', sesame: '⚪', linseed: '🌾', oatmeal: '🌿', old_bread: '🍞', grist: '🥜' };

const state = {
	step: 1, timeBudget: 12, experienceLevel: 2, bakeFromFridge: false,
	leavening: 'yeast', sourdoughType: 'rye', sourdoughReady: 'yes',
	flourAmount: 500, mainFlours: [], sideFlours: [], extras: [], backMethod: 'pot',
};

// ── Helpers ──
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function $(sel) { return document.querySelector(sel); }
function $$(sel) { return document.querySelectorAll(sel); }

// ── Vibe Label ──
function updateTimeVibe(h) {
	const el = $('#time-vibe');
	if (h <= 6) el.textContent = LABELS.vibe.fast;
	else if (h <= 8) el.textContent = LABELS.vibe.relaxed;
	else if (h <= 12) el.textContent = LABELS.vibe.cozy;
	else if (h <= 24) el.textContent = LABELS.vibe.overnight;
	else el.textContent = LABELS.vibe.slow;
}

// ── Progress ──
function updateProgress() {
	for (let i = 1; i <= 5; i++) {
		const btn = $(`[data-progress="${i}"]`);
		const circle = btn.querySelector('.step-circle');
		circle.className = 'step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold ';
		if (i < state.step) {
			circle.className += 'bg-amber-900 text-white';
			circle.innerHTML = '✓';
		} else if (i === state.step) {
			circle.className += 'bg-crust text-white';
			circle.textContent = i;
		} else {
			circle.className += 'bg-gray-300 text-gray-600';
			circle.textContent = i;
		}
		// Lines
		const line = $(`[data-line="${i}"]`);
		if (line) line.className = 'w-8 md:w-12 h-0.5 step-line ' + (i < state.step ? 'bg-amber-900' : 'bg-gray-300');
	}
}

// ── Summary Tags ──
function updateSummary() {
	const tags = [];
	if (state.step >= 2) {
		tags.push(state.timeBudget + ' h');
		tags.push(LABELS.level[state.experienceLevel] || 'Level ' + state.experienceLevel);
	}
	if (state.step >= 3) {
		tags.push(LABELS.tags[state.leavening] || state.leavening);
		tags.push(state.flourAmount + 'g ' + LABELS.tagFlour.replace('%dg ', ''));
	}
	if (state.step >= 5 && state.backMethod) {
		tags.push(LABELS.tags[state.backMethod] || state.backMethod);
	}
	$('#summary-tags').innerHTML = tags.map(t => `<span class="bg-bread-100 text-gray-700 text-xs font-medium px-3 py-1 rounded-full">${esc(t)}</span>`).join('');
}

// ── Step Navigation ──
function goStep(step) {
	if (step >= 3 && !validateStep2()) { goStep(2); showStep2Error(true); return; }
	showStep2Error(false);

	state.step = step;
	for (let i = 1; i <= 5; i++) {
		$(`#step-${i}`).classList.toggle('hidden', i !== step);
	}
	$('#loading').classList.add('hidden');
	$('#prev-btn').classList.toggle('hidden', step <= 1);
	$('#next-btn').classList.toggle('hidden', step >= 5);
	$('#nav-buttons').classList.toggle('hidden', step >= 5);

	$('#next-btn').innerHTML = step === 4 ? LABELS.navCreate : LABELS.navNext;

	updateProgress();
	updateSummary();

	if (step === 5) {
		syncState();
		fetchRecipe();
	}

	window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep() { goStep(Math.min(5, state.step + 1)); }
function prevStep() { goStep(Math.max(1, state.step - 1)); }

function newRecipe() {
	$('#step-5-empty').classList.remove('hidden');
	$('#step-5-result').classList.add('hidden');
	$('#nav-buttons').classList.remove('hidden');
	goStep(1);
}

function validateStep2() {
	const selects = $$('#main-flours select');
	return Array.from(selects).some(s => s.value);
}

function showStep2Error(show) {
	$('#main-flour-error').classList.toggle('hidden', !show);
}

function syncState() {
	state.mainFlours = Array.from($$('#main-flours select')).map(s => s.value).filter(Boolean);
	state.sideFlours = Array.from($$('#side-flours select')).map(s => s.value).filter(Boolean);
}

// ── Flour Selectors ──
function floursForMain(level) {
	const info = LEVEL_INFO[level];
	const allowAncient = info ? info.ancient_main : false;
	return FLOURS.filter(f => {
		if (f.category === 'ancient' && !allowAncient) return false;
		return f.level_main <= level;
	});
}

function floursForSide(level) {
	const info = LEVEL_INFO[level];
	const maxSide = info ? info.side_flours : 0;
	if (maxSide === 0) return [];
	const allowAncient = info ? info.ancient_side : false;
	return FLOURS.filter(f => {
		if (f.category === 'ancient' && !allowAncient) return false;
		return f.level_side <= level;
	});
}

function renderFlourSelectors() {
	const level = state.experienceLevel;
	const info = LEVEL_INFO[level] || LEVEL_INFO[2];
	const mainCount = info.main_flours || 1;
	const sideCount = info.side_flours || 0;

	$('#main-flour-hint').textContent = LABELS.mainFlourHint.replace('%d', mainCount).replace('%s', mainCount > 1 ? 'e' : '');

	// Main flours
	const mainEl = $('#main-flours');
	mainEl.innerHTML = '';
	const mainOptions = floursForMain(level);
	for (let i = 0; i < mainCount; i++) {
		const wrap = document.createElement('div');
		const label = document.createElement('label');
		label.className = 'text-sm text-gray-600 block mb-1';
		label.textContent = LABELS.mainFlourN.replace('%d', i + 1);
		const select = document.createElement('select');
		select.className = 'w-full border border-bread-200 rounded-lg px-3 py-2.5 bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-crust/30';
		select.innerHTML = '<option value="">' + esc(LABELS.mainSelect) + '</option>';
		const byGrain = {};
		mainOptions.forEach(f => { if (!byGrain[f.grain]) byGrain[f.grain] = []; byGrain[f.grain].push(f); });
		Object.keys(byGrain).forEach(grain => {
			const group = document.createElement('optgroup');
			group.label = byGrain[grain][0].label.split(' ')[0];
			byGrain[grain].forEach(f => { const opt = document.createElement('option'); opt.value = f.id; opt.textContent = f.label; group.appendChild(opt); });
			select.appendChild(group);
		});
		if (state.mainFlours[i]) select.value = state.mainFlours[i];
		select.addEventListener('change', () => {
			state.mainFlours[i] = select.value || null;
			state.mainFlours = state.mainFlours.filter(Boolean);
			showStep2Error(false);
			updateSummary();
		});
		wrap.appendChild(label);
		wrap.appendChild(select);
		mainEl.appendChild(wrap);
	}

	// Side flours
	const sideWrap = $('#side-flours-wrap');
	const sideEl = $('#side-flours');
	sideWrap.classList.toggle('hidden', sideCount === 0);
	sideEl.innerHTML = '';
	if (sideCount > 0) {
		const sideOptions = floursForSide(level);
		for (let i = 0; i < sideCount; i++) {
			const wrap = document.createElement('div');
			const label = document.createElement('label');
			label.className = 'text-sm text-gray-600 block mb-1';
			label.textContent = LABELS.sideFlourN.replace('%d', i + 1);
			const select = document.createElement('select');
			select.className = 'w-full border border-bread-200 rounded-lg px-3 py-2.5 bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-crust/30';
			select.innerHTML = '<option value="">' + esc(LABELS.sideSelect) + '</option>';
			const byGrain = {};
			sideOptions.forEach(f => { if (!byGrain[f.grain]) byGrain[f.grain] = []; byGrain[f.grain].push(f); });
			Object.keys(byGrain).forEach(grain => {
				const group = document.createElement('optgroup');
				group.label = byGrain[grain][0].label.split(' ')[0];
				byGrain[grain].forEach(f => { const opt = document.createElement('option'); opt.value = f.id; opt.textContent = f.label; group.appendChild(opt); });
				select.appendChild(group);
			});
			if (state.sideFlours[i]) select.value = state.sideFlours[i];
			select.addEventListener('change', () => {
				state.sideFlours[i] = select.value || null;
				state.sideFlours = state.sideFlours.filter(Boolean);
				updateSummary();
			});
			wrap.appendChild(label);
			wrap.appendChild(select);
			sideEl.appendChild(wrap);
		}
	}
}

// ── Extras Grid ──
function renderExtras() {
	const info = LEVEL_INFO[state.experienceLevel] || LEVEL_INFO[2];
	const maxExtras = info.max_extras || 5;
	const container = $('#extras-grid');
	container.innerHTML = '';

	EXTRAS.slice(0, 7).forEach(extra => {
		const btn = document.createElement('button');
		btn.type = 'button';
		const isSelected = state.extras.includes(extra.id);
		btn.className = 'card-transition rounded-xl p-5 text-center border-2 ' +
			(isSelected ? 'border-crust bg-white shadow-md -translate-y-0.5' : 'border-bread-200 bg-white hover:border-bread-300');
		btn.innerHTML = `<span class="text-3xl block mb-2">${EXTRA_ICONS[extra.id] || '•'}</span><span class="text-sm font-bold">${esc(extra.name)}</span>`;
		btn.addEventListener('click', () => {
			const idx = state.extras.indexOf(extra.id);
			if (idx >= 0) state.extras.splice(idx, 1);
			else if (state.extras.length < maxExtras) state.extras.push(extra.id);
			renderExtras();
			updateSummary();
		});
		container.appendChild(btn);
	});

	$('#extras-counter').textContent = state.extras.length > 0
		? LABELS.extrasCounterBs.replace('%d', state.extras.length)
		: LABELS.extrasCounter.replace('%d', state.extras.length);

	const footerEl = $('#extras-footer');
	if (footerEl) footerEl.classList.toggle('hidden', state.extras.length === 0);

	// Warnings
	const warnEl = $('#extras-warning');
	if (state.timeBudget <= 4) {
		warnEl.textContent = LABELS.warnQuick;
		warnEl.classList.remove('hidden');
	} else if (state.timeBudget >= 6 && state.timeBudget <= 8 && (state.leavening === 'sourdough' || state.leavening === 'hybrid')) {
		warnEl.textContent = LABELS.warnNoBs;
		warnEl.classList.remove('hidden');
	} else {
		warnEl.classList.add('hidden');
	}
}

// ── Back Method Recommended ──
function updateBackRecommended() {
	const info = LEVEL_INFO[state.experienceLevel] || LEVEL_INFO[2];
	const rec = info.recommended_back;
	$$('[data-recommended]').forEach(el => {
		el.classList.toggle('hidden', el.dataset.recommended !== rec);
	});
}

// ── Fetch Recipe ──
function fetchRecipe() {
	$('#step-5-empty').classList.add('hidden');
	$('#step-5-result').classList.add('hidden');
	$('#step-5-error').classList.add('hidden');
	$('#loading').classList.remove('hidden');
	$$('.step-section').forEach(el => el.classList.add('hidden'));

	const body = {
		timeBudget: state.timeBudget, experienceLevel: state.experienceLevel,
		bakeFromFridge: state.bakeFromFridge, leavening: state.leavening,
		sourdoughType: state.sourdoughType, sourdoughReady: state.sourdoughReady,
		flourAmount: state.flourAmount, mainFlours: state.mainFlours,
		sideFlours: state.sideFlours, extras: state.extras, backMethod: state.backMethod,
	};

	fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
		.then(res => {
			if (!res.ok) return res.json().then(j => Promise.reject(new Error(j.error || LABELS.errorDefault)));
			return res.json();
		})
		.then(recipe => {
			$('#loading').classList.add('hidden');
			$('#step-5').classList.remove('hidden');
			renderRecipe(recipe);
			$('#step-5-result').classList.remove('hidden');
			$('#nav-buttons').classList.add('hidden');
			updateProgress();
		})
		.catch(err => {
			$('#loading').classList.add('hidden');
			$('#step-5').classList.remove('hidden');
			$('#step-5-empty').classList.remove('hidden');
			$('#step-5-error').textContent = err.message || LABELS.errorDefault;
			$('#step-5-error').classList.remove('hidden');
		});
}

// ── Render Recipe ──
function renderRecipe(recipe) {
	$('#recipe-title').textContent = recipe.name || '';

	// Tags
	const meta = recipe.meta || {};
	const tags = [meta.level, meta.time, meta.back].filter(Boolean);
	$('#recipe-tags').innerHTML = tags.map((t, i) => `<span class="text-xs font-medium px-3 py-1 rounded-full ${i === 0 ? 'bg-crust text-white' : 'bg-bread-100 text-gray-700'}">${esc(t)}</span>`).join('');

	// Metric cards
	const teaser = recipe.teaser || {};
	const bakeStep = (recipe.timeline || []).find(s => (s.label || '').indexOf(LABELS.groupTitles.main ? 'Backen' : 'Backen') >= 0);
	const bakeDur = bakeStep ? (bakeStep.duration >= 60 ? Math.floor(bakeStep.duration / 60) + ' h' : bakeStep.duration + ' Min.') : '—';
	$('#recipe-metrics').innerHTML = `
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">⚖</span>
			<p class="text-xs text-gray-500">${esc(LABELS.recipeMetricTa)}</p>
			<p class="font-bold text-gray-900">TA ${teaser.ta || ''}</p>
			<p class="text-[10px] text-gray-400 mt-1 leading-snug">${esc(LABELS.helpTa)}</p>
		</div>
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">🌡</span>
			<p class="text-xs text-gray-500">${esc(LABELS.recipeMetricWeight)}</p>
			<p class="font-bold text-gray-900">${teaser.weight || ''}g</p>
		</div>
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">🕐</span>
			<p class="text-xs text-gray-500">${esc(LABELS.recipeMetricBake)}</p>
			<p class="font-bold text-gray-900">${bakeDur}</p>
		</div>`;

	// Ingredients
	const groups = recipe.ingredients || {};
	let ingHtml = '<h3 class="text-lg font-bold text-gray-900 mb-3">' + esc(LABELS.recipeIngredients) + '</h3>';
	Object.keys(groups).forEach(key => {
		const g = groups[key];
		const title = (LABELS.groupTitles[key] || g.label || '').toUpperCase();
		ingHtml += `<div class="mb-4"><h4 class="text-xs font-bold text-crust uppercase tracking-wider mb-2">${esc(title)}</h4>`;
		ingHtml += '<div class="bg-white rounded-lg border border-bread-200 divide-y divide-bread-100">';
		(g.items || []).forEach(item => {
			let amountStr = item.amount + ' ' + (item.unit || 'g');
			if (item.percent != null) amountStr += ' (' + item.percent + '%)';
			ingHtml += `<div class="flex justify-between px-4 py-2 text-sm"><span class="text-gray-800">${esc(item.name)}</span><span class="text-gray-600 font-medium">${amountStr}</span></div>`;
		});
		ingHtml += '</div></div>';
	});
	$('#recipe-ingredients').innerHTML = ingHtml;

	// Kochstück info (conditional: only when kochstueck group exists)
	if (groups.kochstueck) {
		$('#recipe-ingredients').innerHTML += `
			<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
				<h4 class="font-bold text-sm text-blue-900 mb-1">${esc(LABELS.helpKochstueckTitle)}</h4>
				<p class="text-xs text-blue-800 leading-relaxed">${esc(LABELS.helpKochstueckText)}</p>
			</div>`;
	}

	// Timeline
	let tlHtml = '<h3 class="text-lg font-bold text-gray-900 mb-3">' + esc(LABELS.recipeTimeline) + '</h3>';
	tlHtml += '<div class="space-y-0 relative">';
	(recipe.timeline || []).forEach((step, i) => {
		// Technique hints
		let hint = '';
		const lbl = (step.label || '').toLowerCase();
		if (lbl.indexOf('kneten') >= 0 || lbl.indexOf('knead') >= 0) {
			hint = `<p class="text-[10px] text-crust italic mt-1">${esc(LABELS.helpKnead)}</p>`;
		} else if (lbl.indexOf('stockgare') >= 0 && lbl.indexOf('kühlschrank') < 0) {
			hint = `<p class="text-[10px] text-crust italic mt-1">${esc(LABELS.helpStockgare)}</p>`;
		}

		tlHtml += `<div class="flex gap-4 pb-4 relative">
			<div class="flex flex-col items-center">
				<div class="w-3 h-3 rounded-full bg-crust border-2 border-white shadow-sm flex-shrink-0 mt-1.5"></div>
				${i < (recipe.timeline.length - 1) ? '<div class="w-0.5 flex-1 bg-bread-200 mt-1"></div>' : ''}
			</div>
			<div class="flex-1 pb-2">
				<div class="flex items-baseline gap-2">
					<span class="font-bold text-sm text-gray-900">${esc(step.time_formatted)}</span>
					<span class="text-sm text-gray-800">${esc(step.label)}</span>
					<span class="text-xs text-gray-500">(${esc(step.duration_formatted)})</span>
				</div>
				<p class="text-xs text-gray-500 mt-0.5">${esc(step.desc || '')}</p>
				${hint}
			</div>
		</div>`;
	});
	tlHtml += '</div>';
	$('#recipe-timeline').innerHTML = tlHtml;

	// Baking
	$('#recipe-baking').innerHTML = `<h3 class="text-lg font-bold text-gray-900 mb-3">${esc(LABELS.recipeBaking)}</h3><div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-gray-700">${esc(recipe.baking || '')}</div>`;

	// Debug
	if (recipe.debug) {
		$('#recipe-debug').classList.remove('hidden');
		let inputHtml = '';
		Object.entries(recipe.debug.input || {}).forEach(([key, val]) => {
			inputHtml += `<tr class="border-b border-gray-200"><td class="py-1 pr-3 font-bold">${esc(key)}</td><td class="py-1">${esc(String(val))}</td></tr>`;
		});
		$('#debug-input').innerHTML = inputHtml;
		const tbody = $('#debug-decisions tbody');
		tbody.innerHTML = (recipe.debug.decisions || []).map(d => `<tr class="border-b border-gray-100"><td class="py-1 pr-2">${esc(d.source)}</td><td class="py-1 pr-2">${esc(d.rule)}</td><td class="py-1">${esc(d.result)}</td></tr>`).join('');
	}
}

// ── Init ──
function init() {
	// Time slider (non-linear scale)
	const timeSlider = $('#time-slider');
	const timeTicks = $('#time-ticks');
	timeTicks.innerHTML = TIME_STEPS.map(h => `<span>${h}h</span>`).join('');

	function timeFromSlider(idx) { return TIME_STEPS[idx] || 12; }
	function sliderFromTime(h) { const idx = TIME_STEPS.indexOf(h); return idx >= 0 ? idx : 4; }

	timeSlider.min = 0;
	timeSlider.max = TIME_STEPS.length - 1;
	timeSlider.value = sliderFromTime(state.timeBudget);

	timeSlider.addEventListener('input', () => {
		state.timeBudget = timeFromSlider(parseInt(timeSlider.value));
		$('#time-value').textContent = state.timeBudget + ' ' + LABELS.timeUnit;
		updateTimeVibe(state.timeBudget);
		$('#fridge-card').classList.toggle('hidden', state.timeBudget < 12);
		$('#cold-gare-hint').classList.toggle('hidden', state.timeBudget < 12);
		updateSummary();
	});
	updateTimeVibe(state.timeBudget);
	$('#cold-gare-hint').classList.toggle('hidden', state.timeBudget < 12);

	// Level slider
	const levelSlider = $('#level-slider');
	levelSlider.addEventListener('input', () => {
		state.experienceLevel = parseInt(levelSlider.value);
		$('#level-name').textContent = LABELS.level[state.experienceLevel] || 'Level ' + state.experienceLevel;
		$('#level-desc').textContent = LABELS.levelDesc[state.experienceLevel] || '';
		renderFlourSelectors();
		renderExtras();
		updateBackRecommended();
		updateSummary();
	});

	// Fridge toggle
	$('#fridge-toggle').addEventListener('change', (e) => { state.bakeFromFridge = e.target.checked; updateSummary(); });

	// Leavening cards
	$$('#leavening-cards button').forEach(btn => {
		btn.addEventListener('click', () => {
			state.leavening = btn.dataset.value;
			$$('#leavening-cards button').forEach(c => {
				c.classList.toggle('border-crust', c === btn);
				c.classList.toggle('border-transparent', c !== btn);
				c.classList.toggle('is-selected', c === btn);
			});
			$('#sourdough-options').classList.toggle('hidden', state.leavening === 'yeast');
			$('#beginner-st-hint').classList.toggle('hidden', !(state.leavening === 'sourdough' && state.experienceLevel <= 2));
			renderExtras();
			updateSummary();
		});
	});

	// Sourdough type chips
	$$('#sourdough-type-chips button').forEach(btn => {
		btn.addEventListener('click', () => {
			state.sourdoughType = btn.dataset.value;
			$$('#sourdough-type-chips button').forEach(c => {
				const active = c === btn;
				c.classList.toggle('bg-crust', active);
				c.classList.toggle('text-white', active);
				c.classList.toggle('bg-bread-100', !active);
				c.classList.toggle('text-gray-700', !active);
				c.classList.toggle('is-selected', active);
				// Description text opacity
				const desc = c.querySelector('.text-xs');
				if (desc) {
					desc.classList.toggle('opacity-80', active);
					desc.classList.toggle('text-gray-500', !active);
				}
			});
		});
	});

	// Sourdough ready toggle
	$('#sourdough-ready').addEventListener('change', (e) => { state.sourdoughReady = e.target.checked ? 'yes' : 'no'; });

	// Flour amount stepper
	function setFlourAmount(v) {
		v = Math.max(250, Math.min(1000, Math.round(v / 50) * 50));
		state.flourAmount = v;
		$('#flour-amount').textContent = v + 'g';
		updateSummary();
	}
	$('#flour-minus').addEventListener('click', () => setFlourAmount(state.flourAmount - 50));
	$('#flour-plus').addEventListener('click', () => setFlourAmount(state.flourAmount + 50));

	// Method cards
	$$('#method-cards button').forEach(btn => {
		btn.addEventListener('click', () => {
			state.backMethod = btn.dataset.value;
			$$('#method-cards button').forEach(c => {
				c.classList.toggle('border-crust', c === btn);
				c.classList.toggle('border-transparent', c !== btn);
				c.classList.toggle('is-selected', c === btn);
			});
			updateBackRecommended();
			updateSummary();
		});
	});

	// Calculate button
	$('#calculate-btn').addEventListener('click', () => {
		syncState();
		if (!validateStep2()) { goStep(2); showStep2Error(true); return; }
		fetchRecipe();
	});

	// Flour ratio modal
	const flourModal = $('#flour-modal');
	$('#flour-modal-btn').addEventListener('click', () => { flourModal.classList.remove('hidden'); flourModal.classList.add('flex'); });
	$('#flour-modal-close').addEventListener('click', () => { flourModal.classList.add('hidden'); flourModal.classList.remove('flex'); });
	flourModal.addEventListener('click', (e) => { if (e.target === flourModal) { flourModal.classList.add('hidden'); flourModal.classList.remove('flex'); } });

	// Init render
	renderFlourSelectors();
	renderExtras();
	updateBackRecommended();
	updateProgress();
	updateSummary();
}

init();
</script>

</body>
</html>
