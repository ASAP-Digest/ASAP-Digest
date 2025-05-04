<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Card content
	 * @property {Object} [restProps] - Additional props to pass to the div
	 */
	
	/** @type {CardProps} */
	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	class={cn("bg-[hsl(var(--surface-1))] text-[hsl(var(--text-1))] rounded-[var(--radius-lg)] border border-[hsl(var(--border))] shadow-[var(--shadow-md)]", className)}
	{...restProps}
>
	{@render children?.()}
</div>
