<script>
	import { Dialog as DialogPrimitive } from "bits-ui";
	import { fade } from "svelte/transition";
	import { cn } from "$lib/utils.js";
	let className = undefined;
	export let transition = fade;
	export let transitionConfig = {
		duration: 150,
	};
	export { className as class };
</script>

<DialogPrimitive.Overlay
	{transition}
	{transitionConfig}
	class={cn("bg-[hsl(var(--background)/0.8)] fixed inset-0 z-50 backdrop-blur-sm ", className)}
	{...$$restProps}
/>
