<script>
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), class: className, children, collapsed = false, ...restProps } = $props();
</script>

<li
	bind:this={ref}
	data-sidebar="menu-item"
	data-collapsed={collapsed}
	class={cn("list-none", className)}
	{...restProps}
>
	{@render children?.()}
</li>
