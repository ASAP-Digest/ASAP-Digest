<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardDescriptionProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Card description content
	 * @property {string} [textContent] - Alternative text content
	 * @property {Object} [restProps] - Additional props to pass to the element
	 */
	
	/** @type {CardDescriptionProps} */
	let { 
		ref = $bindable(null), 
		class: className, 
		children = undefined,
		textContent = "",
		...restProps
	} = $props();
</script>

<p bind:this={ref} class={cn("text-[hsl(var(--text-2))] text-sm", className)} {...restProps}>
	{#if children}
		{@render children?.()}
	{:else if textContent}
		{textContent}
	{/if}
	<!-- DEBUG: Static Content -->
</p>
