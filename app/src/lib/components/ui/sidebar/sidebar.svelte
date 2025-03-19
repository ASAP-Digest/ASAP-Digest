<script module>
	import { tv } from "tailwind-variants";

	export const sidebarVariants = tv({
		base: 'bg-[hsl(var(--sidebar))] text-[hsl(var(--sidebar-foreground))] flex h-full w-[var(--sidebar-width)] flex-col',
	});
</script>

<script>
	import * as Sheet from "$lib/components/ui/sheet/index.js";
	import { cn } from "$lib/utils.js";
	import { SIDEBAR_WIDTH_MOBILE } from "./constants.js";
	import { useSidebar } from "./context.svelte.js";
	import Trigger from "./sidebar-trigger.svelte";

	/** @type {import('svelte/elements').SvelteHTMLElements['div']['standard']} */
	let { ref = $bindable(null), class: className, collapsible = "default", children, ...restProps } = $props();

	// Safely get the sidebar context
	const sidebar = useSidebar();
	
	// Fallback for isMobile when sidebar is undefined
	const isMobile = $derived(sidebar?.isMobile ?? false);
	
	// Fallback for state when sidebar is undefined
	const sidebarState = $derived(sidebar?.state ?? 'collapsed');
	
	// Computed class with appropriate fallbacks
	const sidebarClass = $derived(
		cn(
			sidebarVariants(),
			className
		)
	);
	
	// Log warning if sidebar context is missing
	$effect(() => {
		if (!sidebar && typeof window !== 'undefined') {
			console.warn('Sidebar context is undefined. Make sure to wrap this component with a SidebarProvider.');
		}
	});
</script>

{#if collapsible === "none"}
	<div
		class={sidebarClass}
		bind:this={ref}
		data-sidebar="sidebar"
		{...restProps}
	>
		{@render children?.()}
	</div>
{:else if collapsible === "default"}
	<div
		class={cn(
			sidebarClass,
			"block"
		)}
		data-sidebar="sidebar"
		data-state={sidebarState}
		{...restProps}
	>
		{@render children?.()}
	</div>

	{#if isMobile}
		<Sheet.Root open={sidebar?.openMobile ?? false} onOpenChange={(open) => sidebar?.setOpenMobile?.(open)}>
			<Sheet.Trigger asChild={false} class="fixed left-4 top-4 z-40">
				<Trigger class="lg:hidden" />
			</Sheet.Trigger>
			<Sheet.Content
				side="left"
				class="p-0 focus-visible:outline-none"
				style={`width: ${SIDEBAR_WIDTH_MOBILE}px;`}
				portalProps={{}}
			>
				<div class="h-full" data-sidebar="sidebar">
					{@render children?.()}
				</div>
			</Sheet.Content>
		</Sheet.Root>
	{/if}
{/if}
