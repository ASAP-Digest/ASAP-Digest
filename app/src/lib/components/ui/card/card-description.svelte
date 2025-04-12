<script>
	import { cn } from "$lib/utils.js";

	let { 
		ref = $bindable(null), 
		class: className, 
		children = undefined,
		textContent = ""
	} = $props();
</script>

<p bind:this={ref} class={cn("text-[hsl(var(--muted-foreground))] text-sm", className)} {...restProps}>
	{#if children}
		{@render children?.()}
	{:else if textContent}
		{textContent}
	{/if}
	<!-- DEBUG: Static Content -->
</p>
