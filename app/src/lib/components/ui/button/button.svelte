<script module>
	import { tv } from "tailwind-variants";

	export const buttonVariants = tv({
		base: "inline-flex items-center justify-center rounded-[var(--radius-md)] whitespace-nowrap text-[var(--font-size-base)] font-[var(--font-weight-medium)] ring-offset-[hsl(var(--canvas-base))] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
		variants: {
			variant: {
				default: "bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))] hover:bg-[hsl(var(--brand-hover))]",
				destructive: "bg-[hsl(var(--functional-error))] text-[hsl(var(--functional-error-fg))] hover:bg-[hsl(var(--functional-error)/0.9)]",
				outline: "border border-[hsl(var(--border))] bg-[hsl(var(--canvas-base))] hover:bg-[hsl(var(--accent))] hover:text-[hsl(var(--accent-fg))]",
				secondary: "bg-[hsl(var(--accent))] text-[hsl(var(--accent-fg))] hover:bg-[hsl(var(--accent-hover))]",
				ghost: "hover:bg-[hsl(var(--surface-2))] hover:text-[hsl(var(--text-1))]",
				link: "text-[hsl(var(--link))] underline-offset-4 hover:underline hover:text-[hsl(var(--link-hover))]",
			},
			size: {
				default: "h-10 px-4 py-2",
				sm: "h-8 rounded-[var(--radius-sm)] px-3 py-1",
				lg: "h-12 rounded-[var(--radius-lg)] px-6 py-3",
				icon: "h-10 w-10",
			},
		},
		defaultVariants: {
			variant: "default",
			size: "default",
		},
	});
</script>

<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {"default" | "destructive" | "outline" | "secondary" | "ghost" | "link"} ButtonVariant
	 * @typedef {"default" | "sm" | "lg" | "icon"} ButtonSize
	 * @typedef {"button" | "submit" | "reset"} ButtonType
	 */

	let {
		class: className = /** @type {string | undefined | null} */ (''),
		variant = /** @type {ButtonVariant} */ ("default"),
		size = /** @type {ButtonSize} */ ("default"),
		ref = /** @type {HTMLButtonElement | HTMLAnchorElement | null} */ ($bindable(null)),
		href = /** @type {string | undefined | null} */ (undefined),
		type = /** @type {ButtonType} */ ("button"),
		children = /** @type {import('svelte').Snippet | undefined} */ (undefined),
		...restProps
	} = $props();
</script>

{#if href}
	<a bind:this={ref} class={cn(buttonVariants({ variant, size, className }))} {href} {...restProps}>
		{@render children?.()}
	</a>
{:else}
	<button
		bind:this={ref}
		class={cn(buttonVariants({ variant, size, className }))}
		{type}
		{...restProps}
	>
		{@render children?.()}
	</button>
{/if}
