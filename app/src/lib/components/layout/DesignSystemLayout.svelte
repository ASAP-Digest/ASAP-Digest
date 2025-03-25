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
      '--background', '--foreground', '--primary', '--primary-foreground',
      '--secondary', '--secondary-foreground', '--accent', '--accent-foreground',
      '--muted', '--muted-foreground', '--card', '--card-foreground',
      '--destructive', '--destructive-foreground', '--border', '--ring'
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
    document.body.style.backgroundColor = 'hsl(var(--background))';
    document.body.style.color = 'hsl(var(--foreground))';
    
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
      --applied-background: hsl(var(--background));
      --applied-foreground: hsl(var(--foreground));
      --applied-primary: hsl(var(--primary));
      --applied-secondary: hsl(var(--secondary));
      --applied-accent: hsl(var(--accent));
      --applied-muted: hsl(var(--muted));
      --applied-card: hsl(var(--card));
      --applied-border: hsl(var(--border));
      
      /* Fallback direct colors in case HSL variables fail */
      --fallback-background: hsl(220 13% 18%);
      --fallback-foreground: hsl(210 40% 98%);
      --fallback-primary: hsl(326 100% 60%);
      --fallback-secondary: hsl(175 98% 60%);
      --fallback-accent: hsl(265 90% 65%);
      --fallback-muted: hsl(220 13% 28%);
      --fallback-card: hsl(220 13% 23%);
      --fallback-border: hsl(220 13% 30%);
    "
  >
    {@render children?.()}
  </div>
</div>

<style>
  /* Direct CSS styles for design system components */
  :global(.design-system-page) {
    background-color: hsl(var(--background, 220 13% 18%)) !important;
    color: hsl(var(--foreground, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container) {
    width: 100%;
    max-width: 100%;
    min-height: 100vh;
    background-color: hsl(var(--background, 220 13% 18%)) !important;
    color: hsl(var(--foreground, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container h1) {
    color: hsl(var(--foreground, 210 40% 98%)) !important;
    font-size: var(--font-size-4xl, 3rem) !important;
    font-weight: var(--font-weight-extrabold, 800) !important;
    margin-bottom: calc(var(--spacing-unit, 0.25rem) * 8) !important;
  }
  
  :global(.design-system-container h2) {
    color: hsl(var(--foreground, 210 40% 98%)) !important;
    font-size: var(--font-size-3xl, 1.875rem) !important;
    font-weight: var(--font-weight-bold, 700) !important;
    margin-bottom: calc(var(--spacing-unit, 0.25rem) * 6) !important;
  }
  
  :global(.design-system-container p) {
    margin-bottom: calc(var(--spacing-unit, 0.25rem) * 5) !important;
    color: hsl(var(--foreground, 210 40% 98%)) !important;
  }
  
  /* Make sure buttons have the correct styling */
  :global(.design-system-container button.btn-primary) {
    background-color: hsl(var(--primary, 326 100% 60%)) !important;
    color: hsl(var(--primary-foreground, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container button.btn-secondary) {
    background-color: hsl(var(--secondary, 175 98% 60%)) !important;
    color: hsl(var(--secondary-foreground, 220 13% 18%)) !important;
  }
  
  /* Override any Tailwind direct color classes */
  :global(.design-system-container .btn-primary) {
    background-color: hsl(var(--primary, 326 100% 60%)) !important;
    color: hsl(var(--primary-foreground, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container .text-primary) {
    color: hsl(var(--primary, 326 100% 60%)) !important;
  }
  
  :global(.design-system-container .bg-primary) {
    background-color: hsl(var(--primary, 326 100% 60%)) !important;
  }
  
  :global(.design-system-container .text-secondary) {
    color: hsl(var(--secondary, 175 98% 60%)) !important;
  }
  
  :global(.design-system-container .bg-secondary) {
    background-color: hsl(var(--secondary, 175 98% 60%)) !important;
  }
  
  :global(.design-system-container .text-background) {
    color: hsl(var(--background, 220 13% 18%)) !important;
  }
  
  :global(.design-system-container .bg-background) {
    background-color: hsl(var(--background, 220 13% 18%)) !important;
  }
  
  :global(.design-system-container .text-foreground) {
    color: hsl(var(--foreground, 210 40% 98%)) !important;
  }
  
  :global(.design-system-container .bg-foreground) {
    background-color: hsl(var(--foreground, 210 40% 98%)) !important;
  }
  
  /* Ensure design system context has fallback colors */
  .design-system-context {
    background-color: var(--applied-background, var(--fallback-background));
    color: var(--applied-foreground, var(--fallback-foreground));
    min-height: 100vh;
    width: 100%;
  }
</style> 