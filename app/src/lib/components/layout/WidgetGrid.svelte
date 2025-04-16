<script>
	/**
	 * @typedef {Object} WidgetGridProps
	 * @property {string} [class] - Optional CSS classes to apply to the grid container.
	 * @property {import('svelte').Snippet} [children] - The content (widgets) to place inside the grid.
	 * @property {Record<string, any>} [restProps] - Allows passing additional HTML attributes.
	 */

	import { cn } from '$lib/utils';

	/** @type {WidgetGridProps} */
	const { class: klass, children, ...restProps } = $props();

</script>

<div class={cn("grid gap-4 md:grid-cols-2 lg:grid-cols-3", klass)} {...restProps}>
  {#if children}
    {@render children()}
  {/if}
</div>

<!-- No <style> block per sk2-scns5-t4-int.mdc rule #12 -->
