<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogTitleProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Title content
	 */
	
	/** @type {DialogTitleProps} */
	let { 
		class: className,
		children,
		...restProps
	} = $props();
</script>

<DialogPrimitive.Title
	class={cn("text-lg font-semibold leading-none tracking-tight", className)}
	{...restProps}
>
	{#if children}
		{@render children()}
	{/if}
</DialogPrimitive.Title>
