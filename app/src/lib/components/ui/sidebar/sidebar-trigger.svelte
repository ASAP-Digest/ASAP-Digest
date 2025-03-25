<script>
	import { Button } from "$lib/components/ui/button/index.js";
	import { cn } from "$lib/utils.js";
	import { PanelLeft } from "$lib/utils/lucide-icons.js";
	import { useSidebar } from "./context.svelte.js";

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
	<PanelLeft />
	<span class="sr-only">Toggle Sidebar</span>
</Button>
