<script>
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), children, child, class: className, ...restProps } = $props();

	const mergedProps = $derived({
		class: cn(
			"text-[hsl(var(--sidebar-foreground)/0.85)] ring-[hsl(var(--sidebar-ring))] flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium outline-none transition-[margin,opa] duration-200 ease-linear focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0 antialiased subpixel-antialiased",
			"group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0",
			className
		),
		"data-sidebar": "group-label",
		...restProps,
	});
</script>

{#if child}
	{@render child({ props: mergedProps })}
{:else}
	<div bind:this={ref} {...mergedProps}>
		{@render children?.()}
	</div>
{/if}
