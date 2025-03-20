import { fontFamily } from "tailwindcss/defaultTheme";
import tailwindcssAnimate from "tailwindcss-animate";

/** @type {import('tailwindcss').Config} */
const config = {
	darkMode: ["class"],
	content: ["./src/**/*.{html,js,svelte,ts}"],
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
				DEFAULT: 'calc(var(--spacing-unit) * 4)',
				md: 'calc(var(--spacing-unit) * 6)',
				lg: 'calc(var(--spacing-unit) * 8)'
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
				sidebar: {
					DEFAULT: "hsl(var(--sidebar-background))",
					foreground: "hsl(var(--sidebar-foreground))",
					primary: "hsl(var(--sidebar-primary))",
					"primary-foreground": "hsl(var(--sidebar-primary-foreground))",
					accent: "hsl(var(--sidebar-accent))",
					"accent-foreground": "hsl(var(--sidebar-accent-foreground))",
					border: "hsl(var(--sidebar-border))",
					ring: "hsl(var(--sidebar-ring))",
				},
				"neon-yellow": "hsl(var(--neon-yellow))",
				"neon-green": "hsl(var(--neon-green))",
				"neon-blue": "hsl(var(--neon-blue))",
			},
			spacing: {
				'1': 'calc(var(--spacing-unit) * 1)',
				'2': 'calc(var(--spacing-unit) * 2)',
				'3': 'calc(var(--spacing-unit) * 3)',
				'4': 'calc(var(--spacing-unit) * 4)',
				'5': 'calc(var(--spacing-unit) * 5)',
				'6': 'calc(var(--spacing-unit) * 6)',
				'8': 'calc(var(--spacing-unit) * 8)',
				'10': 'calc(var(--spacing-unit) * 10)',
				'12': 'calc(var(--spacing-unit) * 12)',
				'16': 'calc(var(--spacing-unit) * 16)',
				'20': 'calc(var(--spacing-unit) * 20)',
				'24': 'calc(var(--spacing-unit) * 24)',
				'32': 'calc(var(--spacing-unit) * 32)',
				"gutter": "calc(var(--spacing-unit) * 2.5)",
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
				'glow-sm': 'var(--glow-sm)',
				'glow-md': 'var(--glow-md)',
				'glow-lg': 'var(--glow-lg)',
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
				'fast': 'var(--duration-fast)',
				'normal': 'var(--duration-normal)',
				'slow': 'var(--duration-slow)',
				'slower': 'var(--duration-slower)',
			},
			transitionTimingFunction: {
				'in-out': 'var(--ease-in-out)',
				'in': 'var(--ease-in)',
				'out': 'var(--ease-out)',
				'bounce': 'var(--ease-bounce)',
				'elastic': 'var(--ease-elastic)',
			},
		},
	},
	plugins: [tailwindcssAnimate],
};

export default config;
