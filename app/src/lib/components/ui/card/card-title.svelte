<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} CardTitleProps
	 * @property {HTMLElement | null} [ref] - The element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {number} [level] - The heading level (1-6)
	 * @property {import('svelte').Snippet} [children] - Card title content
	 * @property {Object} [restProps] - Additional props to pass to the element
	 */
	
	/** @type {CardTitleProps} */
	let { ref = $bindable(null), class: className, level = 3, children, ...restProps } = $props();
</script>

<div
	role="heading"
	aria-level={level}
	bind:this={ref}
	class={cn("font-[var(--font-weight-semibold)] text-[var(--font-size-lg)] leading-[var(--line-height-heading)] tracking-[var(--tracking-tight)]", className)}
	{...restProps}
>
	{@render children?.()}
</div>
