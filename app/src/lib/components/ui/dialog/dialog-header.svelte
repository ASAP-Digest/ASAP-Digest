<script>
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogHeaderProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Header content
	 */
	
	/** @type {DialogHeaderProps} */
	let { 
		class: className,
		children,
		...restProps
	} = $props();
</script>

<div class={cn("flex flex-col space-y-2 text-center sm:text-left", className)} {...restProps}>
	{#if children}
		{@render children()}
	{/if}
</div>
