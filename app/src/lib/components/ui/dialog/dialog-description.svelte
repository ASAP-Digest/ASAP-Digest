<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogDescriptionProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Description content
	 */
	
	/** @type {DialogDescriptionProps} */
	let { 
		class: className,
		children,
		...restProps
	} = $props();
</script>

<DialogPrimitive.Description
	class={cn("text-[hsl(var(--muted-foreground))] text-sm", className)}
	{...restProps}
>
	{#if children}
		{@render children()}
	{/if}
</DialogPrimitive.Description>
