<!--
  Settings Hub
  -----------
  Central settings page with navigation to specific settings areas:
  - Account settings
  - Profile settings
  - Notification settings
  - Privacy settings
  - Security settings
  
  @file-marker settings-hub-page
  @implementation-context: SvelteKit, Better Auth, Shadcn UI
-->
<script>
  import { ChevronRight } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // No specific script logic for this page, mostly navigation
</script>

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner container wrapper and make sections direct grid-stack-items -->

<!-- Settings Header - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <h1 class="text-2xl font-bold mb-8">Settings</h1>
  </div>
  </div>
  
<!-- Settings Options Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <div class="space-y-6">
      <a href="/settings/account" class="flex items-center justify-between bg-[hsl(var(--card))] p-4 rounded-lg shadow-sm border border-[hsl(var(--border))] hover:bg-[hsl(var(--secondary))] transition-colors">
        <div>
          <h2 class="text-xl font-semibold">Account</h2>
          <p class="text-[hsl(var(--muted-foreground))] text-sm">Manage your profile information, email, and password.</p>
        </div>
        <Icon icon={ChevronRight} size={24} />
      </a>
      
      <a href="/settings/security" class="flex items-center justify-between bg-[hsl(var(--card))] p-4 rounded-lg shadow-sm border border-[hsl(var(--border))] hover:bg-[hsl(var(--secondary))] transition-colors">
        <div>
          <h2 class="text-xl font-semibold">Security</h2>
          <p class="text-[hsl(var(--muted-foreground))] text-sm">Configure two-factor authentication, view active sessions.</p>
      </div>
        <Icon icon={ChevronRight} size={24} />
      </a>
      
      <a href="/settings/privacy" class="flex items-center justify-between bg-[hsl(var(--card))] p-4 rounded-lg shadow-sm border border-[hsl(var(--border))] hover:bg-[hsl(var(--secondary))] transition-colors">
        <div>
          <h2 class="text-xl font-semibold">Privacy</h2>
          <p class="text-[hsl(var(--muted-foreground))] text-sm">Control data usage and preferences.</p>
        </div>
        <Icon icon={ChevronRight} size={24} />
      </a>
    </div>
  </div>
</div> 