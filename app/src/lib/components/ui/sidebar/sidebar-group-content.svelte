<script>
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	data-sidebar="group-content"
	class={cn("w-full text-sm antialiased subpixel-antialiased", className)}
	{...restProps}
>
	{@render children?.()}
</div>
