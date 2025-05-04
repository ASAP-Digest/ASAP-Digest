<script>
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} AlertDescriptionProps
	 * @property {string} [className] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Child content
	 * @property {Object} [rest] - All other props to spread to the element
	 */
	
	/** @type {AlertDescriptionProps} */
	const { className, children, ...rest } = $props();
</script>

<div class={cn("text-sm [&_p]:leading-relaxed", className)} {...rest}>
	{@render children?.() || (() => '')}
</div>
