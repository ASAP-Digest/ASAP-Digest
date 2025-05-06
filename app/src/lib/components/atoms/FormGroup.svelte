<!-- FormGroup.svelte - Container for form fields with label and error handling -->
<script>
  import { cn } from "$lib/utils";

  /**
   * @typedef {Object} FormGroupProps
   * @property {string} [id] - ID attribute for the form group (used for label association)
   * @property {string} [label] - Label text for the form group
   * @property {string} [helperText] - Optional helper text displayed below the field
   * @property {string} [errorMessage] - Error message to display (when validation fails)
   * @property {boolean} [required=false] - Whether the field is required
   * @property {string} [className] - Additional CSS classes
   * @property {import('svelte').Snippet} [children] - Form control(s) to render in the group
   */

  /** @type {FormGroupProps} */
  const {
    id = crypto.randomUUID(),
    label,
    helperText,
    errorMessage,
    required = false,
    className = "",
    children
  } = $props();

  const hasError = $derived(!!errorMessage);
</script>

<div class={cn(
  "flex flex-col gap-1.5 mb-4", 
  className
)}>
  {#if label}
    <label 
      for={id} 
      class={cn(
        "text-[var(--font-size-base)] font-[var(--font-weight-regular)]",
        hasError ? "text-[hsl(var(--functional-error))]" : "text-[hsl(var(--text-1))]"
      )}
    >
      {label} {#if required}<span class="text-[hsl(var(--functional-error))]">*</span>{/if}
    </label>
  {/if}

  <!-- Form control -->
  {#if children}
    <div class="w-full">
      {@render children()}
    </div>
  {/if}

  <!-- Helper text or error message -->
  {#if hasError}
    <p class="text-[var(--font-size-sm)] text-[hsl(var(--functional-error))]">
      {errorMessage}
    </p>
  {:else if helperText}
    <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
      {helperText}
    </p>
  {/if}
</div> 