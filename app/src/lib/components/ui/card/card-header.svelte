<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardHeaderProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Card header content
	 * @property {Object} [restProps] - Additional props to pass to the div
	 */

	/** @type {CardHeaderProps} */
	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div bind:this={ref} class={cn("flex flex-col space-y-2 p-6 pb-0", className)} {...restProps}>
  {#if children}
    {@render children?.()}
  {/if}
  <!-- <p>CardHeader Content Placeholder</p> -->
</div>
