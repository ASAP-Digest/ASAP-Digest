<script>
	import { cn } from "$lib/utils.js";
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";

	/**
	 * @typedef {Object} DropdownMenuItemProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {boolean} [inset] - Whether the item should be inset
	 * @property {Object} [restProps] - Additional props to pass to the element
	 */

	/** @type {DropdownMenuItemProps} */
	let { ref = $bindable(null), class: className, inset = false, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Item
	bind:ref
	class={cn(
		"relative flex cursor-default select-none items-center gap-2 rounded-[var(--radius-sm)] px-2 py-1.5 text-[var(--font-size-sm)] outline-none transition-colors data-[highlighted]:bg-[hsl(var(--accent))] data-[highlighted]:text-[hsl(var(--accent-fg))] data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0",
		inset && "pl-8",
		className
	)}
	{...restProps}
/>
