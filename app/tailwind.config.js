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
				// Canvas colors
				"canvas-base": "hsl(var(--canvas-base) / <alpha-value>)",
				"canvas-fg": "hsl(var(--canvas-fg) / <alpha-value>)",
				
				// Brand colors
				"brand": "hsl(var(--brand) / <alpha-value>)",
				"brand-fg": "hsl(var(--brand-fg) / <alpha-value>)",
				"brand-hover": "hsl(var(--brand-hover) / <alpha-value>)",
				
				// Accent colors
				"accent": "hsl(var(--accent) / <alpha-value>)",
				"accent-fg": "hsl(var(--accent-fg) / <alpha-value>)",
				"accent-hover": "hsl(var(--accent-hover) / <alpha-value>)",
				
				// Link colors
				"link": "hsl(var(--link) / <alpha-value>)",
				"link-fg": "hsl(var(--link-fg) / <alpha-value>)",
				"link-hover": "hsl(var(--link-hover) / <alpha-value>)",
				
				// Visited colors
				"visited": "hsl(var(--visited) / <alpha-value>)",
				"visited-fg": "hsl(var(--visited-fg) / <alpha-value>)",
				
				// Functional colors
				"functional-error": "hsl(var(--functional-error) / <alpha-value>)",
				"functional-error-fg": "hsl(var(--functional-error-fg) / <alpha-value>)",
				"functional-success": "hsl(var(--functional-success) / <alpha-value>)",
				"functional-success-fg": "hsl(var(--functional-success-fg) / <alpha-value>)",
				
				// Surface colors
				"surface-1": "hsl(var(--surface-1) / <alpha-value>)",
				"surface-2": "hsl(var(--surface-2) / <alpha-value>)",
				"surface-3": "hsl(var(--surface-3) / <alpha-value>)",
				
				// Text colors
				"text-1": "hsl(var(--text-1) / <alpha-value>)",
				"text-2": "hsl(var(--text-2) / <alpha-value>)",
				"text-3": "hsl(var(--text-3) / <alpha-value>)",
				"text-disabled": "hsl(var(--text-disabled) / <alpha-value>)",
				
				// UI Element colors
				"border": "hsl(var(--border) / <alpha-value>)",
				"input-bg": "hsl(var(--input-bg) / <alpha-value>)",
				"input-border": "hsl(var(--input-border) / <alpha-value>)",
				"input-border-focus": "hsl(var(--input-border-focus) / <alpha-value>)",
				"ring": "hsl(var(--ring) / <alpha-value>)",
				
				// Legacy mappings (for backward compatibility)
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
				popover: {
					DEFAULT: "hsl(var(--popover) / <alpha-value>)",
					foreground: "hsl(var(--popover-foreground) / <alpha-value>)"
				},
				card: {
					DEFAULT: "hsl(var(--card) / <alpha-value>)",
					foreground: "hsl(var(--card-foreground) / <alpha-value>)"
				},
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
			},
			borderRadius: {
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
				'xs': 'var(--font-size-xs)',
				'sm': 'var(--font-size-sm)',
				'base': 'var(--font-size-base)',
				'lg': 'var(--font-size-lg)',
				'xl': 'var(--font-size-xl)',
			},
			fontWeight: {
				'regular': 'var(--font-weight-regular)',
				'medium': 'var(--font-weight-medium)',
				'semibold': 'var(--font-weight-semibold)',
			},
			lineHeight: {
				'heading': 'var(--line-height-heading)',
				'body': 'var(--line-height-body)',
			},
			letterSpacing: {
				'tight': 'var(--tracking-tight)',
				'normal': 'var(--tracking-normal)',
				'wide': 'var(--tracking-wide)',
			},
			boxShadow: {
				'sm': 'var(--shadow-sm)',
				'md': 'var(--shadow-md)',
				'lg': 'var(--shadow-lg)',
				'glow-sm': '0 0 2px',
				'glow-md': '0 0 4px',
				'glow-lg': '0 0 8px',
			},
			transitionDuration: {
				'fast': 'var(--duration-fast)',
				'normal': 'var(--duration-normal)',
				'slow': 'var(--duration-slow)',
			},
			transitionTimingFunction: {
				'in-out': 'var(--ease-in-out)',
				'in': 'var(--ease-in)',
				'out': 'var(--ease-out)',
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
					"0%": { boxShadow: "0 0 2px hsl(var(--brand))" },
					"50%": { boxShadow: "0 0 8px hsl(var(--brand))" },
					"100%": { boxShadow: "0 0 2px hsl(var(--brand))" }
				},
			},
			animation: {
				"accordion-down": "accordion-down 0.2s ease-out",
				"accordion-up": "accordion-up 0.2s ease-out",
				"caret-blink": "caret-blink 1.25s ease-out infinite",
				"neon-pulse": "neon-pulse 2s ease-in-out infinite",
			},
		},
	},
	plugins: [tailwindcssAnimate],
	future: {
		hoverOnlyWhenSupported: true,
	}
};

export default config;
