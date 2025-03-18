<script>
	import { Dialog as SheetPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<SheetPrimitive.Title
	bind:ref
	class={cn("text-[hsl(var(--foreground))] text-lg font-semibold", className)}
	{...restProps}
/>
