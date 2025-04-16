<script>
	/**
	 * @typedef {Object} Props - Props for the WidgetItem component.
	 * @property {import('svelte').Snippet | undefined} [children] - Optional content snippet to render inside the widget item.
	 */

	/** @type {Props} */
	const { children } = $props();
</script>

<div class="widget-item rounded-[var(--radius-lg)] border bg-[hsl(var(--card))] p-4 text-[hsl(var(--card-foreground))] shadow-[var(--shadow-sm)]">
	{#if children}
		{@render children()}
	{/if}
	<!-- Widget item content goes here -->
</div> 