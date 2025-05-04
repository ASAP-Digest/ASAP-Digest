<script>
	import { Checkbox as CheckboxPrimitive } from "bits-ui";
	import Check from "svelte-radix/Check.svelte";
	import Minus from "svelte-radix/Minus.svelte";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CheckboxProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {boolean} [checked] - Whether the checkbox is checked
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {Object} [rest] - Additional props to pass to the checkbox
	 */
	
	/** @type {CheckboxProps} */
	let { 
		class: className, 
		checked = $bindable(false),
		ref = $bindable(null),
		...rest
	} = $props();
</script>

<CheckboxPrimitive.Root
	bind:ref
	class={cn(
		"peer box-content h-4 w-4 shrink-0 rounded-[var(--radius-sm)] border border-[hsl(var(--border))] shadow-[var(--shadow-sm)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 data-[disabled=true]:cursor-not-allowed data-[disabled=true]:opacity-50 data-[state=checked]:bg-[hsl(var(--brand))] data-[state=checked]:text-[hsl(var(--brand-fg))] transition-colors duration-[var(--duration-fast)] ease-[var(--ease-out)]",
		className
	)}
	bind:checked
	{...rest}
>
	<CheckboxPrimitive.Indicator
		class={cn("flex h-4 w-4 items-center justify-center text-current")}
		let:isChecked
		let:isIndeterminate
	>
		{#if isIndeterminate}
			<Minus class="h-3.5 w-3.5" />
		{:else}
			<Check class={cn("h-3.5 w-3.5", !isChecked && "text-transparent")} />
		{/if}
	</CheckboxPrimitive.Indicator>
</CheckboxPrimitive.Root>
