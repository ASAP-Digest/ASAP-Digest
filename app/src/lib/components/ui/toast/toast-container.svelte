<!-- Toast container component -->
<script>
  import { toasts } from '$lib/stores/toast';
  import Toast from './toast.svelte';
</script>

<div class="fixed bottom-0 right-0 z-50 p-4 space-y-2">
  {#each $toasts as toast (toast.id)}
    <Toast
      type={toast.type}
      message={toast.message}
      duration={toast.duration}
      onClose={() => toasts.remove(toast.id)}
    />
  {/each}
</div> 