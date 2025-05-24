<script>
	import { ChevronUp } from "$lib/utils/lucide-compat.js";
	import Icon from "$lib/components/ui/icon/icon.svelte";
	import { Select as SelectPrimitive } from "bits-ui";
	import { cn } from "$lib/utils.js";

	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<button
	bind:this={ref}
	class={cn("flex cursor-default items-center justify-center py-1", className)}
	{...restProps}
>
	<Icon icon={ChevronUp} class="size-4" />
</button>
