<!-- CardContent.svelte - Card content component -->
<script>
  import { cn } from "$lib/utils";

  let {
    className = "",
    children
  } = $props();
</script>

<div class={cn('p-[calc(var(--spacing-unit)*6)]', className)}>
  {@render children?.()}
</div> 