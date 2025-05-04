<!-- CardContent.svelte - Card content component -->
<script>
  import { cn } from "$lib/utils";

  /**
   * @typedef {Object} CardContentProps
   * @property {string} [className] - Additional CSS classes
   * @property {import('svelte').Snippet} [children] - Child content
   */

  /** @type {CardContentProps} */
  let {
    className = "",
    children
  } = $props();
</script>

<div class={cn('p-6', className)}>
  {@render children?.()}
</div> 