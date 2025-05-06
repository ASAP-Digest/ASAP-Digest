<!-- Badge.svelte - Atomic Badge component (wrapper around ui/badge) -->
<script>
  import UIBadge from "$lib/components/ui/badge/badge.svelte";
  import { cn } from "$lib/utils";

  /**
   * @typedef {'default' | 'secondary' | 'destructive' | 'outline'} BadgeVariant
   */

  /**
   * @typedef {Object} BadgeProps
   * @property {BadgeVariant} [variant='default'] - Badge style variant
   * @property {string} [className] - Additional CSS classes
   * @property {import('svelte').Snippet} [children] - Badge content
   */

  /** @type {BadgeProps} */
  const {
    variant = "default",
    className = "",
    children
  } = $props();
</script>

<UIBadge
  {variant}
  class={cn(className)}
>
  {#if children}
    {@render children()}
  {/if}
</UIBadge> 