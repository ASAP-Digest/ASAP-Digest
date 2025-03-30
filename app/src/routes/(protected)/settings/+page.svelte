<script>
  import { onMount } from 'svelte';
  
  // Settings state
  let emailNotifications = $state(true);
  let pushNotifications = $state(false);
  let darkMode = $state(true);
  let privacyLevel = $state('balanced');
  
  onMount(() => {
    console.log('Settings page mounted');
    // TODO: load user settings from API & localStorage
  });
  
  /**
   * Handle form submission
   * @param {Event} event - The form submit event
   */
  function handleSubmit(event) {
    event.preventDefault();
    console.log('Saving settings...');
    // TODO: save settings to API & localStorage
  }
</script>

<div class="container py-8">
  <h1 class="text-3xl font-bold mb-6 text-[hsl(var(--foreground))]">Settings</h1>

  <form onsubmit={handleSubmit} class="max-w-2xl">
    <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] shadow-sm divide-y divide-[hsl(var(--border))]">
      <!-- Notification Settings -->
      <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">Notification Settings</h2>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Email Notifications</h3>
              <p class="text-sm text-[hsl(var(--muted-foreground))]">Receive email updates about your account</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked={emailNotifications} class="sr-only peer" onchange={() => emailNotifications = !emailNotifications} />
              <div class="w-11 h-6 bg-[hsl(var(--muted))] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[hsl(var(--ring))] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[hsl(var(--primary))]"></div>
            </label>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Push Notifications</h3>
              <p class="text-sm text-[hsl(var(--muted-foreground))]">Receive push notifications on your device</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked={pushNotifications} class="sr-only peer" onchange={() => pushNotifications = !pushNotifications} />
              <div class="w-11 h-6 bg-[hsl(var(--muted))] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[hsl(var(--ring))] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[hsl(var(--primary))]"></div>
            </label>
          </div>
        </div>
      </div>
      
      <!-- Appearance Settings -->
      <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">Appearance</h2>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">Dark Mode</h3>
              <p class="text-sm text-[hsl(var(--muted-foreground))]">Enable dark mode for the application</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked={darkMode} class="sr-only peer" onchange={() => darkMode = !darkMode} />
              <div class="w-11 h-6 bg-[hsl(var(--muted))] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[hsl(var(--ring))] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[hsl(var(--primary))]"></div>
            </label>
          </div>
        </div>
      </div>
      
      <!-- Privacy Settings -->
      <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">Privacy</h2>
        
        <div class="space-y-4">
          <div>
            <h3 class="font-medium mb-2">Privacy Level</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mb-3">Choose your privacy preference</p>
            
            <div class="space-y-2">
              <div class="flex items-center">
                <input id="privacy-strict" type="radio" value="strict" name="privacy" checked={privacyLevel === 'strict'} onchange={() => privacyLevel = 'strict'} class="h-4 w-4 text-[hsl(var(--primary))] border-[hsl(var(--muted))] focus:ring-[hsl(var(--ring))]" />
                <label for="privacy-strict" class="ml-2 text-sm font-medium text-[hsl(var(--foreground))]">
                  Strict
                </label>
              </div>
              
              <div class="flex items-center">
                <input id="privacy-balanced" type="radio" value="balanced" name="privacy" checked={privacyLevel === 'balanced'} onchange={() => privacyLevel = 'balanced'} class="h-4 w-4 text-[hsl(var(--primary))] border-[hsl(var(--muted))] focus:ring-[hsl(var(--ring))]" />
                <label for="privacy-balanced" class="ml-2 text-sm font-medium text-[hsl(var(--foreground))]">
                  Balanced
                </label>
              </div>
              
              <div class="flex items-center">
                <input id="privacy-relaxed" type="radio" value="relaxed" name="privacy" checked={privacyLevel === 'relaxed'} onchange={() => privacyLevel = 'relaxed'} class="h-4 w-4 text-[hsl(var(--primary))] border-[hsl(var(--muted))] focus:ring-[hsl(var(--ring))]" />
                <label for="privacy-relaxed" class="ml-2 text-sm font-medium text-[hsl(var(--foreground))]">
                  Relaxed
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Save Button -->
      <div class="p-6">
        <button type="submit" class="px-4 py-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-md hover:bg-[hsl(var(--primary)/0.9)] transition-colors duration-200">
          Save Settings
        </button>
      </div>
    </div>
  </form>
</div> 