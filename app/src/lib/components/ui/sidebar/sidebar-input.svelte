<script>
	import { Input } from "$lib/components/ui/input/index.js";
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), value = $bindable(""), class: className, ...restProps } = $props();
</script>

<Input
	bind:ref
	bind:value
	data-sidebar="input"
	class={cn(
		"bg-[hsl(var(--background))] focus-visible:ring-[hsl(var(--sidebar-ring))] h-8 w-full shadow-none focus-visible:ring-2",
		className
	)}
	{...restProps}
/>
