<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.SubContent
	bind:ref
	class={cn(
		"bg-popover text-popover-foreground z-50 min-w-[8rem] rounded-md border p-2 shadow-lg focus:outline-none",
		className
	)}
	{...restProps}
/>
