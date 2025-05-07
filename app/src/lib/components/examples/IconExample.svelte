<script>
  // @ts-ignore - Svelte component import
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { Search, Bell, Check, Shield, User } from '$lib/utils/lucide-compat.js';
</script>

<div class="p-4 bg-[hsl(var(--surface-1))] rounded-md space-y-4">
  <h2 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)]">Icon Usage Examples</h2>
  
  <div class="flex flex-wrap gap-4">
    <!-- Basic icon usage -->
    <div class="flex flex-col items-center">
      <Icon icon={Search} class="w-6 h-6" />
      <span class="text-[var(--font-size-sm)]">Search</span>
    </div>
    
    <!-- Icon with custom size -->
    <div class="flex flex-col items-center">
      <Icon icon={Bell} size={32} class="text-[hsl(var(--brand))]" />
      <span class="text-[var(--font-size-sm)]">Bell (32px)</span>
    </div>
    
    <!-- Icon with custom color -->
    <div class="flex flex-col items-center">
      <Icon icon={Check} class="w-6 h-6 text-[hsl(var(--functional-success))]" />
      <span class="text-[var(--font-size-sm)]">Success</span>
    </div>
    
    <!-- Icon with different stroke width -->
    <div class="flex flex-col items-center">
      <Icon icon={Shield} class="w-6 h-6" strokeWidth="1" />
      <span class="text-[var(--font-size-sm)]">Thin Shield</span>
    </div>
    
    <!-- Icon in a button -->
    <button class="inline-flex items-center gap-2 px-3 py-2 rounded bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))]">
      <Icon icon={User} class="w-4 h-4" />
      <span>Profile</span>
    </button>
  </div>
  
  <div class="mt-6 p-3 border border-[hsl(var(--border))] rounded bg-[hsl(var(--surface-2))]">
    <pre class="text-[var(--font-size-sm)] overflow-x-auto">
{`// CORRECT import pattern:
import Icon from '$lib/components/ui/icon/icon.svelte';
import { Search, Bell } from '$lib/utils/lucide-compat.js';

// CORRECT usage:
<Icon icon={Search} class="w-6 h-6" />

// INCORRECT - never import directly from lucide-svelte:
// import { Search } from 'lucide-svelte';

// INCORRECT - never use icon component directly:
// <Search class="w-6 h-6" />
`}
    </pre>
  </div>
</div> 