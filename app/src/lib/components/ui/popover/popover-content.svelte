<script>
	import { Popover as PopoverPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { 
		ref = $bindable(null),
		class: className, 
		align = "center", 
		side = "bottom", 
		sideOffset = 4, 
		alignOffset = 0, 
		avoidCollisions = true,
		portalProps = {},
		children,
		...restProps 
	} = $props();
</script>

	<PopoverPrimitive.Content
		bind:ref
		{align}
		{side}
		{sideOffset}
		{alignOffset}
		{avoidCollisions}
		class={cn(
			"data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 bg-popover text-popover-foreground z-50 w-72 rounded-md border p-4 shadow-md outline-none",
			className
		)}
		{...restProps}
	>
		{@render children?.()}
	</PopoverPrimitive.Content>
