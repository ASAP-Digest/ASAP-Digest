<script>
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	data-sidebar="header"
	class={cn("flex flex-col gap-4 p-4 transition-all duration-200", className)}
	{...restProps}
>
	{@render children?.()}
</div>
