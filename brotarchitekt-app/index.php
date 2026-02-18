<?php
/**
 * Brotarchitekt — Standalone App
 * Startbar mit: php -S localhost:8000 -t brotarchitekt-app/
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$flours   = json_encode(BrotarchitektData::get_flours_for_js(), JSON_UNESCAPED_UNICODE);
$extras   = json_encode(BrotarchitektData::get_extras_for_js(), JSON_UNESCAPED_UNICODE);
$levelInfo = json_encode(BrotarchitektData::get_level_info_for_js(), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Der Brot-Architekt</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						bread: {
							50:  '#faf7f2',
							100: '#f5f0e8',
							200: '#ede4d4',
							300: '#e0d1b8',
							400: '#d4b88e',
							500: '#c87137',
							600: '#b5622e',
							700: '#a05228',
							800: '#7a3f20',
							900: '#5d3419',
						},
						crust: {
							DEFAULT: '#E35C3C',
							dark:    '#c94e32',
							light:   '#f0826a',
						},
					},
					fontFamily: {
						serif: ['Georgia', 'Cambria', '"Times New Roman"', 'serif'],
					},
				},
			},
		}
	</script>
	<style>
		/* Slider styles */
		input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 10px; border-radius: 5px; background: #e0d1b8; outline: none; }
		input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 28px; height: 28px; border-radius: 50%; background: #E35C3C; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(44,34,24,0.2); cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
		input[type="range"]::-webkit-slider-thumb:hover { transform: scale(1.1); box-shadow: 0 3px 12px rgba(44,34,24,0.3); }
		input[type="range"]::-moz-range-thumb { width: 28px; height: 28px; border-radius: 50%; background: #E35C3C; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(44,34,24,0.2); cursor: pointer; }
		input[type="range"]::-moz-range-track { height: 10px; border-radius: 5px; background: #e0d1b8; }

		/* Toggle switch */
		.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
		.toggle-switch input { opacity: 0; width: 0; height: 0; }
		.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 26px; transition: 0.3s; }
		.toggle-slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
		.toggle-switch input:checked + .toggle-slider { background: #E35C3C; }
		.toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }

		/* Print */
		@media print {
			.no-print { display: none !important; }
			body { background: white !important; }
		}

		/* Smooth transitions */
		.card-transition { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
	</style>
</head>
<body class="bg-bread-50 min-h-screen font-serif text-gray-800">

<div id="app" class="max-w-2xl mx-auto px-4 py-8">

	<!-- Header -->
	<header class="text-center mb-8">
		<h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Der Brot-Architekt</h1>
		<p class="text-gray-600 text-lg">Bau dir dein eigenes Brot — Schritt für Schritt.</p>
	</header>

	<!-- Progress Steps -->
	<div class="flex items-center justify-center gap-0 mb-6 no-print" id="progress">
		<button data-progress="1" class="progress-step flex flex-col items-center relative" onclick="goStep(1)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-crust text-white">1</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block">Zeit</span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="1"></div>
		<button data-progress="2" class="progress-step flex flex-col items-center relative" onclick="goStep(2)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">2</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block">Mehl</span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="2"></div>
		<button data-progress="3" class="progress-step flex flex-col items-center relative" onclick="goStep(3)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">3</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block">Extras</span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="3"></div>
		<button data-progress="4" class="progress-step flex flex-col items-center relative" onclick="goStep(4)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">4</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block">Backen</span>
		</button>
		<div class="w-8 md:w-12 h-0.5 bg-gray-300 step-line" data-line="4"></div>
		<button data-progress="5" class="progress-step flex flex-col items-center relative" onclick="goStep(5)">
			<span class="step-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-300 text-gray-600">5</span>
			<span class="text-xs mt-1 text-gray-600 hidden md:block">Rezept</span>
		</button>
	</div>

	<!-- Summary Tags -->
	<div id="summary-tags" class="flex flex-wrap justify-center gap-2 mb-6 no-print"></div>

	<!-- ===================== STEP 1: Zeit & Erfahrung ===================== -->
	<section id="step-1" class="step-section">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Zeit & Erfahrung</h2>
		<p class="text-center text-gray-600 mb-6">Wie viel Zeit hast du und wie erfahren bist du?</p>

		<div class="space-y-4">
			<!-- Zeitbudget -->
			<div class="bg-white rounded-xl p-5 shadow-sm border border-bread-200">
				<div class="flex items-center gap-3 mb-4">
					<span class="text-3xl">🕐</span>
					<div>
						<h3 class="font-bold text-gray-900">Zeitbudget</h3>
						<p class="text-sm text-gray-500">Von Teig bis fertiges Brot</p>
					</div>
				</div>
				<input type="range" id="time-slider" min="4" max="48" value="12" step="1">
				<div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
					<span>4h</span><span>8h</span><span>12h</span><span>24h</span><span>48h</span>
				</div>
				<p class="text-center text-xl font-bold text-crust mt-3" id="time-value">12 Stunden</p>
				<p class="text-center text-sm text-gray-500 italic mt-1" id="time-vibe">Gemütliches Tagesbrot</p>
			</div>

			<!-- Kühlschrank -->
			<div id="fridge-card" class="bg-white rounded-xl p-5 shadow-sm border border-bread-200 hidden">
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-3">
						<span class="text-3xl">🧊</span>
						<div>
							<h3 class="font-bold text-gray-900">Direkt aus dem Kühlschrank backen?</h3>
							<p class="text-sm text-gray-500">Brot formen, über Nacht kühlen, am nächsten Tag direkt backen</p>
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
						<h3 class="font-bold text-gray-900">Erfahrungslevel</h3>
						<p class="text-sm text-gray-500">Beeinflusst die verfügbaren Optionen</p>
					</div>
				</div>
				<input type="range" id="level-slider" min="1" max="5" value="2" step="1">
				<div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
					<span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
				</div>
				<div class="bg-bread-50 rounded-lg p-4 mt-3 text-center">
					<p class="font-bold text-gray-900" id="level-name">Grundkenntnisse</p>
					<p class="text-sm text-gray-500" id="level-desc">Einige Brote gebacken</p>
				</div>
			</div>
		</div>
	</section>

	<!-- ===================== STEP 2: Mehl & Triebmittel ===================== -->
	<section id="step-2" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Mehl & Triebmittel</h2>
		<p class="text-center text-gray-600 mb-6">Dein Teig: Triebmittel & Mehlauswahl</p>

		<!-- Triebmittel -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 block">Triebmittel</label>
			<div class="grid grid-cols-3 gap-3" id="leavening-cards">
				<button type="button" data-value="yeast" class="card-transition bg-white rounded-xl p-4 border-2 border-crust shadow-sm text-center is-selected">
					<span class="text-3xl block mb-2">☁️</span>
					<span class="font-bold text-sm block">Hefe</span>
					<span class="text-xs text-gray-500">Einfach & zuverlässig</span>
				</button>
				<button type="button" data-value="sourdough" class="card-transition bg-white rounded-xl p-4 border-2 border-transparent hover:border-bread-300 shadow-sm text-center">
					<span class="text-3xl block mb-2">🫙</span>
					<span class="font-bold text-sm block">Sauerteig</span>
					<span class="text-xs text-gray-500">Mehr Aroma</span>
				</button>
				<button type="button" data-value="hybrid" class="card-transition bg-white rounded-xl p-4 border-2 border-transparent hover:border-bread-300 shadow-sm text-center">
					<span class="text-3xl block mb-2">🫙☁️</span>
					<span class="font-bold text-sm block">Beides</span>
					<span class="text-xs text-gray-500">Beste beider Welten</span>
				</button>
			</div>
		</div>

		<!-- Sauerteig-Optionen -->
		<div id="sourdough-options" class="mb-6 hidden">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 block">Sauerteig-Typ</label>
			<div class="flex flex-wrap gap-2 mb-4" id="sourdough-type-chips">
				<button type="button" data-value="rye" class="card-transition px-4 py-2 rounded-lg text-sm font-medium bg-crust text-white is-selected">Roggen-ST</button>
				<button type="button" data-value="wheat" class="card-transition px-4 py-2 rounded-lg text-sm font-medium bg-bread-100 text-gray-700 hover:bg-bread-200">Weizen-ST</button>
				<button type="button" data-value="spelt" class="card-transition px-4 py-2 rounded-lg text-sm font-medium bg-bread-100 text-gray-700 hover:bg-bread-200">Dinkel-ST</button>
				<button type="button" data-value="lievito_madre" class="card-transition px-4 py-2 rounded-lg text-sm font-medium bg-bread-100 text-gray-700 hover:bg-bread-200">Lievito Madre</button>
			</div>
			<div class="flex items-center justify-between bg-white rounded-xl p-4 border border-bread-200 mb-3">
				<span class="text-sm text-gray-700">Ist dein Sauerteig einsatzbereit?</span>
				<label class="toggle-switch flex-shrink-0">
					<input type="checkbox" id="sourdough-ready" checked>
					<span class="toggle-slider"></span>
				</label>
			</div>
			<p class="text-xs text-gray-500 italic">Aktiv und innerhalb der letzten 12h gefüttert</p>
			<div id="sourdough-warning" class="hidden mt-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>
			<div id="beginner-st-hint" class="hidden mt-3 bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg p-3">Wir empfehlen Back-Anfängern ein wenig Hefe zur Gelingsicherheit. Wir fügen automatisch eine kleine Menge hinzu.</div>
		</div>

		<!-- Hauptmehle -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2 block">Hauptmehle</label>
			<p class="text-sm text-gray-500 mb-3" id="main-flour-hint">Wähle bis zu 1 Hauptmehl</p>
			<div id="main-flour-error" class="hidden mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">Bitte wähle mindestens ein Hauptmehl, um fortzufahren.</div>
			<div id="main-flours" class="space-y-3"></div>
		</div>

		<!-- Nebenmehle -->
		<div id="side-flours-wrap" class="mb-6 hidden">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2 block">Weitere Mehle <span class="text-gray-400 normal-case">(optional)</span></label>
			<div id="side-flours" class="space-y-3"></div>
		</div>

		<!-- Mehlmenge -->
		<div class="mb-6">
			<label class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 block">Mehlmenge</label>
			<div class="flex items-center justify-center gap-6">
				<button type="button" id="flour-minus" class="w-12 h-12 rounded-full bg-bread-100 hover:bg-bread-200 text-xl font-bold text-gray-700 card-transition">−</button>
				<span class="text-3xl font-bold text-crust" id="flour-amount">500g</span>
				<button type="button" id="flour-plus" class="w-12 h-12 rounded-full bg-bread-100 hover:bg-bread-200 text-xl font-bold text-gray-700 card-transition">+</button>
			</div>
			<p class="text-center text-xs text-gray-500 mt-2">Basis für alle Berechnungen (in 50g-Schritten, max 1000g)</p>
		</div>

		<div id="rye-hint" class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>
	</section>

	<!-- ===================== STEP 3: Extras ===================== -->
	<section id="step-3" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Extras</h2>
		<p class="text-center text-gray-600 mb-6">Möchtest du Saaten oder Körner einarbeiten?</p>

		<div id="extras-warning" class="hidden mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3"></div>

		<div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="extras-grid"></div>

		<p class="text-center text-sm text-gray-500 mt-4" id="extras-counter">0/7 Extras ausgewählt</p>
		<p class="text-center text-sm text-gray-400 italic mt-2">Keine Extras? Einfach weiter zum nächsten Schritt.</p>
	</section>

	<!-- ===================== STEP 4: Backmethode ===================== -->
	<section id="step-4" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Backmethode</h2>
		<p class="text-center text-gray-600 mb-6">Wie möchtest du dein Brot backen?</p>

		<div class="space-y-3" id="method-cards">
			<button type="button" data-value="pot" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-crust shadow-sm text-left flex items-start gap-4 relative is-selected">
				<span class="text-3xl">🍲</span>
				<div class="flex-1">
					<span class="font-bold block">Topf / Dutch Oven</span>
					<span class="text-sm text-gray-500">Gusseisen-Topf mit Deckel. Beste Kruste für Anfänger, verzeiht Fehler.</span>
				</div>
				<span data-recommended="pot" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase">Empfohlen</span>
			</button>
			<button type="button" data-value="stone" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-transparent hover:border-bread-300 shadow-sm text-left flex items-start gap-4 relative">
				<span class="text-3xl">🪨</span>
				<div class="flex-1">
					<span class="font-bold block">Pizzastein</span>
					<span class="text-sm text-gray-500">Steinplatte im Ofen. Gleichmäßige Hitze von unten, gutes Ofentriebverhalten.</span>
				</div>
				<span data-recommended="stone" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase">Empfohlen</span>
			</button>
			<button type="button" data-value="steel" class="card-transition w-full bg-white rounded-xl p-5 border-2 border-transparent hover:border-bread-300 shadow-sm text-left flex items-start gap-4 relative">
				<span class="text-3xl">⬛</span>
				<div class="flex-1">
					<span class="font-bold block">Backstahl</span>
					<span class="text-sm text-gray-500">Stahlplatte im Ofen. Schnellste Hitzeübertragung, profihafte Kruste.</span>
				</div>
				<span data-recommended="steel" class="hidden absolute top-3 right-3 bg-crust text-white text-xs font-bold px-2 py-1 rounded-full uppercase">Empfohlen</span>
			</button>
		</div>

		<div class="bg-bread-50 rounded-lg p-4 mt-4 text-sm text-gray-700">
			🔥 Alle Methoden funktionieren gut — die Empfehlung basiert auf deinem Erfahrungslevel. Backe so, wie du dich am wohlsten fühlst!
		</div>
	</section>

	<!-- ===================== STEP 5: Rezept ===================== -->
	<section id="step-5" class="step-section hidden">
		<h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Rezept</h2>
		<p class="text-center text-gray-600 mb-6">Dein persönliches Brotrezept mit Mengen und Zeitplan.</p>

		<!-- Empty state -->
		<div id="step-5-empty">
			<div id="step-5-error" class="hidden mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3"></div>
			<div class="bg-bread-50 rounded-xl p-8 text-center">
				<p class="text-gray-600 mb-4">Alle Angaben sind erfasst. Erstelle jetzt dein Rezept.</p>
				<button type="button" id="calculate-btn" class="bg-crust hover:bg-crust-dark text-white font-bold py-3 px-8 rounded-xl text-lg card-transition uppercase tracking-wide">Rezept erstellen</button>
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
				<summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">Debug: Berechnungsdetails</summary>
				<div class="mt-3 bg-gray-50 rounded-lg p-4 text-xs">
					<h4 class="font-bold mb-2">Eingabeparameter</h4>
					<table class="w-full mb-4" id="debug-input"></table>
					<h4 class="font-bold mb-2">Entscheidungsprotokoll</h4>
					<table class="w-full text-xs" id="debug-decisions">
						<thead><tr class="border-b"><th class="text-left py-1">Modul</th><th class="text-left py-1">Regel</th><th class="text-left py-1">Ergebnis</th></tr></thead>
						<tbody></tbody>
					</table>
				</div>
			</details>

			<!-- Actions -->
			<div class="flex gap-3 no-print">
				<button type="button" onclick="newRecipe()" class="flex-1 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition">↻ Neues Rezept</button>
				<button type="button" onclick="window.print()" class="flex-1 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition">🖨 Drucken</button>
			</div>
		</div>
	</section>

	<!-- Loading -->
	<div id="loading" class="hidden text-center py-12">
		<div class="inline-block w-8 h-8 border-4 border-bread-300 border-t-crust rounded-full animate-spin mb-4"></div>
		<p class="text-gray-600">Rezept wird berechnet…</p>
	</div>

	<!-- Navigation -->
	<div class="flex gap-3 mt-8 no-print" id="nav-buttons">
		<button type="button" id="prev-btn" class="hidden flex-1 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 card-transition" onclick="prevStep()">← Zurück</button>
		<button type="button" id="next-btn" class="flex-1 py-3 rounded-xl bg-crust hover:bg-crust-dark text-white font-bold card-transition" onclick="nextStep()">Weiter →</button>
	</div>

</div>

<script>
const FLOURS = <?= $flours ?>;
const EXTRAS = <?= $extras ?>;
const LEVEL_INFO = <?= $levelInfo ?>;
const API_URL = 'api.php';

const LABELS = {
	level: { 1: 'Einsteiger', 2: 'Grundkenntnisse', 3: 'Fortgeschritten', 4: 'Erfahren', 5: 'Profi' },
	levelDesc: { 1: 'Erste Gehversuche am Backen', 2: 'Einige Brote gebacken', 3: 'Routine mit verschiedenen Mehlen', 4: 'Viele Brote, auch Sauerteig', 5: 'Erfahren mit allen Techniken' },
};

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
	if (h <= 6) el.textContent = 'Schnelles Feierabendbrot';
	else if (h <= 8) el.textContent = 'Entspannter Backtag';
	else if (h <= 12) el.textContent = 'Gemütliches Tagesbrot';
	else if (h <= 24) el.textContent = 'Über-Nacht-Brot mit Tiefgang';
	else el.textContent = 'Slow Baking für Genießer';
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
	const tags = [
		state.timeBudget + ' h',
		LABELS.level[state.experienceLevel] || 'Level ' + state.experienceLevel,
		state.leavening === 'yeast' ? 'Hefe' : state.leavening === 'sourdough' ? 'Sauerteig' : 'Hybrid',
		state.flourAmount + 'g Mehl',
	];
	if (state.backMethod) {
		tags.push({ pot: 'Topf', stone: 'Stein', steel: 'Stahl' }[state.backMethod] || state.backMethod);
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

	// Step 4: button text
	$('#next-btn').innerHTML = step === 4 ? 'Rezept erstellen →' : 'Weiter →';

	updateProgress();

	// Step 5: auto-calculate
	if (step === 5) {
		syncState();
		fetchRecipe();
	}

	// Scroll to top
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

	$('#main-flour-hint').textContent = 'Wähle bis zu ' + mainCount + ' Hauptmehl' + (mainCount > 1 ? 'e' : '');

	// Main flours
	const mainEl = $('#main-flours');
	mainEl.innerHTML = '';
	const mainOptions = floursForMain(level);
	for (let i = 0; i < mainCount; i++) {
		const wrap = document.createElement('div');
		const label = document.createElement('label');
		label.className = 'text-sm text-gray-600 block mb-1';
		label.textContent = 'Hauptmehl ' + (i + 1);
		const select = document.createElement('select');
		select.className = 'w-full border border-bread-200 rounded-lg px-3 py-2.5 bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-crust/30';
		select.innerHTML = '<option value="">Hauptmehl wählen…</option>';
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
			label.textContent = 'Weiteres Mehl ' + (i + 1) + ' (optional)';
			const select = document.createElement('select');
			select.className = 'w-full border border-bread-200 rounded-lg px-3 py-2.5 bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-crust/30';
			select.innerHTML = '<option value="">— optional —</option>';
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

	$('#extras-counter').textContent = state.extras.length + '/7 Extras ausgewählt' + (state.extras.length > 0 ? ' — Brühstück wird automatisch berechnet' : '');

	// Warnings
	const warnEl = $('#extras-warning');
	if (state.timeBudget <= 4) {
		warnEl.textContent = 'Bei 4h werden Körner trocken eingearbeitet. Schrot nicht verfügbar.';
		warnEl.classList.remove('hidden');
	} else if (state.timeBudget >= 6 && state.timeBudget <= 8 && (state.leavening === 'sourdough' || state.leavening === 'hybrid')) {
		warnEl.textContent = 'Kein Brühstück möglich – Sauerteig braucht die Zeit.';
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
			if (!res.ok) return res.json().then(j => Promise.reject(new Error(j.error || 'Serverfehler')));
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
			$('#step-5-error').textContent = err.message || 'Es ist ein Fehler aufgetreten.';
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
	const bakeStep = (recipe.timeline || []).find(s => (s.label || '').indexOf('Backen') >= 0);
	const bakeDur = bakeStep ? (bakeStep.duration >= 60 ? Math.floor(bakeStep.duration / 60) + ' h' : bakeStep.duration + ' Min.') : '—';
	$('#recipe-metrics').innerHTML = `
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">⚖</span>
			<p class="text-xs text-gray-500">Teigausbeute</p>
			<p class="font-bold text-gray-900">TA ${teaser.ta || ''}</p>
		</div>
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">🌡</span>
			<p class="text-xs text-gray-500">Gesamtgewicht</p>
			<p class="font-bold text-gray-900">${teaser.weight || ''}g</p>
		</div>
		<div class="bg-white rounded-xl p-4 text-center shadow-sm border border-bread-200">
			<span class="text-2xl block mb-1">🕐</span>
			<p class="text-xs text-gray-500">Backzeit</p>
			<p class="font-bold text-gray-900">${bakeDur}</p>
		</div>`;

	// Ingredients
	const groups = recipe.ingredients || {};
	const groupTitles = { sourdough: 'SAUERTEIG', kochstueck: 'KOCHSTÜCK', bruehstueck: 'BRÜHSTÜCK', main: 'HAUPTTEIG' };
	let ingHtml = '<h3 class="text-lg font-bold text-gray-900 mb-3">Zutaten</h3>';
	Object.keys(groups).forEach(key => {
		const g = groups[key];
		const title = groupTitles[key] || (g.label || '').toUpperCase();
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

	// Timeline
	let tlHtml = '<h3 class="text-lg font-bold text-gray-900 mb-3">Zeitplan</h3>';
	tlHtml += '<div class="space-y-0 relative">';
	(recipe.timeline || []).forEach((step, i) => {
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
			</div>
		</div>`;
	});
	tlHtml += '</div>';
	$('#recipe-timeline').innerHTML = tlHtml;

	// Baking
	$('#recipe-baking').innerHTML = `<h3 class="text-lg font-bold text-gray-900 mb-3">Backhinweise</h3><div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-gray-700">${esc(recipe.baking || '')}</div>`;

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
	// Time slider
	const timeSlider = $('#time-slider');
	timeSlider.addEventListener('input', () => {
		state.timeBudget = parseInt(timeSlider.value);
		$('#time-value').textContent = state.timeBudget + ' Stunden';
		updateTimeVibe(state.timeBudget);
		$('#fridge-card').classList.toggle('hidden', state.timeBudget < 12);
		updateSummary();
	});
	updateTimeVibe(state.timeBudget);

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
				c.classList.toggle('bg-crust', c === btn);
				c.classList.toggle('text-white', c === btn);
				c.classList.toggle('bg-bread-100', c !== btn);
				c.classList.toggle('text-gray-700', c !== btn);
				c.classList.toggle('is-selected', c === btn);
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
