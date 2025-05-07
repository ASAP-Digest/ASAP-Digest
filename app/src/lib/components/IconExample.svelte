<script>
  // Import our custom Icon wrapper
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // Import createIconObject utility
  import { createIconObject } from '$lib/utils/icon-utils';
  
  // Import raw icons from lucide-svelte
  // Note: This is a workaround while we wait for lucide-svelte to be updated for Svelte 5
  
  // The content below represents what would normally be imported from lucide-svelte
  // We're providing basic SVG paths for demonstration purposes
  const HomeIcon = createIconObject('home', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>');
  const UserIcon = createIconObject('user', '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>');
  const SettingsIcon = createIconObject('settings', '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>');
</script>

<section>
  <h2>Icon Examples</h2>
  <div class="flex gap-8 mt-4">
    <div class="flex flex-col items-center gap-2">
      <Icon icon={HomeIcon} />
      <span>Home</span>
    </div>
    <div class="flex flex-col items-center gap-2">
      <Icon icon={UserIcon} size={32} color="blue" />
      <span>User (32px, blue)</span>
    </div>
    <div class="flex flex-col items-center gap-2">
      <Icon icon={SettingsIcon} size={48} strokeWidth={1} />
      <span>Settings (48px, thin)</span>
    </div>
  </div>
</section> 