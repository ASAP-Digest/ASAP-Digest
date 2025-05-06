/**
 * Global type declaration for Svelte components
 * This eliminates the need for @ts-ignore comments on Svelte imports
 */

declare module '*.svelte' {
  import type { ComponentType, SvelteComponent } from 'svelte';
  
  const component: ComponentType<SvelteComponent>;
  export default component;
} 