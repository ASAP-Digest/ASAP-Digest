<!-- Toast notification component -->
<script>
  import { fade } from 'svelte/transition';
  import { Check, X, AlertCircle, Info } from '$lib/utils/lucide-compat.js';
  import Icon from '../icon/icon.svelte';

  /** @type {'success' | 'error' | 'info' | 'warning'} */
  export let type = 'info';
  /** @type {string} */
  export let message = '';
  /** @type {number} */
  export let duration = 5000;
  /** @type {() => void} */
  export let onClose = () => {};

  let visible = true;

  $: icon = {
    success: Check,
    error: X,
    info: Info,
    warning: AlertCircle
  }[type];

  $: bgColor = {
    success: 'bg-[hsl(var(--success))]',
    error: 'bg-[hsl(var(--destructive))]',
    info: 'bg-[hsl(var(--primary))]',
    warning: 'bg-[hsl(var(--warning))]'
  }[type];

  if (duration > 0) {
    setTimeout(() => {
      visible = false;
      onClose();
    }, duration);
  }
</script>

{#if visible}
  <div
    class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg p-4 text-white shadow-lg"
    class:bg-[hsl(var(--success))]="{type === 'success'}"
    class:bg-[hsl(var(--destructive))]="{type === 'error'}"
    class:bg-[hsl(var(--primary))]="{type === 'info'}"
    class:bg-[hsl(var(--warning))]="{type === 'warning'}"
    transition:fade="{{ duration: 200 }}"
    role="alert"
  >
    <Icon {icon} class="h-5 w-5" />
    <span class="text-sm font-medium">{message}</span>
    <button
      class="ml-2 rounded-full p-1 hover:bg-white/20"
      on:click={() => {
        visible = false;
        onClose();
      }}
    >
      <Icon icon={X} class="h-4 w-4" />
      <span class="sr-only">Close</span>
    </button>
  </div>
{/if} 