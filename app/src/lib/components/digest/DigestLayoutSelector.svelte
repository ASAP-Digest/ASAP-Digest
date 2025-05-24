<script>
    import { createDraftDigest, getLayoutTemplates } from '../../api/digest-builder-api';
    import { useSession } from '$lib/auth-client';
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';
    import { getUserData } from '$lib/stores/user.js';

    // Dummy layout data - TODO: Fetch real layout templates from backend API
    // const layoutTemplates = [
    //     { id: 'default-grid', name: 'Default Grid Layout' },
    //     { id: 'sidebar-layout', name: 'Sidebar Layout' },
    //     { id: 'card-layout', name: 'Card Layout' },
    // ];

    // Get session data using Better Auth
    const { data: session } = useSession();

    // Get user data helper for cleaner access
    const userData = $derived(getUserData(session?.user));

    // Reactive variable to hold fetched layout templates
    let layoutTemplates = $state([]);
    let loadingLayouts = $state(true);

    let selectedLayoutId = $state(null);
    let creatingDigest = $state(false);
    let creationError = $state(null);

    // Fetch layout templates when the component mounts
    onMount(async () => {
        loadingLayouts = true;
        layoutTemplates = await getLayoutTemplates();
        loadingLayouts = false;
    });

    async function handleLayoutSelect(layoutId) {
        if (!userData.wpUserId) {
            console.error('No WordPress user ID available');
            return;
        }

        try {
            const newDigest = await createDraftDigest(userData.wpUserId, selectedLayoutId);
            if (newDigest.success) {
                // Handle success
                console.log('Draft digest created:', newDigest.data);
            }
        } catch (error) {
            console.error('Error creating draft digest:', error);
        }
    }

    async function handleCreateDigest() {
        if (!selectedLayoutId) {
            creationError = 'Please select a layout template.';
            return;
        }

        if (!userData.wpUserId) {
            creationError = 'User not logged in.';
            // TODO: Redirect to login or handle appropriately
            return;
        }

        creatingDigest = true;
        creationError = null;

        const newDigest = await createDraftDigest(userData.wpUserId, selectedLayoutId);

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
                onclick={() => handleLayoutSelect(layout.id)}
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
        onclick={handleCreateDigest}
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