<script>
	// Import the main primitive
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, inset, ...restProps } = $props();
</script>

<!-- Use Label from the main primitive (corresponds to GroupHeading in Shadcn) -->
<DropdownMenuPrimitive.Label
	bind:ref
	class={cn("px-2 py-2 text-sm font-semibold", inset && "pl-8", className)}
	{...restProps}
/>
