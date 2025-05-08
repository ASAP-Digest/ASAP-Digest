/**
 * Store for managing and persisting selected content items
 * @module stores/selected-items-store
 */

import { writable, get } from 'svelte/store';
import { browser } from '$app/environment';
import { createSelectedItemsManager } from '$lib/api/content-fetcher.js';

/**
 * @typedef {import('$lib/api/content-fetcher').ContentItem} ContentItem
 */

// Storage key for persisting selected items
const STORAGE_KEY = 'asapdigest_selected_items';

/**
 * Selected items manager with persistence
 */
const manager = createSelectedItemsManager(STORAGE_KEY);

/**
 * Create a writable store backed by the selected items manager
 */
function createSelectedItemsStore() {
  // Create a writable store
  const { subscribe, set } = writable(manager.getItems());
  
  // Subscribe to changes in the manager
  manager.subscribe((items) => {
    set(items);
  });
  
  return {
    subscribe,
    
    /**
     * Add an item to the selection
     * @param {ContentItem} item 
     */
    add: (item) => {
      manager.addItem(item);
    },
    
    /**
     * Remove an item from the selection
     * @param {ContentItem} item 
     */
    remove: (item) => {
      manager.removeItem(item);
    },
    
    /**
     * Toggle selection state of an item
     * @param {ContentItem} item 
     */
    toggle: (item) => {
      manager.toggleItem(item);
    },
    
    /**
     * Check if an item is selected
     * @param {ContentItem} item 
     * @returns {boolean}
     */
    isSelected: (item) => {
      return manager.isSelected(item);
    },
    
    /**
     * Clear all selected items
     */
    clear: () => {
      manager.clearItems();
    },
    
    /**
     * Get all selected items
     * @returns {ContentItem[]}
     */
    getAll: () => {
      return manager.getItems();
    }
  };
}

/**
 * Store for selected items
 */
export const selectedItems = createSelectedItemsStore();

/**
 * Get the count of selected items
 * @returns {number}
 */
export function getSelectedItemsCount() {
  if (!browser) return 0;
  return get(selectedItems).length;
}

/**
 * Add multiple items to selection
 * @param {ContentItem[]} items 
 */
export function addMultipleItems(items) {
  if (!browser || !items || !items.length) return;
  
  items.forEach(item => {
    selectedItems.add(item);
  });
}

/**
 * Replace all selected items with a new set
 * @param {ContentItem[]} items 
 */
export function replaceSelectedItems(items) {
  if (!browser) return;
  
  selectedItems.clear();
  
  if (items && items.length) {
    addMultipleItems(items);
  }
}

/**
 * Get selected items by type
 * @param {string} type 
 * @returns {ContentItem[]}
 */
export function getSelectedItemsByType(type) {
  if (!browser) return [];
  
  const items = get(selectedItems);
  return items.filter(item => item.type === type);
}

/**
 * Check if the maximum allowed items has been reached
 * @param {number} maxItems 
 * @returns {boolean}
 */
export function isMaxItemsReached(maxItems = 10) {
  if (!browser) return false;
  
  const items = get(selectedItems);
  return items.length >= maxItems;
}

/**
 * Generate a summary of selected items by type
 * @returns {{ [type: string]: number }}
 */
export function getSelectionSummary() {
  if (!browser) return {};
  
  const items = get(selectedItems);
  /** @type {{ [type: string]: number }} */
  const summary = {};
  
  items.forEach(item => {
    summary[item.type] = (summary[item.type] || 0) + 1;
  });
  
  return summary;
} 