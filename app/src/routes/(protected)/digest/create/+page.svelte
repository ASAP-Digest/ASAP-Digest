<!--
  Enhanced Digest Creation Flow - Apple-like Experience
  ---------------------------------------------------
  
  Implements the consolidated NIS integration with proper flow:
  1. Destination Choice (Add to Existing vs Create New)
  2. Layout Selection (for new digests)
  3. Interactive Digest Building (redirect to builder)
  
  Features:
  - Pre-selected module support from NIS
  - Seamless flow integration
  - Mobile-optimized experience
  - Real-time user digest loading
-->
<script>
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { authStore } from '$lib/utils/auth-persistence.js';
  import { 
    fetchLayoutTemplates, 
    createDraftDigest, 
    fetchUserDigests,
    addModuleToDigest
  } from '$lib/api/digest-builder.js';
  import Button from '$lib/components/ui/button/button.svelte';
  import Card from '$lib/components/ui/card/card.svelte';
  import CardContent from '$lib/components/ui/card/card-content.svelte';
  import CardHeader from '$lib/components/ui/card/card-header.svelte';
  import CardTitle from '$lib/components/ui/card/card-title.svelte';
  import { ArrowLeft, Plus, FolderPlus, FileEdit, BookOpen, Grid } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { getUserData } from '$lib/stores/user.js';
  import { Badge } from '$lib/components/ui/badge';

  /** @type {import('./$types').PageData} */
  const { data } = $props();

  // State management
  let isLoading = $state(true);
  let isLoadingUserDigests = $state(false);
  let error = $state('');
  let layoutTemplates = $state([]);
  let userDigests = $state([]);
  let selectedLayout = $state(null);
  let currentStep = $state('destination-choice'); // 'destination-choice', 'layout-selection'
  let preSelectedModule = $state(null);
  let currentUser = $state(null);

  // Get user data helper for cleaner access
  const userData = $derived(getUserData(data.user));
  
  console.log('[Digest Create] Component loaded with data:', data);
  console.log('[Digest Create] User data:', userData);

  // Reactive effect to load data when user becomes available
  $effect(() => {
    const user = authStore.get();
    currentUser = user;
    
    if (user?.id && userData.isValid) {
      // Load layout templates and user digests
      if (layoutTemplates.length === 0) {
        loadLayoutTemplates();
      }
      if (userDigests.length === 0) {
        loadUserDigests();
      }
    }
  });

  // Initialize on mount
  onMount(async () => {
    // Check if we're coming from NIS with a pre-selected module
    const storedModule = sessionStorage.getItem('digest-preselected-module');
    if (storedModule) {
      try {
        preSelectedModule = JSON.parse(storedModule);
        // Clear the stored data
        sessionStorage.removeItem('digest-preselected-module');
        console.log('[Digest Create] Pre-selected module found:', preSelectedModule);
      } catch (e) {
        console.warn('Failed to parse stored module data:', e);
      }
    }
    
    // If no pre-selected module, redirect to main digest page
    if (!preSelectedModule) {
      console.log('[Digest Create] No pre-selected module, redirecting to digest page');
      goto('/digest');
      return;
    }
  });

  // Fallback templates in case backend is not available
  function getFallbackTemplates() {
    return [
      {
        id: 'solo-focus',
        name: 'Solo Focus',
        description: 'Perfect for highlighting a single important story',
        max_modules: 1,
        capacity: '1 module',
        gridstack_config: {
          cellHeight: 80,
          verticalMargin: 10,
          horizontalMargin: 10,
          column: 12,
          animate: true,
          float: false
        },
        default_placements: [
          { x: 2, y: 1, w: 8, h: 4, id: 'main-content' }
        ]
      },
      {
        id: 'dynamic-duo',
        name: 'Dynamic Duo',
        description: 'Ideal for comparing two stories or complementary content',
        max_modules: 2,
        capacity: '2 modules',
        gridstack_config: {
          cellHeight: 80,
          verticalMargin: 10,
          horizontalMargin: 10,
          column: 12,
          animate: true,
          float: false
        },
        default_placements: [
          { x: 0, y: 1, w: 6, h: 4, id: 'content-1' },
          { x: 6, y: 1, w: 6, h: 4, id: 'content-2' }
        ]
      },
      {
        id: 'information-hub',
        name: 'Information Hub',
        description: 'Comprehensive layout for multiple stories',
        max_modules: 4,
        capacity: '4 modules',
        gridstack_config: {
          cellHeight: 60,
          verticalMargin: 10,
          horizontalMargin: 10,
          column: 12,
          animate: true,
          float: false
        },
        default_placements: [
          { x: 0, y: 0, w: 8, h: 3, id: 'featured' },
          { x: 8, y: 0, w: 4, h: 3, id: 'sidebar-1' },
          { x: 0, y: 3, w: 6, h: 3, id: 'content-1' },
          { x: 6, y: 3, w: 6, h: 3, id: 'content-2' }
        ]
      }
    ];
  }

  // Load available layout templates
  async function loadLayoutTemplates() {
    try {
      console.log('Loading layout templates...');
      
      const response = await fetchLayoutTemplates();
      console.log('Layout templates response:', response);
      
      if (response.success) {
        let templates = [];
        if (response.data && Array.isArray(response.data.data)) {
          templates = response.data.data;
        } else if (response.data && Array.isArray(response.data)) {
          templates = response.data;
        }
        
        console.log(`Loaded ${templates.length} layout templates from API`);
        
        if (templates.length === 0) {
          console.log('No templates from API, using fallback templates');
          layoutTemplates = getFallbackTemplates();
        } else {
          layoutTemplates = templates;
        }
      } else {
        console.log('API failed, using fallback templates');
        layoutTemplates = getFallbackTemplates();
      }
    } catch (err) {
      console.error('Layout templates exception:', err);
      layoutTemplates = getFallbackTemplates();
    }
  }

  // Load user's existing digests
  async function loadUserDigests() {
    if (!userData.isValid || isLoadingUserDigests) return;
    
    isLoadingUserDigests = true;
    
    try {
      console.log('[DEBUG] Loading user digests for user:', userData.debugInfo);
      
      const wpUserId = userData.wpUserId;
      if (!wpUserId) {
        console.error('[ERROR] No WordPress user ID found');
        userDigests = [];
        return;
      }

      const response = await fetchUserDigests(wpUserId, 'draft');
      
      if (response.success) {
        userDigests = response.data || [];
        console.log('[SUCCESS] Loaded user digests:', userDigests.length);
      } else {
        console.error('[ERROR] Failed to load user digests:', response.error);
        userDigests = [];
      }
    } catch (err) {
      console.error('[ERROR] Exception loading user digests:', err);
      userDigests = [];
    } finally {
      isLoadingUserDigests = false;
      isLoading = false;
    }
  }

  // Handle destination choice: Create new digest
  function chooseNewDigest() {
    currentStep = 'layout-selection';
  }

  // Handle destination choice: Add to existing digest
  async function chooseExistingDigest(digest) {
    if (!preSelectedModule) return;
    
    try {
      isLoading = true;
      
      // Add the pre-selected module to the existing digest
      // For now, we'll add it to the first available position
      const response = await addModuleToDigest(
        digest.id, 
        preSelectedModule.id, 
        {
          grid_x: 0,
          grid_y: 0,
          grid_width: 6,
          grid_height: 4
        }
      );

      if (response.success) {
        // Navigate to the digest builder to continue editing
        goto(`/digest/builder?layout=${digest.layout_template_id}&digest=${digest.id}`);
      } else {
        error = 'Failed to add module to digest';
      }
    } catch (err) {
      error = err.message || 'Failed to add module to digest';
    } finally {
      isLoading = false;
    }
  }

  // Handle layout selection and create new digest
  async function selectLayout(layout) {
    if (!userData.isValid || !preSelectedModule) {
      console.error('[ERROR] No valid user or pre-selected module found');
      return;
    }

    try {
      isLoading = true;
      selectedLayout = layout;
      
      console.log('[DEBUG] Creating digest with layout:', layout.id, 'for user:', userData.debugInfo);
      
      const wpUserId = userData.wpUserId;
      if (!wpUserId) {
        console.error('[ERROR] No WordPress user ID found');
        return;
      }
      
      const response = await createDraftDigest(wpUserId, layout.id);
      
      if (response.success) {
        console.log('[SUCCESS] Created draft digest:', response.data);
        
        // Navigate to the digest builder with the pre-selected module
        const digestId = response.data.digest_id;
        const moduleParam = encodeURIComponent(JSON.stringify(preSelectedModule));
        goto(`/digest/builder?layout=${layout.id}&digest=${digestId}&preselected=${moduleParam}`);
      } else {
        console.error('[ERROR] Failed to create draft digest:', response.error);
        error = 'Failed to create digest';
      }
    } catch (err) {
      console.error('[ERROR] Exception creating draft digest:', err);
      error = err.message || 'Failed to create digest';
    } finally {
      isLoading = false;
    }
  }

  // Navigate back
  function goBack() {
    if (currentStep === 'layout-selection') {
      currentStep = 'destination-choice';
    } else {
      goto('/digest');
    }
  }
</script>

<svelte:head>
  <title>Create Digest - ASAP Digest</title>
</svelte:head>

<div class="container py-8 max-w-4xl">
  <!-- Header -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <Button variant="ghost" onclick={goBack} class="p-2">
        <Icon icon={ArrowLeft} size={20} />
      </Button>
      <div>
        <h1 class="text-3xl font-bold">
          {#if currentStep === 'destination-choice'}
            Add Content to Digest
          {:else}
            Choose Digest Layout
          {/if}
        </h1>
        <p class="text-muted-foreground">
          {#if currentStep === 'destination-choice'}
            Where would you like to add this content?
          {:else}
            Select a layout template for your new digest
          {/if}
        </p>
      </div>
    </div>
    
    <!-- User Info and Pre-selected Module -->
    <div class="flex items-center gap-4 text-sm">
      <div class="flex items-center gap-2">
        <span class="text-muted-foreground">Creating for:</span>
        <Badge variant="secondary">{userData.displayName}</Badge>
      </div>
      {#if preSelectedModule}
        <div class="flex items-center gap-2">
          <span class="text-muted-foreground">Selected content:</span>
          <Badge variant="outline" class="max-w-48 truncate">{preSelectedModule.title}</Badge>
        </div>
      {/if}
    </div>
  </div>

  {#if isLoading}
    <div class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <span class="ml-3">Loading...</span>
    </div>
  {:else if error}
    <div class="text-center py-12">
      <p class="text-destructive mb-4">{error}</p>
      <Button onclick={() => window.location.reload()}>Try Again</Button>
    </div>
  {:else if currentStep === 'destination-choice'}
    <!-- Destination Choice Step -->
    <div class="space-y-6">
      <!-- Create New Digest Option -->
      <Card class="cursor-pointer transition-all duration-200 hover:shadow-lg hover:border-primary/50" onclick={chooseNewDigest}>
        <CardHeader>
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
              <Icon icon={Plus} size={24} class="text-primary" />
            </div>
            <div>
              <CardTitle class="text-xl">Create New Digest</CardTitle>
              <p class="text-muted-foreground">Start a fresh digest with this content</p>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-sm text-muted-foreground">
            Choose a layout template and build a new digest around your selected content.
            Perfect for creating focused, themed collections.
          </p>
        </CardContent>
      </Card>

      <!-- Add to Existing Digest Option -->
      {#if userDigests.length > 0}
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <Icon icon={FolderPlus} size={20} />
            Add to Existing Digest
          </h3>
          <div class="grid gap-3">
            {#each userDigests as digest}
              <Card class="cursor-pointer transition-all duration-200 hover:shadow-lg hover:border-primary/50" onclick={() => chooseExistingDigest(digest)}>
                <CardContent class="p-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded bg-muted flex items-center justify-center">
                        <Icon icon={FileEdit} size={16} />
                      </div>
                      <div>
                        <h4 class="font-medium">{digest.title || `Draft Digest ${digest.id}`}</h4>
                        <p class="text-sm text-muted-foreground">
                          {digest.module_count || 0} modules • Last updated {new Date(digest.updated_at).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                    <Badge variant="outline">Draft</Badge>
                  </div>
                </CardContent>
              </Card>
            {/each}
          </div>
        </div>
      {:else if !isLoadingUserDigests}
        <Card class="border-dashed">
          <CardContent class="p-8 text-center">
            <Icon icon={BookOpen} size={48} class="mx-auto mb-4 text-muted-foreground" />
            <h3 class="text-lg font-semibold mb-2">No Existing Digests</h3>
            <p class="text-muted-foreground mb-4">You don't have any draft digests yet.</p>
            <Button onclick={chooseNewDigest}>Create Your First Digest</Button>
          </CardContent>
        </Card>
      {/if}
    </div>
  {:else if currentStep === 'layout-selection'}
    <!-- Layout Selection Step -->
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {#each layoutTemplates as layout}
          <Card 
            class="cursor-pointer transition-all duration-200 hover:shadow-lg {selectedLayout?.id === layout.id ? 'ring-2 ring-primary shadow-lg' : ''}"
            onclick={() => selectLayout(layout)}
          >
            <CardHeader class="pb-3">
              <div class="flex items-start justify-between">
                <div>
                  <CardTitle class="text-lg">{layout.name}</CardTitle>
                  <Badge variant="outline" class="mt-1">{layout.capacity || `${layout.max_modules} modules`}</Badge>
                </div>
                {#if selectedLayout?.id === layout.id}
                  <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-foreground" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                {/if}
              </div>
            </CardHeader>
            <CardContent>
              <!-- Layout Preview -->
              <div class="aspect-video bg-muted rounded-lg mb-3 flex items-center justify-center">
                <div class="w-full h-full flex items-center justify-center">
                  <Icon icon={Grid} size={32} class="text-muted-foreground" />
                </div>
              </div>
              
              <p class="text-sm text-muted-foreground">{layout.description}</p>
            </CardContent>
          </Card>
        {/each}
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between items-center pt-4">
        <div class="text-sm text-muted-foreground">
          {#if selectedLayout}
            Selected: <strong>{selectedLayout.name}</strong>
          {:else}
            Select a layout to continue
          {/if}
        </div>
        
        <div class="flex gap-3">
          <Button variant="outline" onclick={goBack}>
            Back
          </Button>
          <Button 
            onclick={() => selectedLayout && selectLayout(selectedLayout)} 
            disabled={!selectedLayout}
            class="min-w-32"
          >
            Create Digest
          </Button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  .container {
    min-height: calc(100vh - 4rem);
  }
</style> 