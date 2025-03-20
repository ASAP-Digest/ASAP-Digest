<script>
	import { cn } from "$lib/utils.js";
	let {
		ref = $bindable(null),
		children,
		child,
		class: className,
		size = "md",
		isActive,
		...restProps
	} = $props();

	const mergedProps = $derived({
		class: cn(
			"text-[hsl(var(--sidebar-foreground))] ring-[hsl(var(--sidebar-ring))] hover:bg-[hsl(var(--sidebar-accent))] hover:text-[hsl(var(--sidebar-accent-foreground))] active:bg-[hsl(var(--sidebar-accent))] active:text-[hsl(var(--sidebar-accent-foreground))] [&>svg]:text-[hsl(var(--sidebar-accent-foreground))] flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-none transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0",
			"data-[active=true]:bg-[hsl(var(--sidebar-accent))] data-[active=true]:text-[hsl(var(--sidebar-accent-foreground))]",
			size === "sm" && "text-xs",
			size === "md" && "text-sm",
			"group-data-[collapsible=icon]:hidden",
			className
		),
		"data-sidebar": "menu-sub-button",
		"data-size": size,
		"data-active": isActive,
		...restProps,
	});
</script>

{#if child}
	{@render child({ props: mergedProps })}
{:else}
	<a bind:this={ref} {...mergedProps}>
		{@render children?.()}
	</a>
{/if}
