<script>
    import { createDraftDigest, getLayoutTemplates } from '../../api/digest-builder-api';
    import { user } from '../../stores/authStore'; // Assuming an auth store provides user info
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';

    // Dummy layout data - TODO: Fetch real layout templates from backend API
    // const layoutTemplates = [
    //     { id: 'default-grid', name: 'Default Grid Layout' },
    //     { id: 'sidebar-layout', name: 'Sidebar Layout' },
    //     { id: 'card-layout', name: 'Card Layout' },
    // ];

    // Reactive variable to hold fetched layout templates
    let layoutTemplates = $state([]);
    let loadingLayouts = $state(true);

    let selectedLayoutId = null;
    let creatingDigest = false;
    let creationError = null;

    // Fetch layout templates when the component mounts
    onMount(async () => {
        loadingLayouts = true;
        layoutTemplates = await getLayoutTemplates();
        loadingLayouts = false;
    });

    async function handleLayoutSelect(layoutId) {
        selectedLayoutId = layoutId;
        // TODO: Add visual indication of selection
    }

    async function handleCreateDigest() {
        if (!selectedLayoutId) {
            creationError = 'Please select a layout template.';
            return;
        }

        if (!$user || !$user.id) {
            creationError = 'User not logged in.';
            // TODO: Redirect to login or handle appropriately
            return;
        }

        creatingDigest = true;
        creationError = null;

        const newDigest = await createDraftDigest($user.id, selectedLayoutId);

        creatingDigest = false;

        if (newDigest && newDigest.success) {
            console.log('Draft digest created:', newDigest);
            // TODO: Redirect user to the digest editing page with newDigest.digest_id
            goto(`/digest/${newDigest.digest_id}/edit`); // Example SvelteKit navigation
        } else {
            creationError = newDigest?.message || 'Failed to create draft digest.';
            console.error('Digest creation failed:', newDigest);
            // TODO: Implement better user feedback for errors
        }
    }
</script>

<div class="digest-layout-selector">
    <h2>Select a Digest Layout</h2>

    <div class="layout-options">
        {#each layoutTemplates as layout}
            <button
                class="layout-button {selectedLayoutId === layout.id ? 'selected' : ''}"
                on:click={() => handleLayoutSelect(layout.id)}
                disabled={creatingDigest}
            >
                {layout.name}
                <!-- TODO: Add layout preview image/icon -->
            </button>
        {/each}
    </div>

    {#if creationError}
        <p class="error-message">{creationError}</p>
    {/if}

    <button
        on:click={handleCreateDigest}
        disabled={!selectedLayoutId || creatingDigest}
        class="create-button"
    >
        {#if creatingDigest}
            Creating...
        {:else}
            Create Digest
        {/if}
    </button>

    <!-- TODO: Add CSS for styling -->
    <style>
        /* Placeholder styles */
        .digest-layout-selector {
            /* Add container styles */
        }

        .layout-options {
            /* Add layout options container styles */
        }

        .layout-button {
            /* Add button styles */
        }

        .layout-button.selected {
            /* Add styles for selected button */
        }

        .create-button {
            /* Add create button styles */
        }

        .error-message {
            /* Add error message styles */
        }
    </style>
</div> 