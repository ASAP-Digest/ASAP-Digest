<script>
	// Import directly from the .svelte file
	import Button from "$lib/components/ui/button/button.svelte";
	import { cn } from "$lib/utils.js";
	import { PanelLeft } from "$lib/utils/lucide-compat.js";
	import { useSidebar } from "./context.svelte.js";
	import Icon from '$lib/components/ui/icon/icon.svelte';

	let { ref = $bindable(null), class: className, onclick, ...restProps } = $props();

	const sidebar = useSidebar();
</script>

<Button
	type="button"
	onclick={(e) => {
		onclick?.(e);
		sidebar.toggle();
	}}
	data-sidebar="trigger"
	variant="ghost"
	size="icon"
	class={cn("h-7 w-7", className)}
	{...restProps}
>
	<Icon icon={PanelLeft} class="w-4 h-4" />
	<span class="sr-only">Toggle Sidebar</span>
</Button>
