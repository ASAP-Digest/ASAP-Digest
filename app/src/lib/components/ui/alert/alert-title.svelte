<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} AlertTitleProps
	 * @property {string} [level="h5"] - Heading level element
	 * @property {string} [className] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Child content 
	 * @property {Object} [rest] - All other props to spread to the element
	 */

	/** @type {AlertTitleProps} */
	const { level = "h5", className, children, ...rest } = $props();
</script>

<svelte:element
	this={level}
	class={cn("mb-1 font-[var(--font-weight-semibold)] leading-[var(--line-height-heading)] tracking-[var(--tracking-tight)]", className)}
	{...rest}
>
	{@render children?.() || (() => '')}
</svelte:element>
