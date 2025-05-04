<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} InputProps
	 * @property {HTMLInputElement | null} [ref] - The input element reference
	 * @property {any} [value] - The input value
	 * @property {string} [type] - The input type
	 * @property {FileList} [files] - File list for file inputs
	 * @property {string} [class] - Additional CSS classes
	 * @property {Object} [restProps] - Additional props to pass to the input
	 */

	/** @type {InputProps} */
	let {
		ref = $bindable(null),
		value = $bindable(),
		type,
		files = $bindable(),
		class: className,
		...restProps
	} = $props();
</script>

{#if type === "file"}
	<input
		bind:this={ref}
		class={cn(
			"flex h-9 w-full rounded-[var(--radius-md)] border border-[hsl(var(--border))] bg-transparent px-3 py-1 text-[hsl(var(--text-1))] text-[var(--font-size-base)] shadow-[var(--shadow-sm)] transition-colors file:border-0 file:bg-transparent file:text-[var(--font-size-sm)] file:font-[var(--font-weight-semibold)] placeholder:text-[hsl(var(--text-3))] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[hsl(var(--ring))] disabled:cursor-not-allowed disabled:opacity-50",
			className
		)}
		type="file"
		bind:files
		bind:value
		{...restProps}
	/>
{:else}
	<input
		bind:this={ref}
		class={cn(
			"flex h-9 w-full rounded-[var(--radius-md)] border border-[hsl(var(--border))] bg-transparent px-3 py-1 text-[hsl(var(--text-1))] text-[var(--font-size-base)] shadow-[var(--shadow-sm)] transition-colors file:border-0 file:bg-transparent file:text-[var(--font-size-sm)] file:font-[var(--font-weight-semibold)] placeholder:text-[hsl(var(--text-3))] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[hsl(var(--ring))] disabled:cursor-not-allowed disabled:opacity-50",
			className
		)}
		{type}
		bind:value
		{...restProps}
	/>
{/if}
