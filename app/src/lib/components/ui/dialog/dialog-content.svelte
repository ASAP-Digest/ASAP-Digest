<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	import { X } from "$lib/utils/lucide-compat.js";
	import Icon from "$lib/components/ui/icon/icon.svelte";
	import * as Dialog from "./index.js";
	import { cn, flyAndScale } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} DialogContentProps
	 * @property {string} [class] - Additional CSS classes
	 * @property {Function} [transition] - Transition function
	 * @property {Object} [transitionConfig] - Transition configuration
	 * @property {import('svelte').Snippet} [children] - The dialog content
	 * @property {Object} [rest] - Additional props to forward
	 */
	
	/** @type {DialogContentProps} */
	let { 
		class: className, 
		transition = flyAndScale,
		transitionConfig = {
			duration: 200,
		},
		children,
		...rest
	} = $props();
</script>

<Dialog.Portal>
	<Dialog.Overlay />
	<DialogPrimitive.Content
		{transition}
		{transitionConfig}
		class={cn(
			"fixed left-[50%] top-[50%] z-[var(--z-modal)] grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border border-[hsl(var(--border))] bg-[hsl(var(--surface-2))] p-6 shadow-[var(--shadow-lg)] sm:rounded-lg md:w-full",
			className
		)}
		{...rest}
	>
		{#if children}
			{@render children()}
		{/if}
		<DialogPrimitive.Close
			class="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-[hsl(var(--ring))] focus:ring-offset-2 focus:ring-offset-[hsl(var(--surface-2))] disabled:pointer-events-none data-[state=open]:bg-[hsl(var(--surface-3))] data-[state=open]:text-[hsl(var(--text-2))]"
		>
			<Icon icon={X} class="h-4 w-4" />
			<span class="sr-only">Close</span>
		</DialogPrimitive.Close>
	</DialogPrimitive.Content>
</Dialog.Portal>
