<!-- CardDescription.svelte - Card description component -->
<script>
  import { cn } from "$lib/utils.js";
  import Typography from './Typography.svelte';

  // Access props using the $props rune
  const { class: className, ...restProps } = $props();
</script>

<Typography 
  variant="p" 
  class={cn(
    'text-[hsl(var(--muted-foreground))]',
    'text-sm',
    className
  )}
  {...restProps}
>
  {@render children?.()}
</Typography> 