<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Separator
	bind:ref
	class={cn("bg-muted -mx-1 my-2 h-px", className)}
	{...restProps}
/>
