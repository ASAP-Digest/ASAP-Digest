<script>
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	class={cn("bg-[hsl(var(--card))] text-[hsl(var(--card-foreground))] rounded-lg border border-[hsl(var(--border))] shadow", className)}
	{...restProps}
>
	{@render children?.()}
</div>
