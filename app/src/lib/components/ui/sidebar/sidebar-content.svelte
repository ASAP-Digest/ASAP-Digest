<script>
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, children, collapsed = false, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	data-sidebar="content"
	data-collapsed={collapsed}
	class={cn(
		"flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden",
		collapsed ? "p-0" : "", // Remove padding when collapsed
		className
	)}
	{...restProps}
>
	{@render children?.()}
</div>
