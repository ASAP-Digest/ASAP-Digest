<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardContentProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Card content
	 * @property {Object} [restProps] - Additional props to pass to the div
	 */
	
	/** @type {CardContentProps} */
	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div bind:this={ref} class={cn("p-6", className)} {...restProps}>
	{@render children?.()}
</div>
