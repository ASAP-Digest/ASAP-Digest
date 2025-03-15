<script lang="ts">
    let { children } = $props();
    import "../app.css";
    import { onMount } from 'svelte';
    import { registerServiceWorker, swUpdateAvailable, isOnline } from '$lib/hooks/useServiceWorker';
    
    let updateSW: (() => Promise<void>) | undefined;
    
    onMount(() => {
        const { updateServiceWorker } = registerServiceWorker();
        updateSW = updateServiceWorker;
    });
</script>

{#if $swUpdateAvailable}
  <div class="update-banner">
    <p>New version available!</p>
    <button on:click={() => updateSW && updateSW()}>Update</button>
  </div>
{/if}

{#if !$isOnline}
  <div class="offline-banner">
    <p>You are currently offline. Some features may not be available.</p>
  </div>
{/if}

{@render children()}

<style>
  .update-banner, .offline-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #00ffff;
    color: #000;
    padding: 10px;
    text-align: center;
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
  }
  
  .offline-banner {
    background-color: #ffcc00;
  }
  
  button {
    background-color: #000;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
  }
</style>