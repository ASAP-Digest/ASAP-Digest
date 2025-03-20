<!-- CardDescription.svelte - Card description component -->
<script>
  import { cn } from "$lib/utils";
  import Typography from './Typography.svelte';

  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
</script>

<Typography 
  variant="p" 
  class={cn('text-[hsl(var(--muted-foreground))] text-[var(--font-size-sm)] mb-0', className)} 
  {...$$restProps}
>
  <slot />
</Typography> 