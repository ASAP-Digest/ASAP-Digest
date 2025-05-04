<script>
	import { Avatar as AvatarPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	/**
	 * @typedef {Object} AvatarProps
	 * @property {string} [className] - Additional CSS classes
	 * @property {HTMLElement | null} [ref] - Bindable reference
	 * @property {string} [loadingStatus] - Loading status of the avatar
	 * @property {Object} [rest] - All other props to spread to the element
	 */

	/** @type {AvatarProps} */
	let {
		class: className,
		ref = $bindable(null),
		loadingStatus = $bindable("loading"),
		...restProps
	} = $props();
</script>

<AvatarPrimitive.Root
	bind:loadingStatus
	bind:ref
	class={cn("relative flex size-10 shrink-0 overflow-hidden rounded-full", className)}
	{...restProps}
/>
