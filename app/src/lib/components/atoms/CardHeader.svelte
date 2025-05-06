<!-- CardHeader.svelte - Card header component -->
<script>
  import { cn } from "$lib/utils";

  let {
    className = "",
    children
  } = $props();
</script>

<div class={cn('p-6 pb-0 space-y-2', className)}>
  {@render children?.()}
</div> 