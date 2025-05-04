<script>
	import { Avatar as AvatarPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} AvatarFallbackProps
	 * @property {string} [className] - Additional CSS classes
	 * @property {HTMLElement | null} [ref] - Bindable reference
	 * @property {Object} [rest] - All other props to spread to the element
	 */

	/** @type {AvatarFallbackProps} */
	let { class: className, ref = $bindable(null), ...restProps } = $props();
</script>

<AvatarPrimitive.Fallback
	bind:ref
	class={cn("bg-[hsl(var(--surface-2))] flex size-full items-center justify-center", className)}
	{...restProps}
/>
