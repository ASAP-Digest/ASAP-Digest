<script>
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	class={cn("bg-[hsl(var(--primary)/0.1)] animate-pulse rounded-md", className)}
	{...restProps}
></div>
