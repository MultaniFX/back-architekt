# Brotarchitekt

**Der Brot-Architekt** – WordPress-Plugin: Geführter Wizard für individuelle Brotrezepte mit grammgenauen Mengen und Zeitplan.

## Inhalt

- **`brotarchitekt/`** – WordPress-Plugin (Shortcode `[brotarchitekt]`, 4-Schritte-Wizard, Rezept-Engine, REST API)
- **`briefing/`** – Anforderungen und Regelwerk (Design-Briefing, Entwicklungslogik, Rezept-Regelwerk)
- **`screens/`** – Screenshots der geplanten Oberfläche

## Plugin-Installation

1. Ordner `brotarchitekt` nach `wp-content/plugins/` kopieren.
2. Plugin unter **Plugins** aktivieren.
3. Auf einer Seite den Shortcode `[brotarchitekt]` einfügen.

Details siehe [brotarchitekt/README.md](brotarchitekt/README.md).

## Technik

- PHP 7.4+, WordPress 5.8+
- Vanilla JS (Wizard), REST API für Rezeptberechnung
- Responsives CSS (Erdtöne, Karten-Layout)
