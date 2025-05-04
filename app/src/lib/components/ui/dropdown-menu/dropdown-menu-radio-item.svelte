<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";
	import { Circle } from "$lib/utils/lucide-compat.js";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} DropdownMenuRadioItemProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Child content
	 * @property {Object} [rest] - Additional props to pass to the item
	 */

	/** @type {DropdownMenuRadioItemProps} */
	let { ref = $bindable(null), class: className, children: childrenProp, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.RadioItem
	bind:ref
	class={cn(
		"relative flex cursor-default select-none items-center rounded-[var(--radius-sm)] py-1.5 pl-8 pr-2 text-[var(--font-size-sm)] outline-none data-[highlighted]:bg-[hsl(var(--accent))] data-[highlighted]:text-[hsl(var(--accent-fg))] data-[disabled]:pointer-events-none data-[disabled]:opacity-50",
		className
	)}
	{...restProps}
>
	{#snippet children({ checked })}
		<span class="absolute left-2 flex size-3.5 items-center justify-center">
			{#if checked}
				<Circle class="size-2 fill-current" />
			{/if}
		</span>
		{@render childrenProp?.({ checked })}
	{/snippet}
</DropdownMenuPrimitive.RadioItem>
