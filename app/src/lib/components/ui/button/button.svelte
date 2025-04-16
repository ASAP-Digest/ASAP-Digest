<script module>
	import { tv } from "tailwind-variants";

	export const buttonVariants = tv({
		base: "inline-flex items-center justify-center rounded-md whitespace-nowrap text-sm font-medium ring-offset-[hsl(var(--background))] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
		variants: {
			variant: {
				default: "bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] hover:bg-[hsl(var(--primary))]/90",
				destructive: "bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] hover:bg-[hsl(var(--destructive))]/90",
				outline: "border border-[hsl(var(--border))] bg-[hsl(var(--background))] hover:bg-[hsl(var(--accent))] hover:text-[hsl(var(--accent-foreground))]",
				secondary: "bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] hover:bg-[hsl(var(--secondary))]/80",
				ghost: "hover:bg-[hsl(var(--accent))] hover:text-[hsl(var(--accent-foreground))]",
				link: "text-[hsl(var(--primary))] underline-offset-4 hover:underline",
			},
			size: {
				default: "h-10 px-4 py-2",
				sm: "h-9 rounded-md px-3",
				lg: "h-11 rounded-md px-8",
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
