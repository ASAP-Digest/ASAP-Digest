<script>
    import { getAvailableModules } from '../../api/digest-builder-api';
    import { onMount } from 'svelte';

    // Reactive variables for available modules and loading state
    let availableModules = $state([]);
    let loadingModules = $state(true);

    // Event dispatcher to communicate selected module to parent component
    import { createEventDispatcher } from 'svelte';
    const dispatch = createEventDispatcher();

    function handleModuleSelect(module) {
        console.log('Module selected:', module);
        // TODO: Implement logic to add this module to the digest via API call
        // This might be done in a parent component that manages the digest state and layout
        dispatch('moduleSelected', { module });
    }

    // TODO: Fetch real data on component mount
    onMount(async () => {
        loadingModules = true;
        availableModules = await getAvailableModules();
        loadingModules = false;
    });
</script>

<div class="digest-module-selector">
    <h2>Available Content Modules</h2>

    {#if loadingModules}
        <p>Loading modules...</p>
    {:else if availableModules.length > 0}
        <ul class="module-list">
            {#each availableModules as module (module.id)}
                <li class="module-item">
                    <h3>{module.name}</h3>
                    <p>{module.description}</p>
                    <button on:click={() => handleModuleSelect(module)}>
                        Add to Digest
                    </button>
                </li>
            {/each}
        </ul>
    {:else}
        <p>No content modules available.</p>
    {/if}

    <!-- TODO: Add CSS for styling -->
    <style>
        .digest-module-selector {
            /* Container styles */
        }

        .module-list {
            /* List styles */
            list-style: none;
            padding: 0;
        }

        .module-item {
            /* List item styles */
            border: 1px solid #ccc;
            margin-bottom: 10px;
            padding: 10px;
        }

        button {
            /* Button styles */
            margin-top: 5px;
        }
    </style>
</div> 