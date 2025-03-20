<!-- CardTitle.svelte - Card title component -->
<script>
  import { cn } from "$lib/utils";
  import Typography from './Typography.svelte';

  let {
    className = "",
    children
  } = $props();
</script>

<Typography 
  variant="h3" 
  class={cn('text-[var(--font-size-xl)] font-[var(--font-weight-semibold)] leading-[var(--line-height-snug)] mb-0', className)}
>
  {@render children?.()}
</Typography> 