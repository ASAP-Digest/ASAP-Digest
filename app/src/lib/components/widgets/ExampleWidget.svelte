<script>
  import WidgetShell from './WidgetShell.svelte';
  import { createCustomIcon, ExternalLink } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import Button from '$lib/components/ui/button/button.svelte';
  
  // Define custom icons
  const NewspaperIcon = createCustomIcon('newspaper', '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path>');
  const InfoIcon = createCustomIcon('info', '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>');
  
  // Demo props using Svelte 5 runes
  let {
    id = 'example-widget',
    title = 'Example Widget',
    loading = false,
    error = false,
    variant = /** @type {'primary' | 'secondary' | 'accent' | 'muted'} */ ('primary'),
    size = /** @type {'default' | 'compact' | 'expanded' | 'full-width'} */ ('default'),
    summary = 'This is an example widget that demonstrates how to use the WidgetShell component.',
    items = [
      { id: 1, title: 'First item', description: 'Description for first item' },
      { id: 2, title: 'Second item', description: 'Description for second item' },
      { id: 3, title: 'Third item', description: 'Description for third item' }
    ]
  } = $props();
  
  // State with runes
  let expanded = $state(false);
  let refreshing = $state(false);
  
  // Methods
  /**
   * Handle refresh button click
   */
  function handleRefresh() {
    refreshing = true;
    loading = true;
    
    // Simulate API call
    setTimeout(() => {
      loading = false;
      refreshing = false;
    }, 1500);
  }
</script>

<WidgetShell
  {title}
  icon={NewspaperIcon}
  {loading}
  {error}
  {variant}
  {size}
  expandable={true}
  refreshable={true}
  onRefresh={handleRefresh}
>
  <div class="flex flex-col gap-3">
    <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
      {summary}
    </p>
    
    <div class="widget-items flex flex-col divide-y divide-[hsl(var(--border))]">
      {#each items as item (item.id)}
        <div class="widget-item py-2 first:pt-0 last:pb-0">
          <h4 class="font-[var(--font-weight-medium)] text-[var(--font-size-sm)]">{item.title}</h4>
          {#if expanded}
            <p class="text-[var(--font-size-xs)] text-[hsl(var(--text-2))]">
              {item.description}
            </p>
          {/if}
        </div>
      {/each}
    </div>
    
    {#if expanded}
      <div class="flex items-center gap-2 text-[var(--font-size-xs)] text-[hsl(var(--text-2))]">
        <Icon 
          icon={InfoIcon} 
          size={14} 
        />
        <span>Additional information is displayed when expanded.</span>
      </div>
    {/if}
  </div>
  
  <div slot="footer" class="flex justify-between items-center">
    <span class="text-[var(--font-size-xs)] text-[hsl(var(--text-2))]">
      Updated: {new Date().toLocaleDateString()}
    </span>
    
    <Button size="sm" variant="outline" class="gap-1">
      View All
      <Icon 
        icon={ExternalLink} 
        size={14} 
      />
    </Button>
  </div>
</WidgetShell> 