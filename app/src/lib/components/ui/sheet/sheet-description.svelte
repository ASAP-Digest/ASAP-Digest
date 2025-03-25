<script>
	import { Dialog as SheetPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<p
	bind:this={ref}
	id={label ? $ids.description : undefined}
	class={cn("text-[hsl(var(--muted-foreground))] text-sm", className)}
	{...restProps}
>
	{@render children?.()}
</p>
