<script>
	import { Label as LabelPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} LabelProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {HTMLLabelElement | null} [ref] - Element reference
	 * @property {Object} [rest] - Additional props to pass to the label
	 * @property {import('svelte').Snippet | undefined} [children] - Optional content snippet
	 */
	
	/** @type {LabelProps} */
	let { class: className, ref = $bindable(null), children = undefined, ...rest } = $props();
</script>

<LabelPrimitive.Root
	bind:ref
	class={cn(
		"text-[var(--font-size-sm)] font-[var(--font-weight-regular)] leading-none text-[hsl(var(--text-1))] peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
		className
	)}
	{...rest}
>
	{#if children}
		{@render children()}
	{/if}
</LabelPrimitive.Root>
