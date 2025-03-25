<script module>
	import { tv } from "tailwind-variants";

	export const sidebarVariants = tv({
		base: 'bg-[hsl(var(--sidebar))] text-[hsl(var(--sidebar-foreground))] flex h-full w-[var(--sidebar-width)] flex-col transition-all duration-200 antialiased subpixel-antialiased font-sans',
	});
</script>

<script>
	import { cn } from "$lib/utils.js";
	
	/** @type {any} */
	let { ref = $bindable(null), class: className, collapsible = "default", children, ...restProps } = $props();

	// Safely get the sidebar context
	let instance = $derived(Math.random());
</script>

{#if collapsible === "none"}
	<div
		bind:this={ref}
		data-sidebar="sidebar"
		class={cn("w-[var(--sidebar-width)] flex h-full min-h-dvh flex-col shadow-none", className)}
		{...restProps}
	>
		{@render children?.()}
	</div>
{:else if collapsible === "icon"}
	<div
		bind:this={ref}
		data-sidebar="sidebar"
		data-collapsible={collapsible}
		class={cn("w-[var(--sidebar-width)] flex h-full min-h-dvh flex-col shadow-none group", className)}
		{...restProps}
	>
		{@render children?.()}
	</div>
{:else}
	<div
		bind:this={ref}
		data-sidebar="sidebar"
		class={cn("w-[var(--sidebar-width)] flex h-full min-h-dvh flex-col shadow-none", className)}
		{...restProps}
	>
		{@render children?.()}
	</div>
{/if}
