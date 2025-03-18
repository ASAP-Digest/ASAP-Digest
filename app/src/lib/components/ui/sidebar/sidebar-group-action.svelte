<script>
	import { cn } from "$lib/utils.js";
	let { ref = $bindable(null), class: className, children, child, ...restProps } = $props();

	const propObj = $derived({
		class: cn(
			"text-[hsl(var(--sidebar-foreground))] ring-[hsl(var(--sidebar-ring))] hover:bg-[hsl(var(--sidebar-accent))] hover:text-[hsl(var(--sidebar-accent-foreground))] absolute right-3 top-3.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 outline-none transition-transform focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0",
			// Increases the hit area of the button on mobile.
			"after:absolute after:-inset-2 after:md:hidden",
			"group-data-[collapsible=icon]:hidden",
			className
		),
		"data-sidebar": "group-action",
		...restProps,
	});
</script>

{#if child}
	{@render child({ props: propObj })}
{:else}
	<button bind:this={ref} {...propObj}>
		{@render children?.()}
	</button>
{/if}
