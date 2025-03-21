<script>
  import { cn } from '$lib/utils';
  import { Loader2 } from '$lib/utils/lucide-icons.js';
  import { createCustomIcon } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/Icon.svelte';

  /**
   * @typedef {'default' | 'compact' | 'expanded' | 'full-width'} WidgetSize
   * @typedef {'primary' | 'secondary' | 'accent' | 'muted'} WidgetVariant
   */

  // Define icon objects for use in the component
  const chevronUpIcon = createCustomIcon('chevron-up', '<polyline points="18 15 12 9 6 15"></polyline>');
  const chevronDownIcon = createCustomIcon('chevron-down', '<polyline points="6 9 12 15 18 9"></polyline>');
  const refreshIcon = createCustomIcon('refresh-cw', '<polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>');
  const alertCircleIcon = createCustomIcon('alert-circle', '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>');
  const wifiOffIcon = createCustomIcon('wifi-off', '<line x1="1" y1="1" x2="23" y2="23"></line><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path><path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line>');

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
    onExpandedChange
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
        return 'p-[0.75rem] gap-[0.5rem]';
      case 'expanded':
        return 'p-[1.5rem] gap-[1rem]';
      case 'full-width':
        return 'p-[1rem] gap-[0.75rem] col-span-full';
      default:
        return 'p-[1rem] gap-[0.75rem]';
    }
  }

  /**
   * Get border and accent classes based on widget variant
   * @returns {string} CSS classes for variant styling
   */
  function getVariantClasses() {
    switch (variant) {
      case 'primary':
        return 'border-[hsl(var(--primary)/0.2)] hover:border-[hsl(var(--primary)/0.4)]';
      case 'secondary':
        return 'border-[hsl(var(--secondary)/0.2)] hover:border-[hsl(var(--secondary)/0.4)]';
      case 'accent':
        return 'border-[hsl(var(--accent)/0.2)] hover:border-[hsl(var(--accent)/0.4)]';
      case 'muted':
        return 'border-[hsl(var(--border))] hover:border-[hsl(var(--border)/0.8)]';
      default:
        return 'border-[hsl(var(--primary)/0.2)] hover:border-[hsl(var(--primary)/0.4)]';
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
    'widget-shell relative bg-[hsl(var(--card))] rounded-[var(--radius-lg)] border shadow-[0_1px_3px_rgba(0,0,0,0.1)] transition-all duration-[var(--duration-normal)]',
    'flex flex-col',
    sizeClasses,
    variantClasses,
    expandedClass,
    className
  )}
  class:hover:shadow-[0_4px_12px_rgba(0,0,0,0.08)]={!error}
>
  <!-- Header with title and icon -->
  {#if title || icon}
    <div class="widget-header flex items-center justify-between mb-[0.75rem]">
      {#if title}
        <h3 class="font-medium text-[var(--font-size-base)]">
          {title}
          
          {#if loading}
            <span class="inline-flex items-center ml-[0.5rem] text-[hsl(var(--muted-foreground))]">
              <Icon 
                icon={Loader2} 
                size={14} 
                class="animate-spin mr-[0.25rem]" 
                color="currentColor" 
              />
              <span class="text-[var(--font-size-xs)]">Loading...</span>
            </span>
          {/if}
        </h3>
      {/if}
      
      <div class="widget-actions flex gap-[0.5rem] text-[hsl(var(--muted-foreground))]">
        {#if icon}
          <div class="widget-icon">
            <Icon 
              icon={icon} 
              size={20} 
              color="currentColor" 
            />
          </div>
        {/if}
        
        {#if expandable}
          <button
            on:click={toggleExpanded}
            class="p-[0.25rem] rounded-[var(--radius-sm)] hover:bg-[hsl(var(--muted)/0.1)] transition-colors duration-[var(--duration-fast)]"
            aria-label={isExpanded ? "Collapse" : "Expand"}
            aria-expanded={isExpanded}
            type="button"
          >
            <Icon 
              icon={isExpanded ? chevronUpIcon : chevronDownIcon} 
              size={16} 
              color="currentColor" 
            />
          </button>
        {/if}
        
        {#if refreshable}
          <button
            on:click={handleRefresh}
            class="p-[0.25rem] rounded-[var(--radius-sm)] hover:bg-[hsl(var(--muted)/0.1)] transition-colors duration-[var(--duration-fast)]"
            aria-label="Refresh content"
            disabled={loading}
            type="button"
          >
            <Icon 
              icon={refreshIcon} 
              size={16} 
              class={loading ? 'animate-spin' : ''} 
              color="currentColor" 
            />
          </button>
        {/if}
      </div>
    </div>
  {/if}
  
  <!-- Loading state -->
  {#if loading && !error}
    <div class="widget-loading flex items-center justify-center py-[1rem] text-[hsl(var(--muted-foreground))]">
      <Icon 
        icon={Loader2} 
        size={24} 
        class="animate-spin mr-[0.5rem]" 
        color="currentColor" 
      />
      <span>Loading content...</span>
    </div>
  
  <!-- Error state -->
  {:else if error}
    <div class="widget-error flex flex-col items-center justify-center py-[1rem] text-[hsl(var(--destructive))]">
      <Icon 
        icon={alertCircleIcon} 
        size={24} 
        class="mb-[0.5rem]" 
        color="currentColor" 
      />
      <span>{errorMessage}</span>
      
      {#if refreshable}
        <button
          on:click={handleRefresh}
          class="mt-[0.75rem] text-[var(--font-size-sm)] text-[hsl(var(--primary))] hover:underline"
          type="button"
        >
          Try again
        </button>
      {/if}
    </div>
  
  <!-- Offline state -->
  {:else if offline}
    <div class="widget-offline flex flex-col items-center justify-center py-[1rem] text-[hsl(var(--warning))]">
      <Icon 
        icon={wifiOffIcon} 
        size={24} 
        class="mb-[0.5rem]" 
        color="currentColor" 
      />
      <span>Content not available offline</span>
      
      {#if refreshable}
        <button
          on:click={handleRefresh}
          class="mt-[0.75rem] text-[var(--font-size-sm)] text-[hsl(var(--primary))] hover:underline"
          type="button"
        >
          Check connection
        </button>
      {/if}
    </div>
  
  <!-- Content -->
  {:else}
    <div class="widget-content flex-1">
      <slot />
    </div>
  {/if}
  
  <!-- Footer slot -->
  {#if $$slots.footer && !loading && !error && !offline}
    <div class="widget-footer mt-[0.75rem] pt-[0.75rem] border-t border-[hsl(var(--border))]">
      <slot name="footer" />
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
    z-index: 1;
  }
</style> 