<script>
	import { cn } from "$lib/utils.js";
	// Import the main primitive
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui"; 

	/**
	 * @typedef {Object} DropdownMenuContentProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes 
	 * @property {number} [sideOffset] - Offset from the side
	 * @property {Object} [restProps] - Additional props to spread
	 */

	/** @type {DropdownMenuContentProps} */
	let {
		ref = $bindable(null),
		class: className,
		sideOffset = 4,
		// portalProps removed, Portal wrapper is removed below
		...restProps
	} = $props();
</script>

<!-- Use Content from the main primitive. Remove explicit Portal wrapper. -->
<DropdownMenuPrimitive.Content
	bind:ref
	{sideOffset}
	class={cn(
		"bg-[hsl(var(--surface-1))] text-[hsl(var(--text-1))] z-50 min-w-[8rem] overflow-hidden rounded-[var(--radius-md)] border border-[hsl(var(--border))] p-1 shadow-[var(--shadow-md)]",
		"data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 outline-none",
		className
	)}
	{...restProps}
/>
