import { writable } from 'svelte/store';

/** @typedef {'success' | 'error' | 'info' | 'warning'} ToastType */
/** @typedef {{ id: string, type: ToastType, message: string, duration: number }} Toast */

function createToastStore() {
  const { subscribe, update } = writable(/** @type {Toast[]} */ ([]));

  return {
    subscribe,
    /** @param {string} message @param {ToastType} type @param {number} duration */
    show: (message, type = 'info', duration = 5000) => {
      const id = Math.random().toString(36).slice(2);
      update(toasts => [...toasts, { id, type, message, duration }]);
      if (duration > 0) {
        setTimeout(() => {
          update(toasts => toasts.filter(t => t.id !== id));
        }, duration);
      }
      return id;
    },
    /** @param {string} id */
    remove: (id) => {
      update(toasts => toasts.filter(t => t.id !== id));
    }
  };
}

export const toasts = createToastStore(); 