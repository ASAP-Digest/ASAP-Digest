<script>
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} SkeletonProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {Object} [rest] - Additional props to pass to the skeleton div
	 */
	
	/** @type {SkeletonProps} */
	let { ref = $bindable(null), class: className, ...rest } = $props();
</script>

<div
	bind:this={ref}
	class={cn("animate-pulse rounded-[var(--radius-md)] bg-[hsl(var(--surface-2)/0.7)]", className)}
	{...rest}
></div>
