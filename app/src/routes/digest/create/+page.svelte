<!--
  Digest Creation Flow - Apple-like Experience (Phase 2 Enhanced)
  -------------------------------------------------------------
  
  Phase 2 Features:
  - Split Modal for Desktop: Module browser left | Layout canvas right
  - Mobile Optimization: Full-screen layout with contextual module selector popups
  - Real-time Preview: Live representation of digest as modules are added
  - Enhanced drag & drop with Gridstack.js integration
  - Responsive design with mobile-first approach
-->
<script>
  import { onMount, onDestroy } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { useSession } from '$lib/auth-client.js';
  import { 
    fetchLayoutTemplates, 
    createDraftDigest, 
    addModuleToDigest,
    fetchDigest,
    fetchUserDigests
  } from '$lib/api/digest-builder.js';
  import { getContent } from '$lib/api/crawler-api.js';
  import Button from '$lib/components/ui/button/button.svelte';
  import Card from '$lib/components/ui/card/card.svelte';
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  import Dialog from '$lib/components/ui/dialog/dialog.svelte';
  import DialogContent from '$lib/components/ui/dialog/dialog-content.svelte';
  import DialogHeader from '$lib/components/ui/dialog/dialog-header.svelte';
  import DialogTitle from '$lib/components/ui/dialog/dialog-title.svelte';
  import { ArrowLeft, Plus, Grid, Layout, Smartphone, Trash2, FolderPlus, FileEdit, PanelLeft, X, Maximize, RefreshCw } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import NewItemsSelector from '$lib/components/selectors/NewItemsSelector.svelte';

  // State management for the refined flow
  let currentStep = $state('destination-choice'); // 'destination-choice', 'layout-selection', 'digest-building'
  let selectedLayout = $state(null);
  let layoutTemplates = $state([]);
  let userDigests = $state([]);
  let currentDigest = $state(null);
  let gridStackInstance = $state(null);
  let isLoading = $state(false);
  let error = $state('');
  let showModuleSelector = $state(false);
  let selectedModuleForPlacement = $state(null);
  let targetGridPosition = $state(null);
  let preSelectedModule = $state(null);

  // Phase 2: Enhanced UI state
  let isMobile = $state(false);
  let showSplitView = $state(false);
  let splitViewMode = $state('desktop'); // 'desktop' | 'mobile'
  let moduleBrowserCollapsed = $state(false);
  let selectedModulePreview = $state(null);
  let draggedModule = $state(null);
  let isFullscreen = $state(false);

  // Get user session
  const { data: session } = useSession();

  // Initialize on mount
  onMount(async () => {
    // Detect mobile device
    checkMobileDevice();
    window.addEventListener('resize', checkMobileDevice);
    
    await loadLayoutTemplates();
    await loadUserDigests();
    
    // Check if we're coming from NIS with a pre-selected module
    // First check sessionStorage (Phase 2 enhanced flow)
    const storedModule = sessionStorage.getItem('digest-preselected-module');
    if (storedModule) {
      try {
        preSelectedModule = JSON.parse(storedModule);
        // Clear the stored data
        sessionStorage.removeItem('digest-preselected-module');
        // Start with destination choice
        currentStep = 'destination-choice';
      } catch (e) {
        console.warn('Failed to parse stored module data:', e);
      }
    } else {
      // Fallback to URL parameters (legacy support)
      const urlParams = new URLSearchParams(window.location.search);
      const moduleId = urlParams.get('module_id');
      const moduleType = urlParams.get('module_type');
      
      if (moduleId && moduleType) {
        // Store pre-selected module for later use
        preSelectedModule = { id: moduleId, type: moduleType };
        // Start with destination choice
        currentStep = 'destination-choice';
      }
    }
  });

  // Cleanup on destroy
  onDestroy(() => {
    if (gridStackInstance) {
      gridStackInstance.destroy();
    }
    window.removeEventListener('resize', checkMobileDevice);
  });

  // Phase 2: Mobile detection and responsive logic
  function checkMobileDevice() {
    isMobile = window.innerWidth < 768;
    if (currentStep === 'digest-building') {
      splitViewMode = isMobile ? 'mobile' : 'desktop';
    }
  }

  // Load available layout templates
  async function loadLayoutTemplates() {
    try {
      isLoading = true;
      const response = await fetchLayoutTemplates();
      if (response.success) {
        layoutTemplates = response.data;
      } else {
        error = 'Failed to load layout templates';
      }
    } catch (err) {
      error = err.message || 'Failed to load layout templates';
    } finally {
      isLoading = false;
    }
  }

  // Load user's existing digests
  async function loadUserDigests() {
    if (!session?.user?.id) return;
    
    try {
      const response = await fetchUserDigests(session.user.id, 'draft');
      if (response.success) {
        userDigests = response.data || [];
      }
    } catch (err) {
      console.warn('Failed to load user digests:', err);
    }
  }

  // Handle destination choice
  function chooseNewDigest() {
    currentStep = 'layout-selection';
  }

  async function chooseExistingDigest(digest) {
    try {
      isLoading = true;
      // Load the existing digest
      const response = await fetchDigest(digest.id);
      if (response.success) {
        currentDigest = response.data;
        // Find the layout template for this digest
        selectedLayout = layoutTemplates.find(l => l.id === currentDigest.layout_template_id);
        currentStep = 'digest-building';
        showSplitView = true;
        splitViewMode = isMobile ? 'mobile' : 'desktop';
        await initializeGridStack();
      } else {
        error = 'Failed to load digest';
      }
    } catch (err) {
      error = err.message || 'Failed to load digest';
    } finally {
      isLoading = false;
    }
  }

  // Handle layout selection
  async function selectLayout(layout) {
    try {
      isLoading = true;
      selectedLayout = layout;
      
      // Create draft digest with selected layout
      const response = await createDraftDigest(session?.user?.id, layout.id);
      if (response.success) {
        currentDigest = { 
          id: response.data.digest_id, 
          layout_template_id: layout.id,
          module_placements: []
        };
        currentStep = 'digest-building';
        showSplitView = true;
        splitViewMode = isMobile ? 'mobile' : 'desktop';
        await initializeGridStack();
        
        // If we have a pre-selected module, add it automatically
        if (preSelectedModule) {
          // Auto-place in first available position
          const firstPosition = selectedLayout.default_placements[0];
          if (firstPosition) {
            targetGridPosition = { ...firstPosition, index: 0 };
            await handleModuleSelected(preSelectedModule);
          }
        }
      } else {
        error = 'Failed to create digest';
      }
    } catch (err) {
      error = err.message || 'Failed to create digest';
    } finally {
      isLoading = false;
    }
  }

  // Phase 2: Enhanced GridStack initialization with real-time preview
  async function initializeGridStack() {
    if (!selectedLayout || !currentDigest) return;

    // Wait for DOM to be ready
    await new Promise(resolve => setTimeout(resolve, 100));
    
    const gridContainer = document.querySelector('.grid-stack');
    if (!gridContainer) {
      console.warn('Grid container not found');
      return;
    }

    // Dynamically import GridStack
    const { GridStack } = await import('gridstack');
    await import('gridstack/dist/gridstack.min.css');

    // Initialize with layout-specific configuration and enhanced options
    gridStackInstance = GridStack.init({
      ...selectedLayout.gridstack_config,
      // Enhanced settings for Phase 2
      acceptWidgets: '.module-widget',
      removable: '.trash-zone',
      animate: true,
      float: false,
      resizable: {
        handles: 'e, se, s, sw, w'
      },
      draggable: {
        handle: '.module-header',
        scroll: true,
        appendTo: 'body'
      }
    }, gridContainer);

    // Enhanced event listeners for real-time preview
    gridStackInstance.on('change', handleGridChange);
    gridStackInstance.on('added', handleModuleAdded);
    gridStackInstance.on('removed', handleModuleRemoved);
    gridStackInstance.on('dragstart', handleDragStart);
    gridStackInstance.on('dragstop', handleDragStop);

    // Create enhanced placeholder grid items or load existing placements
    if (currentDigest.module_placements && currentDigest.module_placements.length > 0) {
      // Load existing placements with enhanced preview
      currentDigest.module_placements.forEach((placement, index) => {
        const widget = {
          x: placement.grid_x,
          y: placement.grid_y,
          w: placement.grid_width,
          h: placement.grid_height,
          id: `module-${placement.placement_id}`,
          content: createModuleContent(placement, true)
        };
        gridStackInstance.addWidget(widget);
      });
    } else {
      // Create enhanced placeholders for new digest
      selectedLayout.default_placements.forEach((placement, index) => {
        const widget = {
          ...placement,
          id: `placeholder-${index}`,
          content: createPlaceholderContent(index, placement)
        };
        gridStackInstance.addWidget(widget);
      });
    }

    // Add enhanced click handlers
    setTimeout(() => {
      addGridEventHandlers();
    }, 100);
  }

  // Phase 2: Enhanced content creation functions
  function createPlaceholderContent(index, placement) {
    return `
      <div class="grid-stack-item-content placeholder-content enhanced-placeholder" data-placement-index="${index}">
        <div class="placeholder-text">
          <div class="placeholder-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
          </div>
          <span class="placeholder-label">Add Module</span>
          <span class="placeholder-size">${placement.w}×${placement.h}</span>
        </div>
        <div class="placeholder-overlay">
          <button class="add-module-btn" data-placement-index="${index}">
            <span>Choose Content</span>
          </button>
        </div>
      </div>
    `;
  }

  function createModuleContent(placement, isExisting = false) {
    return `
      <div class="grid-stack-item-content module-content enhanced-module">
        <div class="module-header">
          <div class="module-info">
            <h3 class="module-title">${isExisting ? `Module ${placement.module_cpt_id}` : placement.title}</h3>
            <span class="module-type">${placement.type || 'Content'}</span>
          </div>
          <div class="module-actions">
            <button class="module-action-btn preview-btn" title="Preview">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
            <button class="module-action-btn remove-module" onclick="removeModule(${placement.placement_id || placement.id})" title="Remove">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
        <div class="module-body">
          <div class="module-preview">
            ${placement.excerpt || placement.summary || 'Module content preview will appear here'}
          </div>
          ${placement.image ? `<div class="module-image"><img src="${placement.image}" alt="${placement.title}" /></div>` : ''}
        </div>
        <div class="module-footer">
          <span class="module-source">${placement.source || 'Unknown Source'}</span>
          <span class="module-date">${placement.publishedAt ? new Date(placement.publishedAt).toLocaleDateString() : ''}</span>
        </div>
      </div>
    `;
  }

  // Phase 2: Enhanced event handlers
  function addGridEventHandlers() {
    // Enhanced placeholder click handlers
    document.querySelectorAll('.placeholder-content, .add-module-btn').forEach(element => {
      element.addEventListener('click', handlePlaceholderClick);
    });

    // Module preview handlers
    document.querySelectorAll('.preview-btn').forEach(btn => {
      btn.addEventListener('click', handleModulePreview);
    });

    // Touch handlers for mobile
    if (isMobile) {
      document.querySelectorAll('.placeholder-content').forEach(placeholder => {
        placeholder.addEventListener('touchstart', handleMobilePlaceholderTouch);
      });
    }
  }

  // Phase 2: Enhanced placeholder click with mobile optimization
  function handlePlaceholderClick(event) {
    event.stopPropagation();
    const placementIndex = event.currentTarget.dataset.placementIndex || 
                          event.currentTarget.closest('[data-placement-index]')?.dataset.placementIndex;
    
    if (placementIndex !== undefined) {
      targetGridPosition = {
        ...selectedLayout.default_placements[parseInt(placementIndex)],
        index: parseInt(placementIndex)
      };

      if (isMobile) {
        // Mobile: Show full-screen module selector
        showModuleSelector = true;
        isFullscreen = true;
      } else {
        // Desktop: Show in split view
        showModuleSelector = true;
        moduleBrowserCollapsed = false;
      }
    }
  }

  // Phase 2: Mobile touch handler
  function handleMobilePlaceholderTouch(event) {
    // Add haptic feedback if available
    if (navigator.vibrate) {
      navigator.vibrate(50);
    }
    handlePlaceholderClick(event);
  }

  // Phase 2: Module preview handler
  function handleModulePreview(event) {
    event.stopPropagation();
    const moduleElement = event.currentTarget.closest('.module-content');
    // Extract module data and show preview
    selectedModulePreview = {
      title: moduleElement.querySelector('.module-title')?.textContent,
      type: moduleElement.querySelector('.module-type')?.textContent,
      content: moduleElement.querySelector('.module-preview')?.textContent
    };
  }

  // Enhanced drag handlers
  function handleDragStart(event, ui) {
    draggedModule = ui.helper;
    document.body.classList.add('dragging-module');
  }

  function handleDragStop(event, ui) {
    draggedModule = null;
    document.body.classList.remove('dragging-module');
  }

  // Handle module selection from selector with enhanced preview
  async function handleModuleSelected(module) {
    // Handle both event object and direct module data
    const moduleData = module.detail ? module.detail : module;
    if (!currentDigest || !targetGridPosition) return;

    try {
      isLoading = true;
      
      // Add module to digest via API
      const response = await addModuleToDigest(
        currentDigest.id, 
        moduleData.id, 
        {
          grid_x: targetGridPosition.x,
          grid_y: targetGridPosition.y,
          grid_width: targetGridPosition.w,
          grid_height: targetGridPosition.h
        }
      );

      if (response.success) {
        // Update the grid item with enhanced module content
        const placeholderElement = document.querySelector(`[data-placement-index="${targetGridPosition.index}"]`);
        if (placeholderElement) {
          const gridItem = placeholderElement.closest('.grid-stack-item');
          gridItem.innerHTML = createModuleContent({
            placement_id: response.data.placement_id,
            module_cpt_id: moduleData.id,
            title: moduleData.title,
            type: moduleData.type,
            excerpt: moduleData.excerpt,
            image: moduleData.image,
            source: moduleData.source,
            publishedAt: moduleData.publishedAt,
            ...targetGridPosition
          });
          
          // Re-add event handlers to the new content
          setTimeout(() => {
            addGridEventHandlers();
          }, 100);
        }

        // Update digest state
        currentDigest.module_placements.push({
          placement_id: response.data.placement_id,
          module_cpt_id: moduleData.id,
          ...targetGridPosition,
          ...moduleData
        });

        // Show success animation
        showSuccessAnimation(gridItem);
      }
    } catch (err) {
      error = err.message || 'Failed to add module';
    } finally {
      isLoading = false;
      showModuleSelector = false;
      targetGridPosition = null;
      isFullscreen = false;
    }
  }

  // Phase 2: Success animation
  function showSuccessAnimation(element) {
    if (element) {
      element.classList.add('module-added-animation');
      setTimeout(() => {
        element.classList.remove('module-added-animation');
      }, 600);
    }
  }

  // Phase 2: Toggle split view components
  function toggleModuleBrowser() {
    moduleBrowserCollapsed = !moduleBrowserCollapsed;
  }

  function toggleFullscreen() {
    isFullscreen = !isFullscreen;
    if (isFullscreen) {
      document.body.classList.add('digest-fullscreen');
    } else {
      document.body.classList.remove('digest-fullscreen');
    }
  }

  // Handle grid changes (drag/resize) with real-time updates
  function handleGridChange(event, items) {
    // Update module placements when grid changes
    console.log('Grid changed:', items);
    // TODO: Debounced API update for real-time sync
  }

  // Handle module added to grid
  function handleModuleAdded(event, items) {
    console.log('Module added:', items);
  }

  // Handle module removed from grid
  function handleModuleRemoved(event, items) {
    console.log('Module removed:', items);
  }

  // Navigate back to previous step
  function goBack() {
    if (currentStep === 'digest-building') {
      if (currentDigest?.module_placements?.length > 0) {
        // Ask for confirmation if there are unsaved changes
        if (confirm('You have unsaved changes. Are you sure you want to go back?')) {
          goToPreviousStep();
        }
      } else {
        goToPreviousStep();
      }
    } else if (currentStep === 'layout-selection') {
      currentStep = 'destination-choice';
    } else {
      goto('/digest');
    }
  }

  function goToPreviousStep() {
    if (currentStep === 'digest-building') {
      currentStep = 'layout-selection';
      showSplitView = false;
      if (gridStackInstance) {
        gridStackInstance.destroy();
        gridStackInstance = null;
      }
    }
  }

  // Save and publish digest
  async function saveDigest() {
    if (!currentDigest) return;
    
    try {
      isLoading = true;
      // TODO: Implement save/publish logic
      goto(`/digest/${currentDigest.id}`);
    } catch (err) {
      error = err.message || 'Failed to save digest';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="digest-creator">
  <!-- Header -->
  <div class="creator-header">
    <Button variant="ghost" onclick={goBack} class="back-button">
      <Icon icon={ArrowLeft} size={20} />
      Back
    </Button>
    <h1 class="creator-title">
      {#if currentStep === 'destination-choice'}
        Choose Destination
      {:else if currentStep === 'layout-selection'}
        Choose Your Digest Layout
      {:else if currentStep === 'digest-building'}
        Build Your Digest
      {/if}
    </h1>
  </div>

  {#if error}
    <div class="error-message">
      {error}
    </div>
  {/if}

  <!-- Destination Choice Step -->
  {#if currentStep === 'destination-choice'}
    <div class="destination-choice">
      <div class="choice-description">
        <p>Where would you like to add your content?</p>
        {#if preSelectedModule}
          <div class="pre-selected-info">
            <div class="pre-selected-module">
              <div class="module-preview">
                {#if preSelectedModule.image}
                  <img src={preSelectedModule.image} alt={preSelectedModule.title} class="module-thumbnail" />
                {/if}
                <div class="module-details">
                  <h4 class="module-title">{preSelectedModule.title}</h4>
                  <p class="module-type">{preSelectedModule.type}</p>
                  {#if preSelectedModule.excerpt}
                    <p class="module-excerpt">{preSelectedModule.excerpt}</p>
                  {/if}
                  <p class="module-source">{preSelectedModule.source || 'Unknown Source'}</p>
                </div>
              </div>
            </div>
          </div>
        {/if}
      </div>

      <div class="choice-grid">
        <!-- Create New Digest Option -->
        <Card class="choice-card" onclick={chooseNewDigest}>
          <CardHeader>
            <div class="choice-icon">
              <Icon icon={FolderPlus} size={48} />
            </div>
            <CardTitle>Create New Digest</CardTitle>
          </CardHeader>
          <CardContent>
            <p>Start fresh with a new digest layout and build from scratch.</p>
          </CardContent>
        </Card>

        <!-- Add to Existing Digest Option -->
        <Card class="choice-card">
          <CardHeader>
            <div class="choice-icon">
              <Icon icon={FileEdit} size={48} />
            </div>
            <CardTitle>Add to Existing Digest</CardTitle>
          </CardHeader>
          <CardContent>
            <p>Add to one of your existing draft digests.</p>
            {#if userDigests.length > 0}
              <div class="existing-digests">
                {#each userDigests.slice(0, 3) as digest}
                  <button 
                    class="digest-option"
                    onclick={() => chooseExistingDigest(digest)}
                  >
                    Digest #{digest.id} ({digest.layout_template_id})
                  </button>
                {/each}
                {#if userDigests.length > 3}
                  <p class="text-sm text-muted-foreground">
                    +{userDigests.length - 3} more digests
                  </p>
                {/if}
              </div>
            {:else}
              <p class="text-sm text-muted-foreground">No draft digests found</p>
            {/if}
          </CardContent>
        </Card>
      </div>
    </div>
  {/if}

  <!-- Layout Selection Step -->
  {#if currentStep === 'layout-selection'}
    <div class="layout-selection">
      <div class="layout-description">
        <p>Select a layout template that best fits your content needs. You can customize the arrangement later.</p>
      </div>

      {#if isLoading}
        <div class="loading-state">
          <p>Loading layout templates...</p>
        </div>
      {:else}
        <div class="layout-grid">
          {#each layoutTemplates as layout (layout.id)}
            <Card class="layout-card" onclick={() => selectLayout(layout)}>
              <CardHeader>
                <CardTitle class="layout-name">{layout.name}</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="layout-preview">
                  <!-- Layout preview visualization -->
                  <div class="preview-grid" style="grid-template-columns: repeat(12, 1fr);">
                    {#each layout.default_placements as placement}
                      <div 
                        class="preview-item"
                        style="grid-column: {placement.x + 1} / {placement.x + placement.w + 1}; grid-row: {placement.y + 1} / {placement.y + placement.h + 1};"
                      ></div>
                    {/each}
                  </div>
                </div>
                <p class="layout-description">{layout.description}</p>
                <div class="layout-meta">
                  <span class="module-count">Up to {layout.max_modules} modules</span>
                  <Icon icon={layout.id === 'mobile-stack' ? Smartphone : Layout} size={16} />
                </div>
              </CardContent>
            </Card>
          {/each}
        </div>
      {/if}
    </div>
  {/if}

  <!-- Digest Building Step - Phase 2 Enhanced -->
  {#if currentStep === 'digest-building'}
    <div class="digest-builder {splitViewMode}" class:fullscreen={isFullscreen}>
      <!-- Enhanced Toolbar -->
      <div class="builder-toolbar">
        <div class="toolbar-left">
          <div class="layout-info">
            <Icon icon={Grid} size={20} />
            <span>{selectedLayout?.name}</span>
            <span class="layout-modules">
              {currentDigest?.module_placements?.length || 0}/{selectedLayout?.max_modules || 0} modules
            </span>
          </div>
        </div>
        
        <div class="toolbar-center">
          {#if !isMobile}
            <Button 
              variant="outline" 
              size="sm"
              onclick={toggleModuleBrowser}
              class="toggle-browser-btn"
            >
              <Icon icon={PanelLeft} size={16} />
              {moduleBrowserCollapsed ? 'Show' : 'Hide'} Browser
            </Button>
          {/if}
        </div>

        <div class="toolbar-right">
          <Button 
            variant="outline" 
            size="sm"
            onclick={toggleFullscreen}
            class="fullscreen-btn"
          >
            <Icon icon={Maximize} size={16} />
            {isFullscreen ? 'Exit' : 'Fullscreen'}
          </Button>
          <Button 
            variant="outline" 
            onclick={() => {
              showModuleSelector = true;
              if (isMobile) isFullscreen = true;
            }}
          >
            <Icon icon={Plus} size={16} />
            Add Module
          </Button>
          <Button onclick={saveDigest} disabled={isLoading}>
            {isLoading ? 'Saving...' : 'Save Digest'}
          </Button>
        </div>
      </div>

      <!-- Phase 2: Split View Layout -->
      {#if splitViewMode === 'desktop' && showSplitView}
        <div class="split-view-container">
          <!-- Left Panel: Module Browser -->
          <div class="module-browser-panel" class:collapsed={moduleBrowserCollapsed}>
            <div class="browser-header">
              <h3>Content Library</h3>
              <Button 
                variant="ghost" 
                size="sm"
                onclick={toggleModuleBrowser}
              >
                <Icon icon={X} size={16} />
              </Button>
            </div>
            <div class="browser-content">
              {#if showModuleSelector && !moduleBrowserCollapsed}
                <NewItemsSelector 
                  startOpen={true}
                  mode="digest-builder"
                  targetPosition={targetGridPosition}
                  excludeIds={currentDigest?.module_placements?.map(p => p.module_cpt_id) || []}
                  onModuleSelect={handleModuleSelected}
                  showFab={false}
                  showPositionInfo={false}
                  singleSelect={false}
                  compactMode={true}
                  on:close={() => {
                    showModuleSelector = false;
                    targetGridPosition = null;
                  }}
                />
              {:else}
                <div class="browser-placeholder">
                  <Icon icon={Plus} size={48} />
                  <p>Click "Add Module" or a grid placeholder to browse content</p>
                </div>
              {/if}
            </div>
          </div>

          <!-- Right Panel: GridStack Canvas -->
          <div class="grid-canvas-panel" class:expanded={moduleBrowserCollapsed}>
            <div class="canvas-header">
              <h3>Digest Layout</h3>
              <div class="canvas-controls">
                <Button variant="ghost" size="sm" title="Reset Layout">
                  <Icon icon={RefreshCw} size={16} />
                </Button>
              </div>
            </div>
            <div class="grid-stack-container">
              <div class="grid-stack"></div>
            </div>
          </div>
        </div>
      {:else}
        <!-- Mobile Full-Screen Layout -->
        <div class="mobile-layout-container">
          <div class="grid-stack-container mobile">
            <div class="grid-stack"></div>
          </div>
        </div>
      {/if}

      <!-- Enhanced Trash Zone -->
      <div class="trash-zone" class:mobile={isMobile}>
        <div class="trash-content">
          <Icon icon={Trash2} size={24} />
          <span>Drop here to remove</span>
        </div>
      </div>

      <!-- Module Preview Modal -->
      {#if selectedModulePreview}
        <Dialog open={true} onClose={() => selectedModulePreview = null}>
          <DialogContent class="module-preview-modal">
            <DialogHeader>
              <DialogTitle>{selectedModulePreview.title}</DialogTitle>
            </DialogHeader>
            <div class="preview-content">
              <div class="preview-type">{selectedModulePreview.type}</div>
              <div class="preview-text">{selectedModulePreview.content}</div>
            </div>
          </DialogContent>
        </Dialog>
      {/if}
    </div>
  {/if}

  <!-- Enhanced Module Selector for Mobile -->
  {#if isMobile && showModuleSelector}
    <Dialog open={true} onClose={() => {
      showModuleSelector = false;
      isFullscreen = false;
      targetGridPosition = null;
    }}>
      <DialogContent class="mobile-module-selector {isFullscreen ? 'fullscreen' : ''}">
        <DialogHeader>
          <DialogTitle class="flex items-center justify-between">
            <span>Select Module</span>
            <Button 
              variant="ghost" 
              size="sm"
              onclick={() => {
                showModuleSelector = false;
                isFullscreen = false;
                targetGridPosition = null;
              }}
            >
              <Icon icon={X} size={20} />
            </Button>
          </DialogTitle>
          {#if targetGridPosition}
            <p class="text-sm text-muted-foreground">
              Position: {targetGridPosition.x}, {targetGridPosition.y} ({targetGridPosition.w}×{targetGridPosition.h})
            </p>
          {/if}
        </DialogHeader>
        <NewItemsSelector 
          startOpen={true}
          mode="module-selector"
          targetPosition={targetGridPosition}
          excludeIds={currentDigest?.module_placements?.map(p => p.module_cpt_id) || []}
          onModuleSelect={handleModuleSelected}
          showFab={false}
          showPositionInfo={false}
          singleSelect={true}
          compactMode={true}
          on:close={() => {
            showModuleSelector = false;
            isFullscreen = false;
            targetGridPosition = null;
          }}
        />
      </DialogContent>
    </Dialog>
  {/if}
</div>

<style>
  .digest-creator {
    min-height: 100vh;
    padding: 1rem;
    background-color: hsl(var(--background));
  }

  .creator-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid hsl(var(--border));
  }

  .creator-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: hsl(var(--foreground));
  }

  .error-message {
    background-color: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
  }

  /* Destination Choice Styles */
  .destination-choice {
    max-width: 800px;
    margin: 0 auto;
  }

  .choice-description {
    text-align: center;
    margin-bottom: 2rem;
    color: hsl(var(--muted-foreground));
  }

  .pre-selected-info {
    margin-top: 1rem;
    padding: 1rem;
    background-color: hsl(var(--muted) / 0.3);
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
  }

  .pre-selected-module {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .module-preview {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }

  .module-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    flex-shrink: 0;
  }

  .module-details {
    flex: 1;
    min-width: 0;
  }

  .module-title {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    line-height: 1.3;
  }

  .module-type {
    margin: 0 0 0.5rem 0;
    font-size: 0.75rem;
    color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.1);
    padding: 0.125rem 0.375rem;
    border-radius: var(--radius);
    text-transform: uppercase;
    font-weight: 500;
    display: inline-block;
  }

  .module-excerpt {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .module-source {
    margin: 0;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    font-weight: 500;
  }

  .choice-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
  }

  .choice-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    text-align: center;
  }

  .choice-card:hover {
    border-color: hsl(var(--primary));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .choice-icon {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
    color: hsl(var(--primary));
  }

  .existing-digests {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .digest-option {
    padding: 0.5rem;
    background-color: hsl(var(--muted) / 0.3);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: background-color 0.2s ease;
    text-align: left;
  }

  .digest-option:hover {
    background-color: hsl(var(--muted) / 0.5);
  }

  /* Layout Selection Styles */
  .layout-selection {
    max-width: 1200px;
    margin: 0 auto;
  }

  .layout-description {
    text-align: center;
    margin-bottom: 2rem;
    color: hsl(var(--muted-foreground));
  }

  .layout-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .layout-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
  }

  .layout-card:hover {
    border-color: hsl(var(--primary));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .layout-preview {
    margin: 1rem 0;
    height: 120px;
    background-color: hsl(var(--muted) / 0.3);
    border-radius: var(--radius);
    padding: 0.5rem;
  }

  .preview-grid {
    display: grid;
    gap: 2px;
    height: 100%;
    grid-template-rows: repeat(6, 1fr);
  }

  .preview-item {
    background-color: hsl(var(--primary) / 0.6);
    border-radius: 2px;
  }

  .layout-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
  }

  /* Phase 2: Enhanced Digest Builder Styles */
  .digest-builder {
    max-width: 1400px;
    margin: 0 auto;
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

  .layout-modules {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    background-color: hsl(var(--muted) / 0.3);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius);
  }

  /* Phase 2: Split View Layout */
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

  .browser-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    color: hsl(var(--muted-foreground));
    text-align: center;
    padding: 2rem;
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

  .grid-canvas-panel.expanded {
    /* Additional styles when module browser is collapsed */
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

  /* Mobile Layout Container */
  .mobile-layout-container {
    width: 100%;
  }

  /* Phase 2: Enhanced GridStack Styles */
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

  /* Enhanced Placeholder Styles */
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
  }

  :global(.placeholder-content:hover) {
    border-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.1);
    transform: scale(1.02);
  }

  :global(.enhanced-placeholder) {
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
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

  /* Enhanced Module Content Styles */
  :global(.module-content) {
    background-color: hsl(var(--background));
    position: relative;
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
    background-color: hsl(var(--muted) / 0.3);
    color: hsl(var(--foreground));
  }

  :global(.remove-module:hover) {
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

  /* Drag and drop states */
  :global(body.dragging-module) {
    cursor: grabbing !important;
  }

  :global(.grid-stack-item.ui-draggable-dragging) {
    z-index: 1000;
    transform: rotate(5deg);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  }

  /* Enhanced Trash Zone */
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

  /* Module Preview Modal */
  .module-preview-modal {
    max-width: 500px;
  }

  .preview-content {
    padding: 1rem 0;
  }

  .preview-type {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    background-color: hsl(var(--muted) / 0.3);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius);
    display: inline-block;
    margin-bottom: 1rem;
  }

  .preview-text {
    line-height: 1.6;
    color: hsl(var(--foreground));
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

  /* Fullscreen body class */
  :global(body.digest-fullscreen) {
    overflow: hidden;
  }

  .loading-state {
    text-align: center;
    padding: 2rem;
    color: hsl(var(--muted-foreground));
  }

  /* Phase 2: Enhanced Responsive Design */
  @media (max-width: 768px) {
    .digest-creator {
      padding: 0.5rem;
    }

    .choice-grid,
    .layout-grid {
      grid-template-columns: 1fr;
    }

    /* Mobile Toolbar */
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
      display: none; /* Hide desktop-only controls */
    }

    .layout-info {
      justify-content: center;
      text-align: center;
    }

    .layout-modules {
      margin-top: 0.25rem;
    }

    /* Mobile Split View - Force mobile layout */
    .split-view-container {
      display: none;
    }

    .mobile-layout-container {
      display: block;
    }

    /* Mobile Grid Container */
    .grid-stack-container.mobile {
      min-height: calc(100vh - 250px);
      margin-bottom: 1rem;
    }

    /* Mobile Placeholders */
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

    /* Mobile Module Content */
    :global(.module-content) {
      min-height: 120px;
    }

    :global(.module-header) {
      margin-bottom: 0.5rem;
      padding-bottom: 0.5rem;
    }

    :global(.module-title) {
      font-size: 0.9rem;
      -webkit-line-clamp: 1;
    }

    :global(.module-actions) {
      gap: 0.5rem;
    }

    :global(.module-action-btn) {
      padding: 0.5rem;
    }

    /* Mobile Trash Zone */
    .trash-zone {
      position: relative;
      bottom: auto;
      right: auto;
      margin: 1rem auto;
      width: 200px;
      height: 60px;
    }

    /* Mobile Fullscreen */
    .digest-builder.fullscreen {
      padding: 0.5rem;
    }

    /* Touch-friendly interactions */
    :global(.grid-stack-item) {
      touch-action: none;
    }

    :global(.placeholder-content:active) {
      transform: scale(0.98);
    }
  }

  /* Tablet optimizations */
  @media (min-width: 769px) and (max-width: 1024px) {
    .module-browser-panel {
      width: 350px;
    }

    .builder-toolbar {
      padding: 0.875rem;
    }

    .toolbar-center {
      flex: 0.5;
    }
  }

  /* Large screen optimizations */
  @media (min-width: 1400px) {
    .digest-builder {
      max-width: 1600px;
    }

    .module-browser-panel {
      width: 450px;
    }

    .split-view-container {
      height: calc(100vh - 180px);
    }
  }
</style> 