<script>
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogFooterProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Footer content
	 */
	
	/** @type {DialogFooterProps} */
	let { 
		class: className,
		children,
		...restProps
	} = $props();
</script>

<div
	class={cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2", className)}
	{...restProps}
>
	{#if children}
		{@render children()}
	{/if}
</div>
