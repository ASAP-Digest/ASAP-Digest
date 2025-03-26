<!-- CardDescription.svelte - Card description component -->
<script>
  import { cn } from "$lib/utils.js";
  import Typography from './Typography.svelte';

  /** @type {string | undefined | null} */
  let className = undefined;
  /** @type {import('svelte/elements').HTMLAttributes<HTMLParagraphElement>} */
  let props = {}; // Use let directly for props in Svelte 5

  // Expose className and other props
  $: ({ class: className, ...props } = $props());
</script>

<Typography 
  variant="p" 
  class={cn(
    'text-[hsl(var(--muted-foreground))]',
    'text-sm',
    className
  )}
  {...props}
>
  {@render children?.()}
</Typography> 