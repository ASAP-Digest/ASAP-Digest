<!-- Toast notification component -->
<script>
  import { fade } from 'svelte/transition';
  import { Check, X, AlertCircle, Info } from '$lib/utils/lucide-compat.js';
  import Icon from '../icon/icon.svelte';

  /**
   * @typedef {Object} ToastProps
   * @property {'success' | 'error' | 'info' | 'warning'} [type] - Type of toast notification
   * @property {string} [message] - Message to display
   * @property {number} [duration] - Duration in milliseconds
   * @property {() => void} [onClose] - Function to call when toast closes
   */

  /** @type {ToastProps} */
  const { 
    type = 'info',
    message = '',
    duration = 5000,
    onClose = () => {}
  } = $props();

  let visible = $state(true);

  const iconMap = {
    success: Check,
    error: X,
    info: Info,
    warning: AlertCircle
  };

  const bgColorMap = {
    success: 'bg-[hsl(var(--functional-success))]',
    error: 'bg-[hsl(var(--functional-error))]',
    info: 'bg-[hsl(var(--brand))]',
    warning: 'bg-[hsl(var(--accent))]'
  };

  const textColorMap = {
    success: 'text-[hsl(var(--functional-success-fg))]',
    error: 'text-[hsl(var(--functional-error-fg))]',
    info: 'text-[hsl(var(--brand-fg))]',
    warning: 'text-[hsl(var(--accent-fg))]'
  };

  const icon = $derived(iconMap[type]);
  const bgColor = $derived(bgColorMap[type]);
  const textColor = $derived(textColorMap[type]);

  if (duration > 0) {
    setTimeout(() => {
      visible = false;
      onClose();
    }, duration);
  }

  function handleClose() {
    visible = false;
    onClose();
  }
</script>

{#if visible}
  <div
    class="fixed bottom-4 right-4 z-[var(--z-notification)] flex items-center gap-2 rounded-[var(--radius-md)] p-4 shadow-[var(--shadow-md)]"
    class:bg-[hsl(var(--functional-success))]={type === 'success'}
    class:bg-[hsl(var(--functional-error))]={type === 'error'}
    class:bg-[hsl(var(--brand))]={type === 'info'}
    class:bg-[hsl(var(--accent))]={type === 'warning'}
    class:text-[hsl(var(--functional-success-fg))]={type === 'success'}
    class:text-[hsl(var(--functional-error-fg))]={type === 'error'}
    class:text-[hsl(var(--brand-fg))]={type === 'info'}
    class:text-[hsl(var(--accent-fg))]={type === 'warning'}
    transition:fade={{ duration: 200 }}
    role="alert"
  >
    <Icon icon={icon} class="h-5 w-5" />
    <span class="text-[var(--font-size-sm)] font-[var(--font-weight-regular)]">{message}</span>
    <button
      class="ml-2 rounded-full p-1 hover:bg-white/20"
      onclick={handleClose}
    >
      <Icon icon={X} class="h-4 w-4" />
      <span class="sr-only">Close</span>
    </button>
  </div>
{/if} 