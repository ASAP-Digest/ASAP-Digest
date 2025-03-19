<script>
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, children, ...restProps } = $props();
</script>

<div
	bind:this={ref}
	class={cn("bg-[hsl(var(--card))] text-[hsl(var(--card-foreground))] rounded-xl border border-[hsl(var(--border))] shadow-[0_2px_4px_-1px_rgba(0,0,0,0.1),_0_1px_2px_-1px_rgba(0,0,0,0.05)]", className)}
	{...restProps}
>
	{@render children?.()}
</div>
