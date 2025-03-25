<!-- dialog.svelte - Root dialog component -->
<script>
  import { Dialog as DialogPrimitive } from "bits-ui";
  import { cn } from "$lib/utils.js";
  
  let {
    ref = $bindable(null),
    open = false,
    onClose = () => {},
    children,
    ...restProps
  } = $props();
</script>

<DialogPrimitive.Root
  bind:ref
  bind:open
  on:close={onClose}
  {...restProps}
>
  {@render children?.()}
</DialogPrimitive.Root> 