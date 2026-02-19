/**
 * Brotarchitekt — Tailwind Konfiguration
 * Custom Colors, Fonts und Theme-Erweiterungen.
 */
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
