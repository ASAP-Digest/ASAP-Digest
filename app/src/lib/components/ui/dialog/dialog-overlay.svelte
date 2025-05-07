<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	import { fade } from "svelte/transition";
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogOverlayProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {Function} [transition] - Transition function
	 * @property {Object} [transitionConfig] - Transition configuration
	 */
	
	/** @type {DialogOverlayProps} */
	let { 
		class: className, 
		transition = fade,
		transitionConfig = {
			duration: 150,
		},
		...restProps
	} = $props();
</script>

<DialogPrimitive.Overlay
	{transition}
	{transitionConfig}
	class={cn("bg-[hsl(var(--background)/0.8)] fixed inset-0 z-50 backdrop-blur-sm ", className)}
	{...restProps}
/>
