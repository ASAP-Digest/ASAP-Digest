<script>
  import { onMount } from 'svelte';
  import { theme, setTheme, getAvailableThemes } from '$lib/stores/theme.js';
  
  /**
   * @typedef {Object} ThemeDropdownProps
   * @property {string} [className] - Additional CSS classes
   * @property {boolean} [floating=false] - Whether the dropdown is floating (positioned outside its container)
   * @property {Function} [onClose] - Callback when dropdown is closed
   */
  
  /** @type {ThemeDropdownProps} */
  const { className = "", floating = false, onClose = () => {} } = $props();
  
  /** @type {import('$lib/stores/theme.js').ThemeInfo[]} */
  let themes = $state([]);
  
  /** @type {import('$lib/stores/theme.js').ThemeValue} */
  let currentTheme = $state('default');
  
  /** @type {string} */
  let loadError = $state('');
  
  /** 
   * Flag to show debug info 
   * @type {boolean}
   */
  let showDebug = $state(false);
  
  // Initial load of themes - don't wait for onMount
  try {
    themes = getAvailableThemes();
    console.log('Initial themes load in dropdown:', themes.length);
  } catch (error) {
    console.error('Error loading themes initially:', error);
    loadError = error.message;
  }
  
  // Load themes on mount
  onMount(() => {
    if (themes.length === 0) {
      loadThemes();
    }
    
    // Subscribe to theme changes
    const unsubscribe = theme.subscribe(value => {
      currentTheme = value;
      console.log('Theme dropdown - current theme updated:', value);
    });
    
    // Add click outside handler when floating
    if (floating) {
      const handleClickOutside = (event) => {
        const dropdown = document.querySelector('.theme-dropdown-floating');
        if (dropdown && !dropdown.contains(event.target) && 
            !event.target.closest('[data-theme-toggle]')) {
          onClose();
        }
      };
      
      document.addEventListener('click', handleClickOutside);
      
      return () => {
        document.removeEventListener('click', handleClickOutside);
        unsubscribe();
      };
    }
    
    return () => {
      unsubscribe();
    };
  });
  
  /**
   * Load available themes
   */
  function loadThemes() {
    try {
      loadError = '';
      themes = getAvailableThemes();
      console.log('Loaded themes in dropdown after refresh:', themes.length);
      
      if (themes.length === 0) {
        loadError = 'No themes were found. Try reloading the page.';
      }
    } catch (error) {
      console.error('Error loading themes:', error);
      loadError = error.message;
    }
  }
  
  /**
   * Apply a theme and close dropdown
   * @param {import('$lib/stores/theme.js').ThemeValue} themeValue 
   * @param {MouseEvent} event
   */
  function applyTheme(themeValue, event) {
    // Prevent event bubbling
    if (event) event.stopPropagation();
    
    console.log('Applying theme from dropdown:', themeValue);
    setTheme(themeValue);
    
    // Call onClose callback if provided
    if (floating) {
      onClose();
    }
  }
  
  /**
   * Toggle debug info display
   */
  function toggleDebug() {
    showDebug = !showDebug;
  }
</script>

<div 
  class={`theme-dropdown ${floating ? 'theme-dropdown-floating' : ''} ${className}`} 
  role="menu" 
  onclick={(e) => e.stopPropagation()}
  onkeydown={(e) => {
    if (e.key === 'Escape' && floating) {
      onClose();
    }
  }}
>
  <div class="theme-dropdown-header p-2 text-sm font-medium flex justify-between items-center">
    <span>Theme Selection</span>
    <div class="flex space-x-1">
      <button 
        type="button" 
        class="text-xs text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
        onclick={() => loadThemes()}
      >
        Refresh
      </button>
      <button 
        type="button" 
        class="text-xs text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
        onclick={toggleDebug}
      >
        Debug
      </button>
    </div>
  </div>
  
  {#if showDebug}
    <div class="debug-info p-2 text-xs border-t border-[hsl(var(--border))] bg-[hsl(var(--surface-2))]">
      <div>Current theme: {currentTheme}</div>
      <div>Themes length: {themes.length}</div>
      <div>Themes: {themes.map(t => t.value).join(', ')}</div>
      <div>Error: {loadError || 'None'}</div>
      <div>Floating: {floating.toString()}</div>
    </div>
  {/if}
  
  <div class="theme-list">
    {#if themes && themes.length > 0}
      {#each themes as themeOption}
        <button 
          type="button"
          class="theme-option flex items-center justify-between w-full p-2 text-sm hover:bg-[hsl(var(--surface-2))] rounded-xs transition-colors"
          class:active={currentTheme === themeOption.value}
          onclick={(e) => applyTheme(themeOption.value, e)}
        >
          <div class="flex flex-col items-start">
            <div class="flex items-center gap-2">
              <span class="theme-icon text-sm">{themeOption.icon}</span>
              <span class="font-medium text-sm">{themeOption.name}</span>
            </div>
            {#if themeOption.description}
              <span class="text-xs text-[hsl(var(--text-2))] mt-1 ml-7">{themeOption.description}</span>
            {/if}
          </div>
        </button>
      {/each}
    {:else if loadError}
      <div class="p-2 text-sm text-[hsl(var(--error))]">
        Error: {loadError}. <button class="underline" onclick={() => loadThemes()}>Try again</button>
      </div>
    {:else}
      <div class="p-2 text-sm text-[hsl(var(--text-2))]">
        No themes found. <button class="underline" onclick={() => loadThemes()}>Click refresh</button> to try again.
      </div>
    {/if}
  </div>
</div>

<style>
  .theme-dropdown {
    width: 100%;
    background-color: hsl(var(--surface-1));
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    overflow: hidden;
    z-index: var(--z-dropdown, 9000);
    box-shadow: var(--shadow-md);
    position: relative;
  }
  
  /* Floating dropdown has a fixed position and higher z-index */
  .theme-dropdown-floating {
    position: fixed;
    z-index: var(--z-dropdown-floating, 10000); 
    width: 16rem; /* 256px */
    max-width: 90vw;
    animation: floatingFadeIn 0.2s ease-out;
  }
  
  @keyframes floatingFadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .theme-dropdown-header {
    border-bottom: 1px solid hsl(var(--border));
    color: hsl(var(--text-1));
  }
  
  .theme-list {
    max-height: 300px;
    overflow-y: auto;
  }
  
  .theme-option {
    color: hsl(var(--text-1));
    cursor: pointer;
  }
  
  .theme-option.active {
    background-color: hsl(var(--brand) / 0.15);
    font-weight: 600;
    border-left: 3px solid hsl(var(--brand));
    padding-left: calc(0.5rem - 3px);
  }
  
  /* Animation for theme selection */
  .theme-option:active {
    transform: scale(0.98);
  }
  
  .debug-info {
    font-family: monospace;
    white-space: pre-wrap;
  }
</style> 