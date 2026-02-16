# Brotarchitekt – WordPress-Plugin

Geführter Brot-Konfigurator: Wizard für individuelle Brotrezepte mit grammgenauen Mengen und Zeitplan.

## Installation

1. Ordner `brotarchitekt` nach `wp-content/plugins/` kopieren.
2. Im WordPress-Backend unter **Plugins** das Plugin **Brotarchitekt** aktivieren.

## Verwendung

Auf einer beliebigen Seite oder Beitrag den Shortcode einfügen:

```
[brotarchitekt]
```

Die Seite zeigt dann:
- **Landing:** Einleitung und Button „Los geht's“
- **Wizard (4 Schritte):** Zeit & Erfahrung → Triebmittel & Mehl → Extras → Backmethode
- **Ergebnis:** Rezeptname, Zutaten (gruppiert), Zeitplan mit Uhrzeiten, Backhinweise, Buttons „Neues Rezept“ und „Drucken“

## Abhängigkeiten

- WordPress 5.8+
- PHP 7.4+
- Keine weiteren Plugins nötig

## Technik

- **Frontend:** Vanilla JS (Wizard-State, REST-Call), CSS mit Design-Variablen (Erdtöne, warmes Layout).
- **Backend:** REST API unter `POST /wp-json/brotarchitekt/v1/recipe`; Rezept-Engine in PHP (TA, Sauerteig, Brühstück, Kochstück, Zeitplan, Backprofile).

Regelwerk und Entwicklungslogik entsprechen den Dokumenten im Ordner `briefing/`.

## Anpassung

- Texte sind über das WordPress-Übersetzungssystem (Textdomain `brotarchitekt`) änderbar.
- Farben und Abstände in `assets/css/style.css` (CSS-Variablen unter `:root`).
