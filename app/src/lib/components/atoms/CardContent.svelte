<!-- CardContent.svelte - Card content component -->
<script>
  import { cn } from "$lib/utils";

  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
</script>

<div class={cn('p-[calc(var(--spacing-unit)*6)]', className)} {...$$restProps}>
  <slot />
</div> 