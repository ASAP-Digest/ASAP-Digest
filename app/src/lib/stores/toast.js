import { writable } from 'svelte/store';

/** @typedef {'success' | 'error' | 'info' | 'warning'} ToastType */
/** @typedef {{ id: string, type: ToastType, message: string, duration: number }} Toast */

function createToastStore() {
  const { subscribe, update } = writable(/** @type {Toast[]} */ ([]));
  
  /** @type {Object.<string, string>} */
  const activeToasts = {}; // Track active toast messages

  return {
    subscribe,
    /** 
     * Display a toast notification
     * @param {string} message - The toast message
     * @param {ToastType} type - The toast type
     * @param {number} duration - The duration in ms (0 for persistent)
     * @returns {string} The toast ID
     */
    show: (message, type = 'info', duration = 5000) => {
      // Create a key from message and type to track duplicates
      const key = `${message}:${type}`;
      
      // Check if this exact toast is already shown
      if (activeToasts[key]) {
        // Skip creating duplicate toast
        console.log('[Toast] Preventing duplicate toast:', message);
        return activeToasts[key]; // Return existing ID
      }
      
      const id = Math.random().toString(36).slice(2);
      
      // Add to active toasts
      activeToasts[key] = id;
      
      update(toasts => [...toasts, { id, type, message, duration }]);
      
      if (duration > 0) {
        setTimeout(() => {
          // Remove from both store and tracking object
          update(toasts => toasts.filter(t => t.id !== id));
          delete activeToasts[key];
        }, duration);
      }
      
      return id;
    },
    /** 
     * Remove a toast by ID
     * @param {string} id - The toast ID to remove
     */
    remove: (id) => {
      update(toasts => {
        // Find the toast to remove
        const toastToRemove = toasts.find(t => t.id === id);
        
        // If found, remove from tracking object
        if (toastToRemove) {
          const key = `${toastToRemove.message}:${toastToRemove.type}`;
          delete activeToasts[key];
        }
        
        // Remove from store
        return toasts.filter(t => t.id !== id);
      });
    }
  };
}

export const toasts = createToastStore(); 