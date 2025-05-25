<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import { browser } from '$app/environment';
  import { createDraftDigest, saveDigestLayout, fetchDigest, addModuleToDigest, updateDigestStatus } from '$lib/api/digest-builder.js';
  import { getUserData } from '$lib/stores/user.js';
  import Button from '$lib/components/ui/button/button.svelte';
  import Card from '$lib/components/ui/card/card.svelte';
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  import { Badge } from '$lib/components/ui/badge';
  // @ts-ignore - Svelte component import
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';

  /** @type {import('./$types').PageData} */
  const { data } = $props();

  // Get user data and layout from URL params
  const userData = $derived(getUserData(data.user));
  const selectedLayoutId = $derived($page.url.searchParams.get('layout'));
  const digestId = $derived($page.url.searchParams.get('digest'));
  const preSelectedModuleParam = $derived($page.url.searchParams.get('preselected'));

  // State management
  let isLoading = $state(true);
  let error = $state(null);
  let currentDigest = $state(null);
  let gridStack = $state(null);
  let isMobile = $state(false);
  let showModuleSelector = $state(false);
  let selectedGridPosition = $state(null);
  let isModuleBrowserCollapsed = $state(false);
  let preSelectedModule = $state(null);

  // Layout configuration based on selected layout
  const layoutConfigs = {
    'solo-focus': {
      name: 'Solo Focus',
      gridOptions: { column: 12, row: 8, maxRow: 8, cellHeight: 60 },
      placeholders: [
        { x: 2, y: 1, w: 8, h: 6, id: 'main-content' }
      ]
    },
    'dynamic-duo': {
      name: 'Dynamic Duo',
      gridOptions: { column: 12, row: 8, maxRow: 8, cellHeight: 60 },
      placeholders: [
        { x: 0, y: 1, w: 6, h: 6, id: 'content-1' },
        { x: 6, y: 1, w: 6, h: 6, id: 'content-2' }
      ]
    },
    'information-hub': {
      name: 'Information Hub',
      gridOptions: { column: 12, row: 12, maxRow: 12, cellHeight: 50 },
      placeholders: [
        { x: 0, y: 0, w: 8, h: 4, id: 'featured' },
        { x: 8, y: 0, w: 4, h: 4, id: 'sidebar-1' },
        { x: 0, y: 4, w: 4, h: 4, id: 'content-1' },
        { x: 4, y: 4, w: 4, h: 4, id: 'content-2' },
        { x: 8, y: 4, w: 4, h: 4, id: 'sidebar-2' },
        { x: 0, y: 8, w: 12, h: 3, id: 'footer-content' }
      ]
    },
    'news-grid': {
      name: 'News Grid',
      gridOptions: { column: 12, row: 16, maxRow: 16, cellHeight: 40 },
      placeholders: [
        { x: 0, y: 0, w: 3, h: 4, id: 'news-1' },
        { x: 3, y: 0, w: 3, h: 4, id: 'news-2' },
        { x: 6, y: 0, w: 3, h: 4, id: 'news-3' },
        { x: 9, y: 0, w: 3, h: 4, id: 'news-4' },
        { x: 0, y: 4, w: 3, h: 4, id: 'news-5' },
        { x: 3, y: 4, w: 3, h: 4, id: 'news-6' },
        { x: 6, y: 4, w: 3, h: 4, id: 'news-7' },
        { x: 9, y: 4, w: 3, h: 4, id: 'news-8' }
      ]
    },
    'feature-spotlight': {
      name: 'Feature Spotlight',
      gridOptions: { column: 12, row: 10, maxRow: 10, cellHeight: 55 },
      placeholders: [
        { x: 1, y: 1, w: 6, h: 6, id: 'featured-story' },
        { x: 7, y: 1, w: 4, h: 3, id: 'supporting-1' },
        { x: 7, y: 4, w: 4, h: 3, id: 'supporting-2' },
        { x: 1, y: 7, w: 10, h: 2, id: 'summary-bar' }
      ]
    }
  };

  const currentLayout = $derived(layoutConfigs[selectedLayoutId] || layoutConfigs['solo-focus']);

  onMount(async () => {
    if (!browser) return;

    // Check if layout is selected
    if (!selectedLayoutId) {
      goto('/digest/create');
      return;
    }

    // Parse pre-selected module from URL parameters
    if (preSelectedModuleParam) {
      try {
        preSelectedModule = JSON.parse(decodeURIComponent(preSelectedModuleParam));
        console.log('[Digest Builder] Pre-selected module from URL:', preSelectedModule);
      } catch (e) {
        console.warn('[Digest Builder] Failed to parse pre-selected module:', e);
      }
    }

    // Check mobile viewport
    isMobile = window.innerWidth < 768;
    
    // Handle window resize
    const handleResize = () => {
      isMobile = window.innerWidth < 768;
    };
    window.addEventListener('resize', handleResize);

    try {
      // If we have a digest ID, fetch existing digest, otherwise create new one
      if (digestId) {
        console.log('[Digest Builder] Loading existing digest:', digestId);
        const digestResponse = await fetchDigest(digestId);
        if (digestResponse.success) {
          currentDigest = digestResponse.data;
        } else {
          throw new Error(digestResponse.error || 'Failed to load digest');
        }
      } else {
        console.log('[Digest Builder] Creating new draft digest');
        const draftResponse = await createDraftDigest(userData.wpUserId, selectedLayoutId);
        if (draftResponse.success) {
          currentDigest = draftResponse.data;
        } else {
          throw new Error(draftResponse.error || 'Failed to create draft digest');
        }
      }

      await initializeGridStack();

      // If we have a pre-selected module, add it to the first available position
      if (preSelectedModule && currentDigest) {
        await addPreSelectedModule();
      }
    } catch (err) {
      console.error('Error initializing digest builder:', err);
      error = err.message;
    } finally {
      isLoading = false;
    }

    return () => {
      window.removeEventListener('resize', handleResize);
      if (gridStack) {
        gridStack.destroy();
      }
    };
  });

  async function initializeGridStack() {
    if (!browser) return;

    // Dynamically import GridStack
    const { GridStack } = await import('gridstack');
    await import('gridstack/dist/gridstack.css');

    const gridElement = document.querySelector('.grid-stack');
    if (!gridElement) return;

    // Initialize GridStack with layout-specific options
    gridStack = GridStack.init({
      ...currentLayout.gridOptions,
      acceptWidgets: true,
      removable: '.trash-zone',
      float: false,
      animate: true
    }, gridElement);

    // Add placeholder items
    currentLayout.placeholders.forEach(placeholder => {
      const widget = gridStack.addWidget({
        x: placeholder.x,
        y: placeholder.y,
        w: placeholder.w,
        h: placeholder.h,
        id: placeholder.id,
        content: createPlaceholderContent(placeholder)
      });
    });

    // Add event listeners
    gridStack.on('added removed change', saveLayoutChanges);
  }

  function createPlaceholderContent(placeholder) {
    return `
      <div class="placeholder-content" data-position-id="${placeholder.id}">
        <div class="placeholder-text">
          <div class="placeholder-icon">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <div class="placeholder-label">Add Module</div>
          <div class="placeholder-size">${placeholder.w}×${placeholder.h}</div>
        </div>
        <div class="placeholder-overlay">
          <button class="add-module-btn" onclick="window.openModuleSelector('${placeholder.id}')">
            Select Content
          </button>
        </div>
      </div>
    `;
  }

  // Global functions for placeholder buttons and module actions
  if (browser) {
    window.openModuleSelector = (positionId) => {
      selectedGridPosition = positionId;
      showModuleSelector = true;
    };

    window.removeModule = async (moduleId) => {
      if (!currentDigest || !gridStack) return;
      
      try {
        // Find the grid item containing this module
        const gridItems = gridStack.getGridItems();
        const moduleItem = gridItems.find(item => {
          const moduleElement = item.querySelector(`[data-module-id="${moduleId}"]`);
          return moduleElement !== null;
        });

        if (moduleItem) {
          // Remove from grid
          gridStack.removeWidget(moduleItem);
          
          // TODO: Call API to remove from digest
          // await removeModuleFromDigest(currentDigest.digest_id, placementId);
          
          console.log('[Digest Builder] Removed module:', moduleId);
        }
      } catch (err) {
        console.error('[Digest Builder] Error removing module:', err);
      }
    };
  }

  async function addPreSelectedModule() {
    if (!preSelectedModule || !gridStack || !currentDigest) return;

    console.log('[Digest Builder] Adding pre-selected module to grid');

    // Find the first available placeholder
    const firstPlaceholder = currentLayout.placeholders[0];
    if (!firstPlaceholder) return;

    try {
      // Add module to the digest via API
      const response = await addModuleToDigest(
        currentDigest.digest_id,
        preSelectedModule,
        {
          x: firstPlaceholder.x,
          y: firstPlaceholder.y,
          w: firstPlaceholder.w,
          h: firstPlaceholder.h
        }
      );

      if (response.success) {
        // Update the grid with the actual module content
        addModuleToGrid(preSelectedModule, firstPlaceholder.id);
        console.log('[Digest Builder] Successfully added pre-selected module');
      } else {
        console.error('[Digest Builder] Failed to add pre-selected module:', response.error);
      }
    } catch (err) {
      console.error('[Digest Builder] Error adding pre-selected module:', err);
    }
  }

  async function saveLayoutChanges() {
    if (!currentDigest || !gridStack) return;

    const layoutData = gridStack.save();
    try {
      await saveDigestLayout(currentDigest.digest_id, layoutData);
    } catch (err) {
      console.error('Error saving layout:', err);
    }
  }

  async function handleModuleSelected(event) {
    const { selectedItems } = event.detail;
    if (selectedItems.length > 0 && selectedGridPosition) {
      const selectedModule = selectedItems[0];
      
      try {
        // Find the grid position for the selected placeholder
        const placeholder = currentLayout.placeholders.find(p => p.id === selectedGridPosition);
        if (!placeholder) {
          console.error('[Digest Builder] Could not find placeholder for position:', selectedGridPosition);
          return;
        }

        // Add module to the digest via API
        const response = await addModuleToDigest(
          currentDigest.digest_id,
          selectedModule,
          {
            x: placeholder.x,
            y: placeholder.y,
            w: placeholder.w,
            h: placeholder.h
          }
        );

        if (response.success) {
          // Update the grid with the actual module content
          addModuleToGrid(selectedModule, selectedGridPosition);
          console.log('[Digest Builder] Successfully added module to grid');
        } else {
          console.error('[Digest Builder] Failed to add module:', response.error);
          error = 'Failed to add module to digest';
        }
      } catch (err) {
        console.error('[Digest Builder] Error adding module:', err);
        error = 'Failed to add module to digest';
      }
    }
    showModuleSelector = false;
    selectedGridPosition = null;
  }

  function addModuleToGrid(module, positionId) {
    if (!gridStack) return;

    const widget = gridStack.getGridItems().find(item => 
      item.querySelector(`[data-position-id="${positionId}"]`)
    );

    if (widget) {
      // Replace placeholder with actual module content
      widget.innerHTML = createModuleContent(module);
      widget.classList.add('module-added-animation');
    }
  }

  function createModuleContent(module) {
    const title = module.title || 'Untitled';
    const type = module.type || 'content';
    const excerpt = module.excerpt || module.description || module.content || '';
    const source = module.source || module.source_name || 'Unknown Source';
    const publishedAt = module.publishedAt || module.published_at || module.date;
    const image = module.image || module.featured_image || module.thumbnail;
    
    // Truncate excerpt for display
    const truncatedExcerpt = excerpt.length > 150 ? excerpt.substring(0, 150) + '...' : excerpt;
    
    // Format date
    let formattedDate = '';
    if (publishedAt) {
      try {
        formattedDate = new Date(publishedAt).toLocaleDateString();
      } catch (e) {
        formattedDate = publishedAt;
      }
    }

    return `
      <div class="module-content enhanced-module" data-module-id="${module.id}">
        <div class="module-header">
          <div class="module-info">
            <h3 class="module-title" title="${title}">${title}</h3>
            <span class="module-type">${type}</span>
          </div>
          <div class="module-actions">
            <button class="module-action-btn" title="Remove Module" onclick="window.removeModule('${module.id}')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="module-body">
          ${image ? `<div class="module-image"><img src="${image}" alt="${title}" loading="lazy" /></div>` : ''}
          ${truncatedExcerpt ? `<p class="module-preview">${truncatedExcerpt}</p>` : ''}
        </div>
        <div class="module-footer">
          <span class="module-source" title="${source}">${source}</span>
          ${formattedDate ? `<span class="module-date">${formattedDate}</span>` : ''}
        </div>
      </div>
    `;
  }

  function toggleModuleBrowser() {
    isModuleBrowserCollapsed = !isModuleBrowserCollapsed;
  }

  function goBack() {
    goto('/digest/layout-selection');
  }

  async function saveAndExit() {
    if (!currentDigest) return;
    
    try {
      isLoading = true;
      
      // Save current layout
      if (gridStack) {
        await saveLayoutChanges();
      }
      
      console.log('[Digest Builder] Digest saved successfully');
      
      // Navigate to digest list
      goto('/digest');
    } catch (err) {
      console.error('[Digest Builder] Error saving digest:', err);
      error = 'Failed to save digest';
    } finally {
      isLoading = false;
    }
  }

  async function publishDigest() {
    if (!currentDigest) return;
    
    try {
      isLoading = true;
      
      // Save current layout first
      if (gridStack) {
        await saveLayoutChanges();
      }
      
      // Update status to published
      const response = await updateDigestStatus(currentDigest.digest_id, 'published');
      
      if (response.success) {
        console.log('[Digest Builder] Digest published successfully');
        goto('/digest');
      } else {
        throw new Error(response.error || 'Failed to publish digest');
      }
    } catch (err) {
      console.error('[Digest Builder] Error publishing digest:', err);
      error = 'Failed to publish digest';
    } finally {
      isLoading = false;
    }
  }
</script>

<svelte:head>
  <title>Digest Builder - {currentLayout.name} - ASAP Digest</title>
</svelte:head>

<div class="digest-builder" class:fullscreen={isMobile}>
  {#if isLoading}
    <div class="loading-state">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
      <p>Setting up your digest builder...</p>
    </div>
      {:else if error}
    <div class="text-center py-12">
      <div class="max-w-md mx-auto">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-destructive/10 flex items-center justify-center">
          <svg class="w-8 h-8 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold mb-2">Something went wrong</h3>
        <p class="text-destructive mb-4">{error}</p>
        <div class="flex gap-2 justify-center">
          <Button variant="outline" onclick={() => error = null}>
            Try Again
          </Button>
          <Button onclick={goBack}>Go Back</Button>
        </div>
      </div>
    </div>
  {:else}
    <!-- Enhanced Toolbar -->
    <div class="builder-toolbar">
      <div class="toolbar-left">
        <Button variant="ghost" onclick={goBack} class="p-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Button>
        {#if !isMobile}
          <Button variant="ghost" onclick={toggleModuleBrowser} class="p-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </Button>
        {/if}
      </div>

      <div class="toolbar-center">
        <div class="layout-info">
          <span>{currentLayout.name}</span>
          <Badge variant="outline" class="layout-modules">
            {currentLayout.placeholders.length} modules
          </Badge>
          {#if currentDigest}
            <Badge variant={currentDigest.status === 'published' ? 'default' : 'secondary'}>
              {currentDigest.status || 'draft'}
            </Badge>
          {/if}
        </div>
      </div>

      <div class="toolbar-right">
        <Button variant="outline" onclick={saveAndExit} disabled={isLoading}>
          {isLoading ? 'Saving...' : 'Save & Exit'}
        </Button>
        <Button onclick={publishDigest} disabled={isLoading}>
          {isLoading ? 'Publishing...' : 'Publish'}
        </Button>
      </div>
    </div>

    {#if isMobile}
      <!-- Mobile Layout -->
      <div class="mobile-layout-container">
        <div class="grid-stack-container mobile">
          <div class="grid-stack"></div>
        </div>
        
        <!-- Mobile Add Module Button -->
        <div class="text-center mt-4">
          <Button onclick={() => showModuleSelector = true} class="w-full">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Module
          </Button>
        </div>
      </div>
    {:else}
      <!-- Desktop Split View -->
      <div class="split-view-container">
        <!-- Module Browser Panel -->
        <div class="module-browser-panel" class:collapsed={isModuleBrowserCollapsed}>
          <div class="browser-header">
            <h3>Content Library</h3>
            <Button variant="ghost" onclick={toggleModuleBrowser} class="p-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </Button>
          </div>
          <div class="browser-content">
            <NewItemsSelector
              mode="digest-builder"
              compactMode={true}
              showFab={false}
              on:moduleSelect={handleModuleSelected}
            />
          </div>
        </div>

        <!-- Grid Canvas Panel -->
        <div class="grid-canvas-panel" class:expanded={isModuleBrowserCollapsed}>
          <div class="canvas-header">
            <h3>Digest Layout</h3>
            <div class="canvas-controls">
              <Button variant="ghost" class="p-2" title="Reset Layout">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </Button>
            </div>
          </div>
          <div class="grid-stack-container">
            <div class="grid-stack"></div>
          </div>
        </div>
      </div>
    {/if}

    <!-- Trash Zone -->
    <div class="trash-zone" class:mobile={isMobile}>
      <div class="trash-content">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Drop to Remove</span>
      </div>
    </div>
  {/if}
</div>

<!-- Mobile Module Selector Modal -->
{#if showModuleSelector && isMobile}
  <div class="fixed inset-0 bg-background z-50 mobile-module-selector fullscreen">
    <div class="flex flex-col h-full">
      <div class="flex items-center justify-between p-4 border-b">
        <h2 class="text-lg font-semibold">Select Content</h2>
        <Button variant="ghost" onclick={() => showModuleSelector = false} class="p-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </Button>
      </div>
      <div class="flex-1 overflow-hidden">
        <NewItemsSelector
          mode="module-selector"
          singleSelect={true}
          compactMode={true}
          showFab={false}
          on:moduleSelect={handleModuleSelected}
        />
      </div>
    </div>
  </div>
{/if}

<style>
  .digest-builder {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
    position: relative;
    transition: all 0.3s ease;
  }

  .digest-builder.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    max-width: none;
    margin: 0;
    z-index: 9999;
    background-color: hsl(var(--background));
    padding: 1rem;
  }

  .loading-state {
    text-align: center;
    padding: 4rem 2rem;
    color: hsl(var(--muted-foreground));
  }

  /* Enhanced Toolbar */
  .builder-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: hsl(var(--card));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    gap: 1rem;
  }

  .toolbar-left,
  .toolbar-center,
  .toolbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .toolbar-center {
    flex: 1;
    justify-content: center;
  }

  .layout-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
  }

  /* Split View Layout */
  .split-view-container {
    display: flex;
    gap: 1rem;
    height: calc(100vh - 200px);
    min-height: 600px;
  }

  .module-browser-panel {
    width: 400px;
    background-color: hsl(var(--card));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    overflow: hidden;
  }

  .module-browser-panel.collapsed {
    width: 0;
    min-width: 0;
    opacity: 0;
    pointer-events: none;
  }

  .browser-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
    background-color: hsl(var(--muted) / 0.3);
  }

  .browser-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
  }

  .browser-content {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .grid-canvas-panel {
    flex: 1;
    background-color: hsl(var(--card));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
  }

  .canvas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
    background-color: hsl(var(--muted) / 0.3);
  }

  .canvas-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
  }

  .canvas-controls {
    display: flex;
    gap: 0.5rem;
  }

  .grid-stack-container {
    flex: 1;
    padding: 1rem;
    overflow: auto;
    position: relative;
  }

  .grid-stack-container.mobile {
    min-height: calc(100vh - 300px);
    background-color: hsl(var(--card));
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
  }

  .mobile-layout-container {
    width: 100%;
  }

  /* GridStack Styles */
  :global(.grid-stack-item-content) {
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 1rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
  }

  :global(.grid-stack-item-content:hover) {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
  }

  /* Placeholder Styles */
  :global(.placeholder-content) {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    border: 2px dashed hsl(var(--border));
    background-color: hsl(var(--muted) / 0.3);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    height: 100%;
  }

  :global(.placeholder-content:hover) {
    border-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.1);
    transform: scale(1.02);
  }

  :global(.placeholder-text) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    z-index: 2;
  }

  :global(.placeholder-icon) {
    color: hsl(var(--primary));
    opacity: 0.7;
  }

  :global(.placeholder-label) {
    font-weight: 500;
  }

  :global(.placeholder-size) {
    font-size: 0.75rem;
    opacity: 0.7;
  }

  :global(.placeholder-overlay) {
    position: absolute;
    inset: 0;
    background-color: hsl(var(--primary) / 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  :global(.placeholder-content:hover .placeholder-overlay) {
    opacity: 1;
  }

  :global(.add-module-btn) {
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  :global(.add-module-btn:hover) {
    background-color: hsl(var(--primary) / 0.9);
    transform: scale(1.05);
  }

  /* Module Content Styles */
  :global(.module-content) {
    background-color: hsl(var(--background));
    position: relative;
    height: 100%;
  }

  :global(.enhanced-module) {
    background: linear-gradient(135deg, hsl(var(--card)), hsl(var(--background)));
    border: 1px solid hsl(var(--border));
  }

  :global(.module-header) {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid hsl(var(--border));
  }

  :global(.module-info) {
    flex: 1;
    min-width: 0;
  }

  :global(.module-title) {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
    color: hsl(var(--foreground));
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  :global(.module-type) {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    background-color: hsl(var(--muted) / 0.3);
    padding: 0.125rem 0.375rem;
    border-radius: var(--radius);
    text-transform: uppercase;
    font-weight: 500;
  }

  :global(.module-actions) {
    display: flex;
    gap: 0.25rem;
    margin-left: 0.5rem;
  }

  :global(.module-action-btn) {
    background: none;
    border: none;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--radius);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  :global(.module-action-btn:hover) {
    background-color: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
  }

  :global(.module-body) {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  :global(.module-preview) {
    font-size: 0.875rem;
    line-height: 1.4;
    color: hsl(var(--muted-foreground));
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }

  :global(.module-image) {
    width: 100%;
    height: 60px;
    overflow: hidden;
    border-radius: var(--radius);
    margin-top: 0.5rem;
  }

  :global(.module-image img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  :global(.module-footer) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 0.5rem;
    border-top: 1px solid hsl(var(--border));
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
  }

  :global(.module-source) {
    font-weight: 500;
  }

  :global(.module-date) {
    opacity: 0.7;
  }

  /* Animation for module addition */
  :global(.module-added-animation) {
    animation: moduleAdded 0.6s ease-out;
  }

  @keyframes moduleAdded {
    0% {
      transform: scale(0.8);
      opacity: 0;
    }
    50% {
      transform: scale(1.05);
    }
    100% {
      transform: scale(1);
      opacity: 1;
    }
  }

  /* Trash Zone */
  .trash-zone {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 140px;
    height: 90px;
    background-color: hsl(var(--destructive) / 0.1);
    border: 2px dashed hsl(var(--destructive));
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: hsl(var(--destructive));
    font-size: 0.875rem;
    opacity: 0.7;
    transition: all 0.3s ease;
    z-index: 1000;
    backdrop-filter: blur(10px);
  }

  .trash-zone:hover {
    opacity: 1;
    transform: scale(1.05);
    background-color: hsl(var(--destructive) / 0.2);
  }

  .trash-zone.mobile {
    position: relative;
    bottom: auto;
    right: auto;
    margin: 1rem auto;
    width: 200px;
    height: 60px;
  }

  .trash-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
  }

  /* Mobile Module Selector */
  .mobile-module-selector {
    width: 100vw;
    height: 100vh;
    max-width: none;
    max-height: none;
    margin: 0;
    border-radius: 0;
  }

  .mobile-module-selector.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 10000;
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
    .digest-builder {
      padding: 0.5rem;
    }

    .builder-toolbar {
      flex-direction: column;
      gap: 0.75rem;
      align-items: stretch;
      padding: 0.75rem;
    }

    .toolbar-left,
    .toolbar-center,
    .toolbar-right {
      justify-content: center;
      flex-wrap: wrap;
    }

    .toolbar-center {
      display: none;
    }

    .layout-info {
      justify-content: center;
      text-align: center;
    }

    .split-view-container {
      display: none;
    }

    .mobile-layout-container {
      display: block;
    }

    .grid-stack-container.mobile {
      min-height: calc(100vh - 250px);
      margin-bottom: 1rem;
    }

    :global(.placeholder-content) {
      min-height: 120px;
      padding: 1rem;
    }

    :global(.placeholder-text) {
      font-size: 1rem;
    }

    :global(.add-module-btn) {
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
    }

    .trash-zone {
      position: relative;
      bottom: auto;
      right: auto;
      margin: 1rem auto;
      width: 200px;
      height: 60px;
    }

    .digest-builder.fullscreen {
      padding: 0.5rem;
    }
  }
</style> 