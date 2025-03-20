<!-- CardHeader.svelte - Card header component -->
<script>
  import { cn } from "$lib/utils";

  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
</script>

<div class={cn('p-[calc(var(--spacing-unit)*6)] pb-0 space-y-[calc(var(--spacing-unit)*1.5)]', className)} {...$$restProps}>
  <slot />
</div> 