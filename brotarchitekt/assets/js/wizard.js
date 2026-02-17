(function () {
	'use strict';

	const DATA = window.brotarchitektData || {};
	const REST_URL = (DATA.restUrl || '').replace(/\/$/, '') + '/recipe';
	const NONCE = DATA.nonce || '';
	const LABELS = DATA.labels || {};
	const FLOURS = DATA.flours || [];
	const EXTRAS = DATA.extras || [];
	const LEVEL_INFO = DATA.levelInfo || {};

	const state = {
		step: 1,
		timeBudget: 12,
		experienceLevel: 2,
		bakeFromFridge: false,
		leavening: 'yeast',
		sourdoughType: 'rye',
		sourdoughReady: 'yes',
		flourAmount: 500,
		mainFlours: [],
		sideFlours: [],
		extras: [],
		backMethod: 'pot',
	};

	const app = document.getElementById('brotarchitekt-app');
	if (!app) return;

	// Vibe-Label für Zeitslider
	const timeVibeEl = app.querySelector('[data-time-vibe]');
	function updateTimeVibe(h) {
		if (!timeVibeEl) return;
		if (h <= 6) timeVibeEl.textContent = 'Schnelles Feierabendbrot';
		else if (h <= 8) timeVibeEl.textContent = 'Entspannter Backtag';
		else if (h <= 12) timeVibeEl.textContent = 'Gemütliches Tagesbrot';
		else if (h <= 24) timeVibeEl.textContent = 'Über-Nacht-Brot mit Tiefgang';
		else timeVibeEl.textContent = 'Slow Baking für Genießer';
	}

	// Views
	const landing = app.querySelector('[data-view="landing"]');
	const wizard = app.querySelector('[data-view="wizard"]');
	const result = app.querySelector('[data-view="result"]');
	const loading = app.querySelector('[data-view="loading"]');
	const errorView = app.querySelector('[data-view="error"]');

	function showView(name) {
		[landing, wizard, result, loading, errorView].forEach(el => {
			if (el) el.hidden = true;
		});
		const view = app.querySelector('[data-view="' + name + '"]');
		if (view) view.hidden = false;
		app.setAttribute('data-state', name);
	}

	function getLevelInfo() {
		return LEVEL_INFO[state.experienceLevel] || LEVEL_INFO[2];
	}

	function floursForMain(levelNum) {
		const info = LEVEL_INFO[levelNum];
		const maxMain = info ? info.main_flours : 1;
		const allowAncient = info ? info.ancient_main : false;
		return FLOURS.filter(f => {
			if (f.category === 'ancient' && !allowAncient) return false;
			return f.level_main <= levelNum;
		});
	}

	function floursForSide(levelNum) {
		const info = LEVEL_INFO[levelNum];
		const maxSide = info ? info.side_flours : 0;
		if (maxSide === 0) return [];
		const allowAncient = info ? info.ancient_side : false;
		return FLOURS.filter(f => {
			if (f.category === 'ancient' && !allowAncient) return false;
			return f.level_side <= levelNum;
		});
	}

	function renderFlourSelectors() {
		const levelNum = state.experienceLevel;
		const info = getLevelInfo();
		const mainCount = info.main_flours || 1;
		const sideCount = info.side_flours || 0;

		const mainContainer = app.querySelector('[data-main-flours]');
		const sideContainer = app.querySelector('[data-side-flours]');
		const sideWrap = app.querySelector('[data-side-flours-wrap]');
		const mainHint = app.querySelector('[data-main-flour-hint]');
		if (!mainContainer || !sideContainer) return;

		if (mainHint) mainHint.textContent = (LABELS.mainFlourHint || 'Wähle bis zu') + ' ' + mainCount + ' ' + (LABELS.mainFloursLabel || 'Hauptmehle');
		if (sideWrap) sideWrap.hidden = sideCount === 0;

		mainContainer.innerHTML = '';
		const mainOptions = floursForMain(levelNum);
		for (let i = 0; i < mainCount; i++) {
			const select = document.createElement('select');
			select.className = 'brotarchitekt-select';
			select.name = 'mainFlour_' + i;
			select.dataset.index = String(i);
			select.dataset.role = 'main-flour';
			const opt0 = document.createElement('option');
			opt0.value = '';
			opt0.textContent = LABELS.mainFlour + ' ' + (i + 1) + ' wählen';
			select.appendChild(opt0);
			// Gruppieren nach Getreide
			const byGrain = {};
			mainOptions.forEach(f => {
				if (!byGrain[f.grain]) byGrain[f.grain] = [];
				byGrain[f.grain].push(f);
			});
			Object.keys(byGrain).forEach(grain => {
				const group = document.createElement('optgroup');
				group.label = byGrain[grain][0].label.split(' ')[0];
				byGrain[grain].forEach(f => {
					const opt = document.createElement('option');
					opt.value = f.id;
					opt.textContent = f.label;
					group.appendChild(opt);
				});
				select.appendChild(group);
			});
			select.value = state.mainFlours[i] || '';
			select.addEventListener('change', function () {
				state.mainFlours[i] = select.value || null;
				state.mainFlours = state.mainFlours.filter(Boolean);
				var req = app.querySelector('[data-step-2-required]');
				if (req) req.hidden = true;
				updateSummary();
			});
			const wrap = document.createElement('div');
			wrap.className = 'brotarchitekt-field';
			const label = document.createElement('label');
			label.textContent = (LABELS.mainFlour || 'Hauptmehl') + ' ' + (i + 1);
			wrap.appendChild(label);
			wrap.appendChild(select);
			mainContainer.appendChild(wrap);
		}

		if (sideCount > 0) {
			sideContainer.hidden = false;
			sideContainer.innerHTML = '';
			const sideOptions = floursForSide(levelNum);
			for (let i = 0; i < sideCount; i++) {
				const select = document.createElement('select');
				select.className = 'brotarchitekt-select';
				select.name = 'sideFlour_' + i;
				select.dataset.index = String(i);
				select.dataset.role = 'side-flour';
				const opt0 = document.createElement('option');
				opt0.value = '';
				opt0.textContent = (LABELS.sideFlour || 'Weiteres Mehl') + ' ' + (i + 1) + ' (optional)';
				select.appendChild(opt0);
				const byGrain = {};
				sideOptions.forEach(f => {
					if (!byGrain[f.grain]) byGrain[f.grain] = [];
					byGrain[f.grain].push(f);
				});
				Object.keys(byGrain).forEach(grain => {
					const group = document.createElement('optgroup');
					group.label = byGrain[grain][0].label.split(' ')[0];
					byGrain[grain].forEach(f => {
						const opt = document.createElement('option');
						opt.value = f.id;
						opt.textContent = f.label;
						group.appendChild(opt);
					});
					select.appendChild(group);
				});
				select.value = state.sideFlours[i] || '';
				select.addEventListener('change', () => {
					state.sideFlours[i] = select.value || null;
					state.sideFlours = state.sideFlours.filter(Boolean);
					updateSummary();
				});
				const wrap = document.createElement('div');
				wrap.className = 'brotarchitekt-field';
				const label = document.createElement('label');
				label.textContent = (LABELS.sideFlour || 'Weiteres Mehl') + ' ' + (i + 1);
				wrap.appendChild(label);
				wrap.appendChild(select);
				sideContainer.appendChild(wrap);
			}
		} else {
			sideContainer.hidden = true;
		}

		// Vorauswahl wiederherstellen
		mainContainer.querySelectorAll('[data-role="main-flour"]').forEach((sel, i) => {
			if (state.mainFlours[i]) sel.value = state.mainFlours[i];
		});
		sideContainer.querySelectorAll('[data-role="side-flour"]').forEach((sel, i) => {
			if (state.sideFlours[i]) sel.value = state.sideFlours[i];
		});
	}

	const EXTRA_ICONS = { sunflower: '🌻', pumpkin: '🎃', sesame: '⚪', linseed: '🌾', oatmeal: '🌿', old_bread: '🍞', grist: '🥜' };

	function renderExtras() {
		const info = getLevelInfo();
		const maxExtras = info.max_extras || 5;
		const container = app.querySelector('[data-extras]');
		const counterEl = app.querySelector('[data-extras-counter]');
		if (!container) return;
		container.innerHTML = '';
		EXTRAS.slice(0, 7).forEach(extra => {
			const card = document.createElement('button');
			card.type = 'button';
			card.className = 'brotarchitekt-extras-card';
			card.dataset.extra = extra.id;
			if (state.extras.includes(extra.id)) card.classList.add('is-selected');
			const icon = document.createElement('span');
			icon.className = 'brotarchitekt-extras-card-icon';
			icon.textContent = EXTRA_ICONS[extra.id] || '•';
			card.appendChild(icon);
			const name = document.createElement('span');
			name.textContent = extra.name;
			card.appendChild(name);
			card.addEventListener('click', () => {
				const idx = state.extras.indexOf(extra.id);
				if (idx >= 0) {
					state.extras.splice(idx, 1);
				} else if (state.extras.length < maxExtras) {
					state.extras.push(extra.id);
				}
				state.extras = state.extras.slice(0, maxExtras);
				container.querySelectorAll('.brotarchitekt-extras-card').forEach(c => {
					c.classList.toggle('is-selected', state.extras.includes(c.dataset.extra));
				});
				if (counterEl) counterEl.textContent = state.extras.length + '/7 ' + (LABELS.extrasCounter || 'Extras ausgewählt — Brühstück wird automatisch berechnet');
				updateSummary();
			});
			container.appendChild(card);
		});
		if (counterEl) counterEl.textContent = state.extras.length + '/7 ' + (LABELS.extrasCounter || 'Extras ausgewählt — Brühstück wird automatisch berechnet');

		const warnEl = app.querySelector('[data-extras-warning]');
		if (warnEl) {
			if (state.timeBudget <= 4) {
				warnEl.textContent = 'Bei 4h werden Körner trocken eingearbeitet. Schrot nicht verfügbar.';
				warnEl.hidden = false;
			} else if (state.timeBudget >= 6 && state.timeBudget <= 8 && (state.leavening === 'sourdough' || state.leavening === 'hybrid')) {
				warnEl.textContent = 'Kein Brühstück möglich – Sauerteig braucht die Zeit.';
				warnEl.hidden = false;
			} else {
				warnEl.hidden = true;
			}
		}
	}

	function updateSummary() {
		const tags = [];
		tags.push(state.timeBudget + ' h');
		tags.push(LABELS.level && LABELS.level[state.experienceLevel] ? LABELS.level[state.experienceLevel] : 'Level ' + state.experienceLevel);
		if (state.leavening === 'yeast') tags.push(LABELS.yeast || 'Hefe');
		else if (state.leavening === 'sourdough') tags.push(LABELS.sourdough || 'Sauerteig');
		else tags.push(LABELS.hybrid || 'Hybrid');
		tags.push(state.flourAmount + ' g Mehl');
		if (state.backMethod) tags.push(state.backMethod === 'pot' ? (LABELS.pot || 'Topf') : (state.backMethod === 'stone' ? (LABELS.stone || 'Stein') : (LABELS.steel || 'Stahl')));
		const container = app.querySelector('[data-summary-tags]');
		if (container) {
			container.innerHTML = tags.map(t => '<span class="brotarchitekt-tag">' + escapeHtml(t) + '</span>').join('');
			container.setAttribute('aria-hidden', 'false');
		}
	}

	function escapeHtml(s) {
		const div = document.createElement('div');
		div.textContent = s;
		return div.innerHTML;
	}

	const TOTAL_STEPS = 5;

	/** Prüft Schritt 2: mindestens ein Hauptmehl gewählt. */
	function validateStep2() {
		var mainFlours = Array.from(app.querySelectorAll('[data-main-flours] select')).map(function (s) { return s.value; }).filter(Boolean);
		return mainFlours.length > 0;
	}

	function showStep2Validation(show) {
		var notice = app.querySelector('[data-step-2-required]');
		if (notice) notice.hidden = !show;
		if (show) {
			var first = app.querySelector('[data-main-flours] select');
			if (first) first.focus();
		}
	}

	function goStep(step) {
		state.step = step;
		app.querySelectorAll('.brotarchitekt-step').forEach((section, i) => {
			section.setAttribute('aria-hidden', i + 1 !== step);
		});
		app.querySelectorAll('.brotarchitekt-progress-step').forEach((el, i) => {
			const n = i + 1;
			el.classList.toggle('is-active', n === step);
			el.classList.toggle('is-completed', n < step);
			el.setAttribute('aria-selected', n === step);
		});
		const prevBtn = app.querySelector('.brotarchitekt-wizard-nav [data-action="prev"]');
		const nextBtn = app.querySelector('.brotarchitekt-wizard-nav [data-action="next"]');
		if (prevBtn) prevBtn.hidden = step <= 1;
		if (nextBtn) {
			nextBtn.hidden = step >= TOTAL_STEPS;
			nextBtn.innerHTML = step === 4
				? (LABELS.calculate || 'Rezept erstellen') + ' <span aria-hidden="true">\u2192</span>'
				: (LABELS.next || 'Weiter') + ' <span aria-hidden="true">\u2192</span>';
		}
		// Zur „Seite" scrollen: Wizard-Container oder aktiven Schritt oben anzeigen
		const stepsContainer = app.querySelector('.brotarchitekt-steps');
		if (stepsContainer) {
			stepsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		// Step 5: Rezept automatisch berechnen (nur wenn nicht bereits geladen)
		if (step === 5 && !state._fetching) {
			// State aus DOM synchronisieren
			app.querySelectorAll('[data-main-flours] select').forEach((sel, i) => {
				if (sel.value) state.mainFlours[i] = sel.value;
			});
			state.mainFlours = state.mainFlours.filter(Boolean);
			app.querySelectorAll('[data-side-flours] select').forEach((sel, i) => {
				if (sel.value) state.sideFlours[i] = sel.value;
			});
			state.sideFlours = state.sideFlours.filter(Boolean);
			const flourInputEl = app.querySelector('[data-flour-amount-input]');
			if (flourInputEl) state.flourAmount = parseInt(flourInputEl.value, 10) || 500;
			fetchRecipe();
		}
	}

	function bindWizard() {
		// Zeit-Slider (Ausgabe: "X Stunden")
		const timeInput = app.querySelector('#ba-time');
		const timeOutput = app.querySelector('#ba-time-value');
		const hoursLabel = (LABELS.hours || 'Stunden').replace(/^.*\s/, '') || 'Stunden';
		if (timeInput && timeOutput) {
			timeInput.value = state.timeBudget;
			timeOutput.textContent = state.timeBudget + ' ' + (LABELS.hours || 'Stunden');
			updateTimeVibe(state.timeBudget);
			timeInput.addEventListener('input', () => {
				state.timeBudget = parseInt(timeInput.value, 10);
				timeOutput.textContent = state.timeBudget + ' ' + (LABELS.hours || 'Stunden');
				updateTimeVibe(state.timeBudget);
				const fridgeWrap = app.querySelector('[data-fridge-wrap]');
				if (fridgeWrap) fridgeWrap.hidden = state.timeBudget < 12;
				updateSummary();
			});
		}

		// Level-Slider + Level-Info-Box (Name + Beschreibung)
		const levelInput = app.querySelector('#ba-level');
		const levelNameEl = app.querySelector('[data-level-name]');
		const levelDescEl = app.querySelector('[data-level-desc]');
		function updateLevelInfo() {
			const L = LABELS.level || {};
			const D = LABELS.levelDesc || {};
			if (levelNameEl) levelNameEl.textContent = L[state.experienceLevel] || ('Level ' + state.experienceLevel);
			if (levelDescEl) levelDescEl.textContent = D[state.experienceLevel] || '';
		}
		if (levelInput) {
			levelInput.value = state.experienceLevel;
			updateLevelInfo();
			levelInput.addEventListener('input', () => {
				state.experienceLevel = parseInt(levelInput.value, 10);
				updateLevelInfo();
				renderFlourSelectors();
				renderExtras();
				updateBackRecommended();
				updateSummary();
			});
		}

		// Kühlschrank-Toggle
		const fridgeWrap = app.querySelector('[data-fridge-wrap]');
		if (fridgeWrap) fridgeWrap.hidden = state.timeBudget < 12;
		const fridgeCheck = app.querySelector('input[name="bakeFromFridge"]');
		if (fridgeCheck) {
			fridgeCheck.checked = state.bakeFromFridge;
			fridgeCheck.addEventListener('change', () => {
				state.bakeFromFridge = fridgeCheck.checked;
				updateSummary();
			});
		}

		// Triebmittel-Karten
		app.querySelectorAll('[data-leavening] .brotarchitekt-card').forEach(btn => {
			btn.addEventListener('click', () => {
				state.leavening = btn.dataset.value;
				app.querySelectorAll('[data-leavening] .brotarchitekt-card').forEach(c => c.classList.remove('is-selected'));
				btn.classList.add('is-selected');
				const so = app.querySelector('[data-sourdough-options]');
				if (so) so.hidden = state.leavening === 'yeast';
				app.querySelector('[data-sourdough-warning]').hidden = true;
				app.querySelector('[data-beginner-st-hint]').hidden = !(state.leavening === 'sourdough' && state.experienceLevel <= 2);
				updateSourdoughWarning();
				renderExtras();
				updateSummary();
			});
		});
		const leaveningFirst = app.querySelector('[data-leavening] .brotarchitekt-card[data-value="yeast"]');
		if (leaveningFirst) leaveningFirst.classList.add('is-selected');

		// Sauerteig-Typ
		app.querySelectorAll('[data-sourdough-type] .brotarchitekt-chip').forEach(btn => {
			btn.addEventListener('click', () => {
				state.sourdoughType = btn.dataset.value;
				app.querySelectorAll('[data-sourdough-type] .brotarchitekt-chip').forEach(c => c.classList.remove('is-selected'));
				btn.classList.add('is-selected');
			});
		});
		app.querySelector('[data-sourdough-type] .brotarchitekt-chip[data-value="rye"]').classList.add('is-selected');

		// Sauerteig einsatzbereit (ein Toggle)
		const stReadyToggle = app.querySelector('input[name="sourdoughReadyToggle"]');
		if (stReadyToggle) {
			stReadyToggle.checked = state.sourdoughReady === 'yes';
			stReadyToggle.addEventListener('change', () => {
				state.sourdoughReady = stReadyToggle.checked ? 'yes' : 'no';
				updateSourdoughWarning();
			});
		}

		function updateSourdoughWarning() {
			const w = app.querySelector('[data-sourdough-warning]');
			if (!w) return;
			if (state.leavening === 'yeast') { w.hidden = true; return; }
			if (state.timeBudget < 8) {
				w.textContent = 'Dein Sauerteig muss bereits einsatzbereit sein.';
				w.hidden = false;
			} else if (state.timeBudget <= 12) {
				w.textContent = 'Nur schnelle Auffrischung möglich (~4h).';
				w.hidden = false;
			} else {
				w.hidden = true;
			}
		}

		// Mehlmenge Stepper (Anzeige + verstecktes Input)
		const flourDisplay = app.querySelector('.brotarchitekt-stepper-value');
		const flourInput = app.querySelector('[data-flour-amount-input]');
		function setFlourAmount(v) {
			v = Math.max(250, Math.min(1000, Math.round(v / 50) * 50));
			state.flourAmount = v;
			if (flourDisplay) flourDisplay.textContent = v;
			if (flourInput) flourInput.value = v;
			updateSummary();
		}
		app.querySelectorAll('[data-action="flour-minus"]').forEach(btn => {
			btn.addEventListener('click', () => setFlourAmount(state.flourAmount - 50));
		});
		app.querySelectorAll('[data-action="flour-plus"]').forEach(btn => {
			btn.addEventListener('click', () => setFlourAmount(state.flourAmount + 50));
		});
		setFlourAmount(state.flourAmount);

		// Backmethode
		app.querySelectorAll('[data-back-method] .brotarchitekt-card').forEach(btn => {
			btn.addEventListener('click', () => {
				state.backMethod = btn.dataset.value;
				app.querySelectorAll('[data-back-method] .brotarchitekt-card').forEach(c => c.classList.remove('is-selected'));
				btn.classList.add('is-selected');
				updateBackRecommended();
				updateSummary();
			});
		});
		updateBackRecommended();
		const firstBack = app.querySelector('[data-back-method] .brotarchitekt-card[data-value="pot"]');
		if (firstBack) firstBack.classList.add('is-selected');
	}

	function updateBackRecommended() {
		const rec = getLevelInfo().recommended_back;
		app.querySelectorAll('.brotarchitekt-recommended').forEach(el => {
			el.hidden = el.dataset.recommended !== rec;
		});
	}

	// Navigation
	app.addEventListener('click', (e) => {
		const action = e.target.closest('[data-action]');
		if (!action) return;
		const a = action.dataset.action;
		if (a === 'start-wizard') {
			showView('wizard');
			wizard.hidden = false;
			renderFlourSelectors();
			renderExtras();
			updateBackRecommended();
			goStep(1);
			updateSummary();
			return;
		}
		if (a === 'prev') {
			goStep(Math.max(1, state.step - 1));
			return;
		}
		if (a === 'next') {
			var nextStep = state.step + 1;
			if (state.step === 2 && nextStep >= 3 && !validateStep2()) {
				showStep2Validation(true);
				return;
			}
			showStep2Validation(false);
			goStep(Math.min(TOTAL_STEPS, nextStep));
			return;
		}
		if (a === 'skip-extras') {
			state.extras = [];
			app.querySelectorAll('[data-extras] .brotarchitekt-extras-card').forEach(c => c.classList.remove('is-selected'));
			goStep(4);
			updateSummary();
			return;
		}
		if (a === 'calculate') {
			if (!validateStep2()) {
				goStep(2);
				showStep2Validation(true);
				return;
			}
			showStep2Validation(false);
			state.mainFlours = Array.from(app.querySelectorAll('[data-main-flours] select')).map(function (s) { return s.value; }).filter(Boolean);
			state.sideFlours = Array.from(app.querySelectorAll('[data-side-flours] select')).map(function (s) { return s.value; }).filter(Boolean);
			var flourInputEl = app.querySelector('[data-flour-amount-input]');
			if (flourInputEl) state.flourAmount = parseInt(flourInputEl.value, 10) || 500;
			fetchRecipe();
			return;
		}
		if (a === 'new-recipe') {
			showView('wizard');
			wizard.hidden = false;
			const step5Empty = app.querySelector('[data-step-5-empty]');
			const step5Result = app.querySelector('[data-step-5-result]');
			if (step5Empty) step5Empty.hidden = false;
			if (step5Result) step5Result.hidden = true;
			goStep(1);
			updateSummary();
			return;
		}
		if (a === 'print') {
			window.print();
			return;
		}
	});

	function getErrorMessage(err) {
		if (!err) return LABELS.error || 'Es ist ein Fehler aufgetreten.';
		var msg = err.message || String(err);
		if (msg.indexOf('JSON') !== -1 || msg.indexOf('Unexpected token') !== -1) {
			return 'Serverfehler: Die Antwort konnte nicht gelesen werden. Bitte prüfe, ob das Plugin aktiv ist und die REST-API erreichbar ist.';
		}
		if (msg.indexOf('Failed to fetch') !== -1 || msg.indexOf('NetworkError') !== -1) {
			return 'Netzwerkfehler: Keine Verbindung zum Server. Bitte Internetverbindung und Seite prüfen.';
		}
		if (msg.indexOf('403') !== -1 || msg.indexOf('401') !== -1) {
			return 'Zugriff verweigert. Bitte Seite neu laden und erneut versuchen.';
		}
		return msg;
	}

	function showRecipeError(message) {
		var step5Error = app.querySelector('[data-step-5-error]');
		var step5Empty = app.querySelector('[data-step-5-empty]');
		var step5Result = app.querySelector('[data-step-5-result]');
		if (step5Error && step5Empty) {
			step5Error.textContent = message;
			step5Error.hidden = false;
			step5Empty.hidden = false;
			if (step5Result) step5Result.hidden = true;
			showView('wizard');
			if (wizard) wizard.hidden = false;
			// Step 5 anzeigen ohne goStep (vermeidet erneuten fetchRecipe-Aufruf)
			app.querySelectorAll('.brotarchitekt-step').forEach(function (section, i) {
				section.setAttribute('aria-hidden', i + 1 !== 5);
			});
			state.step = 5;
		} else {
			if (errorView) {
				errorView.textContent = message;
				errorView.hidden = false;
			}
			showView('error');
		}
	}

	function fetchRecipe() {
		state._fetching = true;
		var step5Error = app.querySelector('[data-step-5-error]');
		if (step5Error) {
			step5Error.hidden = true;
			step5Error.textContent = '';
		}
		showView('loading');
		var body = {
			timeBudget: state.timeBudget,
			experienceLevel: state.experienceLevel,
			bakeFromFridge: state.bakeFromFridge,
			leavening: state.leavening,
			sourdoughType: state.sourdoughType,
			sourdoughReady: state.sourdoughReady,
			flourAmount: state.flourAmount,
			mainFlours: Array.isArray(state.mainFlours) ? state.mainFlours : [],
			sideFlours: Array.isArray(state.sideFlours) ? state.sideFlours : [],
			extras: Array.isArray(state.extras) ? state.extras : [],
			backMethod: state.backMethod || 'pot',
		};
		fetch(REST_URL, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': NONCE,
			},
			body: JSON.stringify(body),
		})
			.then(function (res) {
				if (!res.ok) {
					return res.text().then(function (text) {
						var msg = res.status + ' ' + res.statusText;
						try {
							var j = JSON.parse(text);
							if (j.message) msg = j.message;
							else if (j.code) msg = (j.code + ': ' + (j.message || msg));
						} catch (e) {}
						return Promise.reject(new Error(msg));
					});
				}
				return res.json().then(function (data) {
					return data;
				}, function (parseErr) {
					return Promise.reject(new Error('Server hat keine gültige JSON-Antwort geliefert. Möglicherweise ein PHP-Fehler auf dem Server.'));
				});
			})
			.then(function (recipe) {
				state._fetching = false;
				if (!recipe || typeof recipe !== 'object') {
					throw new Error('Ungültige Rezept-Antwort vom Server.');
				}
				var step5Result = app.querySelector('[data-step-5-result]');
				var step5Empty = app.querySelector('[data-step-5-empty]');
				if (step5Result && step5Empty) {
					renderResult(recipe, step5Result);
					step5Empty.hidden = true;
					if (step5Error) step5Error.hidden = true;
					step5Result.hidden = false;
				}
				// Wizard + Step 5 anzeigen (ohne goStep um Endlosschleife zu vermeiden)
				showView('wizard');
				if (wizard) wizard.hidden = false;
				// Step 5 sichtbar machen
				app.querySelectorAll('.brotarchitekt-step').forEach(function (section, i) {
					section.setAttribute('aria-hidden', i + 1 !== 5);
				});
				app.querySelectorAll('.brotarchitekt-progress-step').forEach(function (el, i) {
					var n = i + 1;
					el.classList.toggle('is-active', n === 5);
					el.classList.toggle('is-completed', n < 5);
					el.setAttribute('aria-selected', n === 5);
				});
				var prevBtn = app.querySelector('.brotarchitekt-wizard-nav [data-action="prev"]');
				var nextBtn = app.querySelector('.brotarchitekt-wizard-nav [data-action="next"]');
				if (prevBtn) prevBtn.hidden = false;
				if (nextBtn) nextBtn.hidden = true;
				state.step = 5;
			})
			.catch(function (err) {
				state._fetching = false;
				var message = getErrorMessage(err);
				showRecipeError(message);
			});
	}

	function renderResult(recipe, root) {
		root = root || app;
		const q = (sel) => root.querySelector(sel);

		const titleEl = q('[data-recipe-title]');
		if (titleEl) titleEl.textContent = recipe.name || '';
		const meta = recipe.meta || {};
		const tagLabels = [meta.level, meta.time, meta.back].filter(Boolean);
		const metaContainer = q('[data-recipe-meta]');
		if (metaContainer) metaContainer.innerHTML = tagLabels.map((t, i) => '<span class="brotarchitekt-tag' + (i === 0 ? ' brotarchitekt-tag--active' : '') + '">' + escapeHtml(t) + '</span>').join('');

		const teaser = recipe.teaser || {};
		const bakeDuration = (recipe.timeline && recipe.timeline.length) ? recipe.timeline.find(s => (s.label || '').indexOf('Backen') >= 0) : null;
		const durationStr = bakeDuration ? (bakeDuration.duration >= 60 ? (Math.floor(bakeDuration.duration / 60) + ' Min.') : (bakeDuration.duration + ' Min.')) : (teaser.bakeTime || '—');
		const metaCardsHtml = '<div class="brotarchitekt-metric-card"><span class="brotarchitekt-metric-card-icon">⚖</span><p class="brotarchitekt-metric-card-label">Teigausbeute</p><p class="brotarchitekt-metric-card-value">TA ' + (teaser.ta || '') + '</p></div>' +
			'<div class="brotarchitekt-metric-card"><span class="brotarchitekt-metric-card-icon">🌡</span><p class="brotarchitekt-metric-card-label">Gesamtgewicht</p><p class="brotarchitekt-metric-card-value">' + (teaser.weight || '') + 'g</p></div>' +
			'<div class="brotarchitekt-metric-card"><span class="brotarchitekt-metric-card-icon">🕐</span><p class="brotarchitekt-metric-card-label">Backzeit</p><p class="brotarchitekt-metric-card-value">' + durationStr + '</p></div>';
		const teaserEl = q('[data-recipe-teaser]');
		if (teaserEl) teaserEl.innerHTML = metaCardsHtml;

		const ingContainer = q('[data-ingredients]');
		if (ingContainer) {
			ingContainer.innerHTML = '<h3>Zutaten</h3>';
			const groupTitles = { sourdough: 'SAUERTEIG', kochstueck: 'KOCHSTÜCK', bruehstueck: 'BRÜHSTÜCK', main: 'HAUPTTEIG' };
			const groups = recipe.ingredients || {};
			Object.keys(groups).forEach(key => {
				const g = groups[key];
				const section = document.createElement('div');
				section.className = 'brotarchitekt-ingredients-group';
				const title = groupTitles[key] || g.label.toUpperCase();
				section.innerHTML = '<h3>' + escapeHtml(title) + '</h3><table class="brotarchitekt-ingredients-table"><tbody></tbody></table>';
				const tbody = section.querySelector('tbody');
				(g.items || []).forEach(item => {
					const tr = document.createElement('tr');
					let amountStr = item.amount + ' ' + (item.unit || 'g');
					if (item.percent != null && item.percent !== undefined) amountStr += ' (' + item.percent + ' %)';
					tr.innerHTML = '<td>' + escapeHtml(item.name) + '</td><td>' + amountStr + '</td>';
					tbody.appendChild(tr);
				});
				ingContainer.appendChild(section);
			});
		}

		const tlContainer = q('[data-timeline]');
		if (tlContainer) {
			tlContainer.innerHTML = '<h3>Zeitplan</h3>';
			const list = document.createElement('ol');
			list.className = 'brotarchitekt-timeline-list';
			(recipe.timeline || []).forEach((step) => {
				const li = document.createElement('li');
				li.innerHTML = '<div class="brotarchitekt-timeline-content"><strong>' + escapeHtml(step.time_formatted) + '</strong> ' + escapeHtml(step.label) + ' <span class="brotarchitekt-timeline-duration">(' + step.duration_formatted + ')</span><span class="brotarchitekt-timeline-desc">' + escapeHtml(step.desc || '') + '</span></div>';
				list.appendChild(li);
			});
			tlContainer.appendChild(list);
		}

		const bakingEl = q('[data-baking]');
		if (bakingEl) bakingEl.innerHTML = '<h3>Backhinweise</h3><p>' + escapeHtml(recipe.baking || '') + '</p>';

		const debugSection = q('[data-debug]');
		if (debugSection) {
			if (recipe.debug) {
				debugSection.hidden = false;
				const inputTable = q('[data-debug-input]');
				if (inputTable && recipe.debug.input) {
					let inputHtml = '';
					Object.entries(recipe.debug.input).forEach(([key, val]) => {
						inputHtml += '<tr><td><strong>' + escapeHtml(key) + '</strong></td><td>' + escapeHtml(String(val)) + '</td></tr>';
					});
					inputTable.innerHTML = inputHtml;
				}
				const decTable = q('[data-debug-decisions] tbody');
				if (decTable && recipe.debug.decisions) {
					decTable.innerHTML = recipe.debug.decisions.map(d => '<tr><td>' + escapeHtml(d.source) + '</td><td>' + escapeHtml(d.rule) + '</td><td>' + escapeHtml(d.result) + '</td></tr>').join('');
				}
			} else {
				debugSection.hidden = true;
			}
		}
	}

	// Progress-Steps: Klick auf Schritt wechselt dorthin (Pflichtfelder prüfen)
	app.addEventListener('click', function (e) {
		var stepBtn = e.target.closest('.brotarchitekt-progress-step');
		if (!stepBtn || !stepBtn.dataset.step) return;
		var targetStep = parseInt(stepBtn.dataset.step, 10);
		if (targetStep >= 3 && !validateStep2()) {
			goStep(2);
			showStep2Validation(true);
			return;
		}
		showStep2Validation(false);
		goStep(targetStep);
	});

	bindWizard();

	// Direkt Wizard starten (kein Landing-Screen)
	showView('wizard');
	if (wizard) wizard.hidden = false;
	renderFlourSelectors();
	renderExtras();
	updateBackRecommended();
	goStep(1);
	updateSummary();
})();
