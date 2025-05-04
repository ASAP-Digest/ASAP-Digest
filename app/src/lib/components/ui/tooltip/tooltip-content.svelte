<script>
	import { Tooltip as TooltipPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";
	import { fly } from "svelte/transition";
	import { cubicOut } from "svelte/easing";

	/**
	 * @typedef {Object} TooltipContentProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {number} [sideOffset] - Offset from the side
	 * @property {Object} [rest] - Additional props to pass to the tooltip
	 */
	
	/** @type {TooltipContentProps} */
	let { ref = $bindable(null), class: className, sideOffset = 4, ...rest } = $props();
</script>

<TooltipPrimitive.Content
	bind:ref
	{sideOffset}
	class={cn(
		"z-[var(--z-tooltip)] overflow-hidden rounded-[var(--radius-sm)] bg-[hsl(var(--brand))] px-3 py-1.5 text-[var(--font-size-xs)] text-[hsl(var(--brand-fg))] shadow-[var(--shadow-sm)] animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2",
		"body-sidebar-collapsed:z-[9999]",
		className
	)}
	{...rest}
/>
