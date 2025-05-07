<!-- dialog.svelte - Root dialog component -->
<script>
  import { Dialog as DialogPrimitive } from "bits-ui";
  import { cn } from "$lib/utils.js";
  
  /**
   * @typedef {Object} DialogProps
   * @property {any} [ref] - Reference to the dialog element
   * @property {boolean} [open=false] - Whether the dialog is open
   * @property {() => void} [onClose] - Callback when dialog closes
   * @property {import('svelte').Snippet} [children] - Dialog content
   */
  
  /** @type {DialogProps} */
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
  {#if children}
    {@render children()}
  {/if}
</DialogPrimitive.Root> 