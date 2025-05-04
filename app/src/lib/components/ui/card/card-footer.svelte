<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardFooterProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Card footer content
	 * @property {Object} [restProps] - Additional props to pass to the div
	 */

	/** @type {CardFooterProps} */
	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div bind:this={ref} class={cn("flex items-center p-6 pt-0", className)} {...restProps}>
	{@render children?.()}
</div>
