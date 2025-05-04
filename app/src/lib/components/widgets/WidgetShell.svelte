<script>
  import { cn } from '$lib/utils';
  import { Loader2, ChevronUp, ChevronDown, RefreshCw, AlertCircle, WifiOff } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';

  /**
   * @typedef {'default' | 'compact' | 'expanded' | 'full-width'} WidgetSize
   * @typedef {'primary' | 'secondary' | 'accent' | 'muted'} WidgetVariant
   */

  /**
   * Declare component props using Svelte 5 runes syntax
   * @typedef {Object} WidgetProps
   * @property {string} [title] - Widget title
   * @property {any} [icon] - Icon to display in the header
   * @property {boolean} [loading] - Whether the widget is loading
   * @property {boolean} [error] - Whether the widget has an error
   * @property {string} [errorMessage] - Error message to display
   * @property {WidgetSize} [size] - Widget size variant
   * @property {WidgetVariant} [variant] - Widget style variant
   * @property {string} [className] - Additional class names
   * @property {boolean} [expandable] - Whether the widget can be expanded
   * @property {boolean} [expanded] - Whether the widget is expanded
   * @property {boolean} [refreshable] - Whether the widget can be refreshed
   * @property {() => void} [onRefresh] - Callback when refresh button is clicked
   * @property {boolean} [offline] - Whether the widget is offline
   * @property {(expanded: boolean) => void} [onExpandedChange] - Callback when expanded state changes
   * @property {import('svelte').Snippet} [children] - Widget content
   * @property {import('svelte').Snippet} [footer] - Widget footer content
   */
  let { 
    title = '', 
    icon = null, 
    loading = false, 
    error = false, 
    errorMessage = 'Failed to load content',
    size = /** @type {WidgetSize} */ ('default'),
    variant = /** @type {WidgetVariant} */ ('primary'),
    className = '',
    expandable = false,
    expanded = false,
    refreshable = false,
    onRefresh = () => {},
    offline = false,
    onExpandedChange,
    children,
    footer
  } = $props();

  // Use $state for reactive variables that can change
  let isExpanded = $state(expanded);
  
  // Update the expanded state when the prop changes
  $effect(() => {
    isExpanded = expanded;
  });

  /**
   * Toggle widget expanded state
   * @param {Event} e - The click event
   */
  function toggleExpanded(e) {
    isExpanded = !isExpanded;
    // If the component has a bind:expanded prop, we need to notify the parent
    if (onExpandedChange) {
      onExpandedChange(isExpanded);
    }
  }

  /**
   * Get spacing classes based on widget size
   * @returns {string} CSS classes for spacing
   */
  function getSizeClasses() {
    switch (size) {
      case 'compact':
        return 'p-3 gap-2'; // 12px padding, 8px gap (multiples of 4px)
      case 'expanded':
        return 'p-6 gap-4'; // 24px padding, 16px gap (multiples of 8px)
      case 'full-width':
        return 'p-4 gap-3 col-span-full'; // 16px padding, 12px gap (multiples of 4px)
      default:
        return 'p-4 gap-3'; // 16px padding, 12px gap (multiples of 4px)
    }
  }

  /**
   * Get border and accent classes based on widget variant
   * @returns {string} CSS classes for variant styling
   */
  function getVariantClasses() {
    switch (variant) {
      case 'primary':
        return 'border-[hsl(var(--brand)/0.2)] hover:border-[hsl(var(--brand)/0.4)]';
      case 'secondary':
        return 'border-[hsl(var(--accent)/0.2)] hover:border-[hsl(var(--accent)/0.4)]';
      case 'accent':
        return 'border-[hsl(var(--link)/0.2)] hover:border-[hsl(var(--link)/0.4)]';
      case 'muted':
        return 'border-[hsl(var(--border))] hover:border-[hsl(var(--border)/0.8)]';
      default:
        return 'border-[hsl(var(--brand)/0.2)] hover:border-[hsl(var(--brand)/0.4)]';
    }
  }

  /**
   * Handle refresh button click
   * @param {Event} e - The click event
   */
  function handleRefresh(e) {
    if (onRefresh) {
      onRefresh();
    }
  }

  // Use $derived for computed properties that depend on reactive state
  const sizeClasses = $derived(getSizeClasses());
  const variantClasses = $derived(getVariantClasses());
  const expandedClass = $derived(isExpanded ? 'widget-expanded' : '');
</script>

<div 
  class={cn(
    'widget-shell relative bg-[hsl(var(--surface-2))] rounded-[var(--radius-lg)] border shadow-[var(--shadow-sm)] transition-all duration-[var(--duration-normal)]',
    'flex flex-col',
    sizeClasses,
    variantClasses,
    expandedClass,
    className
  )}
  class:hover:shadow-[var(--shadow-md)]={!error}
>
  <!-- Header with title and icon -->
  {#if title || icon}
    <div class="widget-header flex items-center justify-between mb-3">
      {#if title}
        <h3 class="font-[var(--font-weight-semibold)] text-[var(--font-size-base)] text-[hsl(var(--text-1))]">
          {title}
          
          {#if loading}
            <span class="inline-flex items-center ml-2 text-[hsl(var(--text-2))]">
              <Icon 
                icon={Loader2} 
                size={14} 
                class="animate-spin mr-1" 
              />
              <span class="text-[var(--font-size-xs)]">Loading...</span>
            </span>
          {/if}
        </h3>
      {/if}
      
      <div class="widget-actions flex gap-2 text-[hsl(var(--text-2))]">
        {#if icon}
          <div class="widget-icon">
            <Icon 
              icon={icon} 
              size={20} 
            />
          </div>
        {/if}
        
        {#if expandable}
          <button
            on:click={toggleExpanded}
            class="p-1 rounded-[var(--radius-sm)] hover:bg-[hsl(var(--surface-3))] transition-colors duration-[var(--duration-fast)]"
            aria-label={isExpanded ? "Collapse" : "Expand"}
            aria-expanded={isExpanded}
            type="button"
          >
            <Icon 
              icon={isExpanded ? ChevronUp : ChevronDown} 
              size={16} 
            />
          </button>
        {/if}
        
        {#if refreshable}
          <button
            on:click={handleRefresh}
            class="p-1 rounded-[var(--radius-sm)] hover:bg-[hsl(var(--surface-3))] transition-colors duration-[var(--duration-fast)]"
            aria-label="Refresh content"
            disabled={loading}
            type="button"
          >
            <Icon 
              icon={RefreshCw} 
              size={16} 
              class={loading ? 'animate-spin' : ''} 
            />
          </button>
        {/if}
      </div>
    </div>
  {/if}
  
  <!-- Loading state -->
  {#if loading && !error}
    <div class="widget-loading flex items-center justify-center py-4 text-[hsl(var(--text-2))]">
      <Icon 
        icon={Loader2} 
        size={24} 
        class="animate-spin mr-2" 
      />
      <span>Loading content...</span>
    </div>
  
  <!-- Error state -->
  {:else if error}
    <div class="widget-error flex flex-col items-center justify-center py-4 text-[hsl(var(--functional-error))]">
      <Icon 
        icon={AlertCircle} 
        size={24} 
        class="mb-2" 
      />
      <span>{errorMessage}</span>
      
      {#if refreshable}
        <button
          on:click={handleRefresh}
          class="mt-3 text-[var(--font-size-sm)] text-[hsl(var(--brand))] hover:underline"
          type="button"
        >
          Try again
        </button>
      {/if}
    </div>
  
  <!-- Offline state -->
  {:else if offline}
    <div class="widget-offline flex flex-col items-center justify-center py-4 text-[hsl(var(--text-1))]">
      <Icon 
        icon={WifiOff} 
        size={24} 
        class="mb-2" 
      />
      <span>Content not available offline</span>
      
      {#if refreshable}
        <button
          on:click={handleRefresh}
          class="mt-3 text-[var(--font-size-sm)] text-[hsl(var(--brand))] hover:underline"
          type="button"
        >
          Check connection
        </button>
      {/if}
    </div>
  {:else}
    <!-- Content slot -->
    <div class="widget-content flex-1 min-h-0">
      {#if children}
        {@render children()}
      {/if}
    </div>
  {/if}
  
  <!-- Footer slot -->
  {#if footer}
    <div class="widget-footer mt-3 pt-3 border-t border-[hsl(var(--border)/0.5)]">
      {@render footer()}
    </div>
  {/if}
</div>

<style>
  /* Use scoped styles for local widget enhancements */
  .widget-shell {
    box-sizing: border-box;
    max-width: 100%;
    overflow: hidden;
  }
  
  .widget-expanded {
    height: auto !important;
    max-height: none !important;
  }
  
  /* Optional animation for expanding/collapsing */
  .widget-content {
    transition: height var(--duration-normal) var(--ease-out);
  }
  
  /* Ensure proper stacking of elements */
  .widget-loading, .widget-error, .widget-offline {
    position: relative;
  }
</style> 