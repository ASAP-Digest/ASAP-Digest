<script>
	import { alertVariants } from "./index.js";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} AlertProps
	 * @property {string} [variant="default"] - Alert variant style
	 * @property {string} [className] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Child content
	 * @property {Object} [rest] - All other props to spread to the element
	 */

	/** @type {AlertProps} */
	const { variant = "default", className, children, ...rest } = $props();
</script>

<div class={cn(alertVariants({ variant }), className)} {...rest} role="alert">
	{@render children?.() || (() => '')}
</div>
