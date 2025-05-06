<!-- Checkbox.svelte - Atomic Checkbox component (wrapper around ui/checkbox) -->
<script>
    import UICheckbox from "$lib/components/ui/checkbox/checkbox.svelte";
    import { cn } from "$lib/utils";
  
    /**
     * @typedef {Object} CheckboxProps
     * @property {boolean} [checked=false] - Whether the checkbox is checked
     * @property {string} [id] - Input ID (for label association)
     * @property {string} [name] - Input name
     * @property {boolean} [disabled=false] - Whether the input is disabled
     * @property {boolean} [required=false] - Whether the input is required
     * @property {string} [value] - Input value
     * @property {() => void} [onCheckedChange] - Change handler
     * @property {string} [className] - Additional CSS classes
     * @property {import('svelte').Snippet} [children] - Optional label content
     */
  
    /** @type {CheckboxProps} */
    const {
      checked = false,
      id,
      name,
      disabled = false,
      required = false,
      value,
      onCheckedChange,
      className = "",
      children
    } = $props();
  </script>
  
  <div class="flex items-center space-x-2">
    <UICheckbox
      {checked}
      {id}
      {name}
      {disabled}
      {required}
      {value}
      {onCheckedChange}
      class={cn(className)}
    />
    
    {#if children}
      <label 
        for={id} 
        class="text-[var(--font-size-sm)] font-[var(--font-weight-regular)] leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {@render children()}
      </label>
    {/if}
  </div>