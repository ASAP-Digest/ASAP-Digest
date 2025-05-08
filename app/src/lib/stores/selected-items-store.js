/**
 * Store for managing and persisting selected content items
 * @module stores/selected-items-store
 */

import { writable, get, derived } from 'svelte/store';
import { browser } from '$app/environment';
import { createSelectedItemsManager } from '$lib/api/content-fetcher.js';

// @ts-ignore - Missing type declarations for localforage
import localforage from 'localforage';

/**
 * @typedef {import('$lib/api/content-fetcher').ContentItem} ContentItem
 */

// Storage key for persisting selected items
const STORAGE_KEY = 'asapdigest_selected_items';

// Storage configuration
localforage.config({
  name: 'asapdigest',
  storeName: 'selected_items',
  description: 'Selected content items for digest creation'
});

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
    },
    
    /**
     * Reorder selected items
     * @param {number} oldIndex - Index of the item to move
     * @param {number} newIndex - Destination index
     */
    reorder: (oldIndex, newIndex) => {
      const items = manager.getItems();
      
      // Validate indices
      if (oldIndex < 0 || oldIndex >= items.length || 
          newIndex < 0 || newIndex >= items.length) {
        return;
      }
      
      // Reorder items
      const [movedItem] = items.splice(oldIndex, 1);
      items.splice(newIndex, 0, movedItem);
      
      // Update store with reordered items
      manager.clearItems();
      items.forEach(item => manager.addItem(item));
    },
    
    /**
     * Clear all selected items and persist the empty state
     */
    persist: () => {
      set([]);
      localforage.setItem(STORAGE_KEY, []);
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

/**
 * Derived store that provides a summary of selected items by type
 */
export const selectionSummary = derived(selectedItems, ($selectedItems) => {
  /** @type {{ [type: string]: number }} */
  const summary = {};
  
  $selectedItems.forEach(item => {
    summary[item.type] = (summary[item.type] || 0) + 1;
  });
  
  return summary;
});

/**
 * Export selected items as JSON
 * @returns {string} JSON string of selected items
 */
export function exportSelectedItemsAsJson() {
  if (!browser) return '';
  
  const items = get(selectedItems);
  return JSON.stringify(items);
}

/**
 * Import selected items from JSON
 * @param {string} json - JSON string of items to import
 * @returns {boolean} Success flag
 */
export function importSelectedItemsFromJson(json) {
  if (!browser) return false;
  
  try {
    const items = JSON.parse(json);
    
    if (!Array.isArray(items)) {
      return false;
    }
    
    replaceSelectedItems(items);
    return true;
  } catch (error) {
    console.error('[Selected Items Store] Import error:', error);
    return false;
  }
}

/**
 * Save selected items to localforage with metadata
 * @param {string} name - Name of the saved selection
 * @param {string} [description=''] - Optional description
 * @returns {Promise<string>} ID of the saved selection
 */
export async function saveSelection(name, description = '') {
  if (!browser) return '';
  
  const items = get(selectedItems);
  const id = `selection_${Date.now()}`;
  
  await localforage.setItem(id, {
    id,
    name,
    description,
    timestamp: Date.now(),
    items
  });
  
  return id;
}

/**
 * Load a saved selection by ID
 * @param {string} id - ID of the saved selection
 * @returns {Promise<boolean>} Success flag
 */
export async function loadSelection(id) {
  if (!browser) return false;
  
  try {
    const saved = await localforage.getItem(id);
    
    if (!saved || !saved.items || !Array.isArray(saved.items)) {
      return false;
    }
    
    replaceSelectedItems(saved.items);
    return true;
  } catch (error) {
    console.error('[Selected Items Store] Load error:', error);
    return false;
  }
}

/**
 * Get all saved selections
 * @returns {Promise<Array<{id: string, name: string, description: string, timestamp: number, count: number}>>} List of saved selections
 */
export async function getSavedSelections() {
  if (!browser) return [];
  
  try {
    /** @type {Array<{id: string, name: string, description: string, timestamp: number, items: ContentItem[]}>} */
    const selections = [];
    
    await localforage.iterate((value, key) => {
      if (typeof key === 'string' && key.startsWith('selection_')) {
        selections.push(value);
      }
    });
    
    // Sort by timestamp (newest first) and map to summary objects
    return selections
      .sort((a, b) => b.timestamp - a.timestamp)
      .map(sel => ({
        id: sel.id,
        name: sel.name,
        description: sel.description,
        timestamp: sel.timestamp,
        count: sel.items.length
      }));
  } catch (error) {
    console.error('[Selected Items Store] Get saved selections error:', error);
    return [];
  }
}

/**
 * Delete a saved selection
 * @param {string} id - ID of the selection to delete
 * @returns {Promise<boolean>} Success flag
 */
export async function deleteSelection(id) {
  if (!browser) return false;
  
  try {
    await localforage.removeItem(id);
    return true;
  } catch (error) {
    console.error('[Selected Items Store] Delete error:', error);
    return false;
  }
} 