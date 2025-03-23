import { fontFamily } from "tailwindcss/defaultTheme";
import tailwindcssAnimate from "tailwindcss-animate";

/** @type {import('tailwindcss').Config} */
const config = {
	darkMode: ["class"],
	content: [
		"./src/**/*.svelte",
		"./src/**/*.js",
		"./src/components/**/*.{svelte,js}",
		"./src/routes/**/*.{svelte,js}",
		"./src/lib/**/*.{svelte,js}"
	],
	safelist: ["dark"],
	theme: {
		screens: {
			"mobile": "478px",
			"mobile-landscape": "767px",
			"tablet": "991px",
			"desktop": "1440px",
			"sm": "640px",
			"md": "768px",
			"lg": "1024px",
			"xl": "1280px",
			"2xl": "1536px",
		},
		container: {
			center: true,
			padding: {
				DEFAULT: '1rem',
				md: '1.5rem',
				lg: '2rem'
			},
			screens: {
				"2xl": "1440px"
			}
		},
		extend: {
			colors: {
				border: "hsl(var(--border) / <alpha-value>)",
				input: "hsl(var(--input) / <alpha-value>)",
				ring: "hsl(var(--ring) / <alpha-value>)",
				background: "hsl(var(--background) / <alpha-value>)",
				foreground: "hsl(var(--foreground) / <alpha-value>)",
				primary: {
					DEFAULT: "hsl(var(--primary) / <alpha-value>)",
					foreground: "hsl(var(--primary-foreground) / <alpha-value>)"
				},
				secondary: {
					DEFAULT: "hsl(var(--secondary) / <alpha-value>)",
					foreground: "hsl(var(--secondary-foreground) / <alpha-value>)"
				},
				destructive: {
					DEFAULT: "hsl(var(--destructive) / <alpha-value>)",
					foreground: "hsl(var(--destructive-foreground) / <alpha-value>)"
				},
				muted: {
					DEFAULT: "hsl(var(--muted) / <alpha-value>)",
					foreground: "hsl(var(--muted-foreground) / <alpha-value>)"
				},
				accent: {
					DEFAULT: "hsl(var(--accent) / <alpha-value>)",
					foreground: "hsl(var(--accent-foreground) / <alpha-value>)"
				},
				popover: {
					DEFAULT: "hsl(var(--popover) / <alpha-value>)",
					foreground: "hsl(var(--popover-foreground) / <alpha-value>)"
				},
				card: {
					DEFAULT: "hsl(var(--card) / <alpha-value>)",
					foreground: "hsl(var(--card-foreground) / <alpha-value>)"
				},
				"sidebar-bg": "hsl(var(--sidebar-background))",
				"sidebar-fg": "hsl(var(--sidebar-foreground))",
				"sidebar-primary": "hsl(var(--sidebar-primary))",
				"sidebar-primary-fg": "hsl(var(--sidebar-primary-foreground))",
				"sidebar-accent": "hsl(var(--sidebar-accent))",
				"sidebar-accent-fg": "hsl(var(--sidebar-accent-foreground))",
				"sidebar-border": "hsl(var(--sidebar-border))",
				"sidebar-ring": "hsl(var(--sidebar-ring))",
				"neon-yellow": "hsl(var(--neon-yellow))",
				"neon-green": "hsl(var(--neon-green))",
				"neon-blue": "hsl(var(--neon-blue))",
			},
			spacing: {
				'1': '0.25rem',    /* 4px */
				'2': '0.5rem',     /* 8px */
				'3': '0.75rem',    /* 12px */
				'4': '1rem',       /* 16px */
				'5': '1.25rem',    /* 20px */
				'6': '1.5rem',     /* 24px */
				'8': '2rem',       /* 32px */
				'10': '2.5rem',    /* 40px */
				'12': '3rem',      /* 48px */
				'16': '4rem',      /* 64px */
				'20': '5rem',      /* 80px */
				'24': '6rem',      /* 96px */
				'32': '8rem',      /* 128px */
				"gutter": "0.625rem",  /* 10px */
			},
			borderRadius: {
				'none': 'var(--radius-none)',
				'sm': 'var(--radius-sm)',
				'md': 'var(--radius-md)',
				'lg': 'var(--radius-lg)',
				'xl': 'var(--radius-xl)',
				'2xl': 'var(--radius-2xl)',
				'full': 'var(--radius-full)',
			},
			fontFamily: {
				sans: ["var(--font-sans)", ...fontFamily.sans],
				body: ["var(--font-body)", ...fontFamily.sans],
				mono: ["var(--font-mono)", ...fontFamily.mono]
			},
			fontSize: {
				xs: 'var(--font-size-xs)',
				sm: 'var(--font-size-sm)',
				base: 'var(--font-size-base)',
				lg: 'var(--font-size-lg)',
				xl: 'var(--font-size-xl)',
				'2xl': 'var(--font-size-2xl)',
				'3xl': 'var(--font-size-3xl)',
				'4xl': 'var(--font-size-4xl)',
				'5xl': 'var(--font-size-5xl)',
			},
			fontWeight: {
				thin: 'var(--font-weight-thin)',
				light: 'var(--font-weight-light)',
				normal: 'var(--font-weight-normal)',
				medium: 'var(--font-weight-medium)',
				semibold: 'var(--font-weight-semibold)',
				bold: 'var(--font-weight-bold)',
				extrabold: 'var(--font-weight-extrabold)',
			},
			lineHeight: {
				none: 'var(--line-height-none)',
				tight: 'var(--line-height-tight)',
				snug: 'var(--line-height-snug)',
				normal: 'var(--line-height-normal)',
				relaxed: 'var(--line-height-relaxed)',
				loose: 'var(--line-height-loose)',
			},
			boxShadow: {
				'glow-sm': '0 0 2px',
				'glow-md': '0 0 4px',
				'glow-lg': '0 0 8px',
			},
			keyframes: {
				"accordion-down": {
					from: { height: "0" },
					to: { height: "var(--bits-accordion-content-height)" },
				},
				"accordion-up": {
					from: { height: "var(--bits-accordion-content-height)" },
					to: { height: "0" },
				},
				"caret-blink": {
					"0%,70%,100%": { opacity: "1" },
					"20%,50%": { opacity: "0" },
				},
				"neon-pulse": {
					"0%": { boxShadow: "0 0 2px hsl(var(--primary))" },
					"50%": { boxShadow: "0 0 8px hsl(var(--primary))" },
					"100%": { boxShadow: "0 0 2px hsl(var(--primary))" }
				},
			},
			animation: {
				"accordion-down": "accordion-down 0.2s ease-out",
				"accordion-up": "accordion-up 0.2s ease-out",
				"caret-blink": "caret-blink 1.25s ease-out infinite",
				"neon-pulse": "neon-pulse 2s ease-in-out infinite",
			},
			transitionDuration: {
				'fast': '150ms',
				'normal': '300ms',
				'slow': '500ms',
				'slower': '1000ms',
			},
			transitionTimingFunction: {
				'in-out': 'cubic-bezier(0.4, 0, 0.2, 1)',
				'in': 'cubic-bezier(0.4, 0, 1, 1)',
				'out': 'cubic-bezier(0, 0, 0.2, 1)',
				'bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
				'elastic': 'cubic-bezier(0.68, -0.6, 0.32, 1.6)',
			},
		},
	},
	plugins: [tailwindcssAnimate],
	future: {
		hoverOnlyWhenSupported: true,
	}
};

export default config;
