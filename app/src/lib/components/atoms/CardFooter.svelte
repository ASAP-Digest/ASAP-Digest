<!-- CardFooter.svelte - Card footer component -->
<script>
  import { cn } from "$lib/utils";

  let {
    className = "",
    children
  } = $props();
</script>

<div class={cn('p-6 pt-0 mt-3', className)}>
  {@render children?.()}
</div> 