<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	
	/**
	 * @typedef {Object} DialogPortalProps
	 * @property {import('svelte').Snippet} [children] - The portal content
	 */
	
	/** @type {DialogPortalProps} */
	let { children, ...restProps } = $props();
</script>

<DialogPrimitive.Portal {...restProps}>
	{#if children}
		{@render children()}
	{/if}
</DialogPrimitive.Portal>
