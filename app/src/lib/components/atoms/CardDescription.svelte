<!-- CardDescription.svelte - Card description component -->
<script>
  import { cn } from "$lib/utils.js";
  import Typography from './Typography.svelte';

  // Destructure all props, including children and textContent
  const { 
    class: className, 
    children = undefined,
    textContent = "",
    ...restProps 
  } = $props();
</script>

<Typography 
  variant="p" 
  class={cn(
    'text-[hsl(var(--muted-foreground))]',
    'text-sm',
    className
  )}
  {...restProps}
  {textContent}  
>
  {#if children}
    {@render children()} <!-- Pass children snippet down -->
  {/if}
</Typography> 