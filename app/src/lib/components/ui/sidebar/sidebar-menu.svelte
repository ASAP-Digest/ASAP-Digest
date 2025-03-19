<script>
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), class: className, children, collapsed = false, ...restProps } = $props();
</script>

<ul
	bind:this={ref}
	data-sidebar="menu"
	data-collapsed={collapsed}
	class={cn(
		"flex w-full min-w-0 flex-col gap-1 list-none", 
		collapsed ? "p-0 items-center justify-center" : "",
		className
	)}
	{...restProps}
>
	{@render children?.()}
</ul>
