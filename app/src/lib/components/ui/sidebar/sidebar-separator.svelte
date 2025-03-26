<script>
	// Import directly from the .svelte file
	import Separator from "$lib/components/ui/separator/separator.svelte";
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), class: className, ...restProps } = $props();
</script>

<Separator
	bind:ref
	data-sidebar="separator"
	class={cn("bg-[hsl(var(--sidebar-border))] mx-2 w-auto", className)}
	{...restProps}
/>
