<script>
	import { cn } from "$lib/utils.js";
	
	/**
	 * @typedef {Object} SidebarMenuBadgeProps
	 * @property {HTMLElement | null} [ref] - Element reference
	 * @property {string} [class] - Additional CSS classes
	 * @property {import('svelte').Snippet} [children] - Badge content
	 * @property {Object} [rest] - Additional props to pass to the badge
	 */
	
	/** @type {SidebarMenuBadgeProps} */
	let { ref = $bindable(null), class: className, children, ...rest } = $props();
</script>

<div
	bind:this={ref}
	data-sidebar="menu-badge"
	class={cn(
		"text-[hsl(var(--sidebar-foreground))] pointer-events-none absolute right-1 flex h-5 min-w-5 select-none items-center justify-center rounded-md px-1 text-xs font-medium tabular-nums",
		"peer-hover/menu-button:text-[hsl(var(--sidebar-accent-foreground))] peer-data-[active=true]/menu-button:text-[hsl(var(--sidebar-accent-foreground))]",
		"peer-data-[size=sm]/menu-button:top-1",
		"peer-data-[size=default]/menu-button:top-1.5",
		"peer-data-[size=lg]/menu-button:top-2.5",
		"group-data-[collapsible=icon]:hidden",
		className
	)}
	{...rest}
>
	{@render children?.()}
</div>
