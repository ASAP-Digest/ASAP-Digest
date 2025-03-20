<!-- CardFooter.svelte - Card footer component -->
<script>
  import { cn } from "$lib/utils";

  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
</script>

<div class={cn('p-[calc(var(--spacing-unit)*6)] pt-0 mt-[calc(var(--spacing-unit)*3)]', className)} {...$$restProps}>
  <slot />
</div> 