<!-- DesignSystemLayout.svelte - Layout component for design system pages -->
<script>
  import { onMount } from 'svelte';
  
  /**
   * Function to force refresh of CSS variables - helpful for design system rendering
   */
  function refreshCSSVariables() {
    // Get all CSS color variables from :root
    const computedStyle = getComputedStyle(document.documentElement);
    const cssVars = Array.from(document.styleSheets)
      .flatMap(sheet => {
        try {
          return Array.from(sheet.cssRules);
        } catch (e) {
          return [];
        }
      })
      .filter(rule => rule.selectorText === ':root')
      .flatMap(rule => Array.from(rule.style))
      .filter(name => name.startsWith('--'));
    
    // Log all CSS variables and their values
    console.log('All CSS variables:', cssVars.map(name => {
      return {
        name,
        value: computedStyle.getPropertyValue(name).trim()
      };
    }));
    
    // Re-apply essential theme color variables
    const themeVars = [
      '--brand', '--brand-fg', '--brand-hover',
      '--accent', '--accent-fg', '--accent-hover',
      '--link', '--link-fg', '--link-hover',
      '--visited', '--visited-fg',
      '--functional-error', '--functional-error-fg',
      '--functional-success', '--functional-success-fg',
      '--canvas-base', '--canvas-fg',
      '--text-1', '--text-2', '--text-3', '--text-disabled',
      '--surface-1', '--surface-2', '--surface-3',
      '--border', '--ring'
    ];
    
    themeVars.forEach(name => {
      const value = computedStyle.getPropertyValue(name).trim();
      if (value) {
        // Apply to both root and body for maximum compatibility
        document.documentElement.style.setProperty(name, value);
        document.body.style.setProperty(name, value);
        
        // Create an explicit CSS variable for direct use
        const directVarName = `--direct-${name.substring(2)}`;
        const hslValue = `hsl(${value})`;
        document.documentElement.style.setProperty(directVarName, hslValue);
        document.body.style.setProperty(directVarName, hslValue);
        
        console.log(`Applied CSS variable ${name}: ${value} -> ${directVarName}: ${hslValue}`);
      }
    });
    
    // Add debug output on the page
    if (document.getElementById('css-debug-output') === null) {
      const debugContainer = document.createElement('div');
      debugContainer.id = 'css-debug-output';
      debugContainer.style.position = 'fixed';
      debugContainer.style.bottom = '0';
      debugContainer.style.right = '0';
      debugContainer.style.background = 'rgba(0,0,0,0.8)';
      debugContainer.style.color = 'white';
      debugContainer.style.padding = '10px';
      debugContainer.style.zIndex = '9999';
      debugContainer.style.fontSize = '12px';
      debugContainer.style.maxHeight = '300px';
      debugContainer.style.overflow = 'auto';
      debugContainer.style.maxWidth = '400px';
      
      const cssVarHtml = themeVars.map(name => {
        const value = computedStyle.getPropertyValue(name).trim();
        return `<div><code>${name}</code>: <span style="color: hsl(${value})">■</span> ${value}</div>`;
      }).join('');
      
      debugContainer.innerHTML = `
        <h4 style="margin: 0 0 10px 0">CSS Variables</h4>
        ${cssVarHtml}
        <button id="refresh-css-vars" style="margin-top: 10px; padding: 5px">Refresh CSS Variables</button>
      `;
      
      document.body.appendChild(debugContainer);
      
      // Add event listener to refresh button
      setTimeout(() => {
        const refreshBtn = document.getElementById('refresh-css-vars');
        if (refreshBtn) {
          refreshBtn.addEventListener('click', refreshCSSVariables);
        }
      }, 100);
    }
  }
  
  onMount(() => {
    // Refresh CSS variables on mount
    refreshCSSVariables();
    
    // Apply global styles specific to design system
    document.body.classList.add('design-system-page');
    
    // Force all design system specific styles
    document.documentElement.style.setProperty('--css-forced', 'true');
    
    // Add direct background and text colors for debugging
    document.body.style.backgroundColor = 'hsl(var(--canvas-base))';
    document.body.style.color = 'hsl(var(--canvas-fg))';
    
    return () => {
      // Clean up on unmount
      document.body.classList.remove('design-system-page');
      const debugOutput = document.getElementById('css-debug-output');
      if (debugOutput) {
        debugOutput.remove();
      }
    };
  });
  
  let {
    children = /** @type {import('svelte').Snippet | undefined} */ (undefined)
  } = $props();
</script>

<!-- We use a specialized container for design system pages -->
<div class="design-system-container">
  <!-- 
    Force HSL variable application with inline style 
    This ensures variables are applied correctly
  -->
  <div 
    class="design-system-context"
    style="
      --applied-canvas-base: hsl(var(--canvas-base));
      --applied-canvas-fg: hsl(var(--canvas-fg));
      --applied-brand: hsl(var(--brand));
      --applied-brand-fg: hsl(var(--brand-fg));
      --applied-accent: hsl(var(--accent));
      --applied-accent-fg: hsl(var(--accent-fg));
      --applied-surface-1: hsl(var(--surface-1));
      --applied-surface-2: hsl(var(--surface-2));
      --applied-text-1: hsl(var(--text-1));
      --applied-text-2: hsl(var(--text-2));
      --applied-border: hsl(var(--border));
      
      /* Fallback direct colors in case HSL variables fail */
      --fallback-canvas-base: hsl(220 13% 18%);
      --fallback-canvas-fg: hsl(210 40% 98%);
      --fallback-brand: hsl(326 100% 60%);
      --fallback-brand-fg: hsl(210 40% 98%);
      --fallback-accent: hsl(175 98% 60%);
      --fallback-accent-fg: hsl(220 13% 18%);
      --fallback-surface-1: hsl(220 13% 23%);
      --fallback-surface-2: hsl(220 13% 28%);
      --fallback-text-1: hsl(210 40% 98%);
      --fallback-text-2: hsl(210 40% 75%);
      --fallback-border: hsl(220 13% 30%);
    "
  >
    {@render children?.()}
  </div>
</div>

<style>
  /* Direct CSS styles for design system components */
  :global(.design-system-page) {
    background-color: hsl(var(--canvas-base, 220 13% 18%)) !important;
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container) {
    width: 100%;
    max-width: 100%;
    min-height: 100vh;
    background-color: hsl(var(--canvas-base, 220 13% 18%)) !important;
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container h1) {
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
    font-size: var(--font-size-xl) !important;
    font-weight: var(--font-weight-semibold) !important;
    margin-bottom: 8 !important;
  }
  
  :global(.design-system-container h2) {
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
    font-size: var(--font-size-lg) !important;
    font-weight: var(--font-weight-semibold) !important;
    margin-bottom: 6 !important;
  }
  
  :global(.design-system-container p) {
    margin-bottom: 5 !important;
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
  }
  
  /* Make sure buttons have the correct styling */
  :global(.design-system-container button.btn-primary) {
    background-color: hsl(var(--brand, 326 100% 60%)) !important;
    color: hsl(var(--brand-fg, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container button.btn-secondary) {
    background-color: hsl(var(--accent, 175 98% 60%)) !important;
    color: hsl(var(--accent-fg, 220 13% 18%)) !important;
  }
  
  /* Override any Tailwind direct color classes */
  :global(.design-system-container .btn-primary) {
    background-color: hsl(var(--brand, 326 100% 60%)) !important;
    color: hsl(var(--brand-fg, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container .text-primary) {
    color: hsl(var(--brand, 326 100% 60%)) !important;
  }
  
  :global(.design-system-container .bg-primary) {
    background-color: hsl(var(--brand, 326 100% 60%)) !important;
  }
  
  :global(.design-system-container .text-secondary) {
    color: hsl(var(--accent, 175 98% 60%)) !important;
  }
  
  :global(.design-system-container .bg-secondary) {
    background-color: hsl(var(--accent, 175 98% 60%)) !important;
  }
  
  :global(.design-system-container .text-background) {
    color: hsl(var(--canvas-base, 220 13% 18%)) !important;
  }
  
  :global(.design-system-container .bg-background) {
    background-color: hsl(var(--canvas-base, 220 13% 18%)) !important;
  }
  
  :global(.design-system-container .text-foreground) {
    color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container .bg-foreground) {
    background-color: hsl(var(--canvas-fg, 210 40% 98%)) !important;
  }
  
  /* Ensure design system context has fallback colors */
  .design-system-context {
    background-color: var(--applied-canvas-base, var(--fallback-canvas-base));
    color: var(--applied-canvas-fg, var(--fallback-canvas-fg));
    min-height: 100vh;
    width: 100%;
  }
</style> 