<script>
    import { error } from '@sveltejs/kit';
    // @ts-ignore - Svelte component import
    import DigestLayoutSelector from '$lib/components/digest/DigestLayoutSelector.svelte';
    // @ts-ignore - Svelte component import
    import DigestModuleSelector from '$lib/components/digest/DigestModuleSelector.svelte';
    // @ts-ignore - Svelte component import
    import GenericModule from '$lib/components/digest/modules/GenericModule.svelte';
    import { getDigest, updateModulePlacement, removeModulePlacement, addModuleToDigest } from '$lib/api/digest-builder-api';
    import { tick } from 'svelte'; // Import tick for potential future use if needed
    import { onMount, onDestroy } from 'svelte'; // Import lifecycle functions

    /**
     * @typedef {import('@sveltejs/kit').PageLoadEvent} PageLoadEvent
     */

    /**
     * @param {PageLoadEvent} event
     */
    export async function load(event) {
        const digestId = event.params.digest_id;

        if (!digestId) {
            // This route should only be accessed with a digest ID
            // TODO: Redirect to digest creation page or handle appropriately
            throw error(404, 'Digest ID missing');
        }

        // Fetch the digest data
        const digestData = await getDigest(Number(digestId));

        if (!digestData) {
            // TODO: Handle case where digest is not found or API error
             throw error(404, 'Digest not found or could not be loaded');
        }

        return { digest: digestData };
    }

    // Component state for digest data
    const { data } = $props(); // Data loaded from the load function

    // Reactive variable for digest data
    let currentDigest = $state(data.digest);

    let gridStackInstance = null; // Variable to hold the GridStack instance

    // Initialize GridStack when the component mounts
    onMount(() => {
        // TODO: Configure grid options based on the selected layout template
        gridStackInstance = GridStack.init({
            // options here
            cellHeight: 50, // Example cell height
            margin: 10,
            alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            resizable: {
                handles: 'e,se,s,sw,w'
            }
        });

        // Load existing modules into GridStack
        loadGridStackItems(currentDigest.module_placements);

        // Attach event listeners for drag/resize changes
        gridStackInstance.on('change', handleGridChange);
        gridStackInstance.on('added', handleGridChange); // Handle items added via drag-in from selector
        gridStackInstance.on('removed', handleGridChange);
    });

    // Destroy GridStack instance when component is destroyed
    onDestroy(() => {
        if (gridStackInstance) {
            gridStackInstance.off('change', handleGridChange);
            gridStackInstance.off('added', handleGridChange);
            gridStackInstance.off('removed', handleGridChange);
            gridStackInstance.destroy();
        }
    });

    // Helper function to load items into GridStack
    function loadGridStackItems(placements) {
        if (!gridStackInstance || !placements) return;

        const items = placements.map(p => ({
            x: p.grid_x,
            y: p.grid_y,
            w: p.grid_width,
            h: p.grid_height,
            id: p.placement_id, // Use placement ID as item ID
            content: `<div data-placement-id="${p.placement_id}"></div>` // Placeholder, content will be rendered by Svelte
        }));

        gridStackInstance.load(items, true); // true for update
    }

    // Function to handle changes in GridStack (drag, resize, add, remove)
    async function handleGridChange(event, items) {
        if (!gridStackInstance || !items) return;

        // Process changed items (drag/resize)
        for (const item of items) {
            console.log('Grid item changed:', item);
            // Find the corresponding placement data to get the module CPT ID if needed
            const placement = currentDigest.module_placements.find(p => p.placement_id === Number(item.id));

            if (!placement) {
                console.warn('Changed grid item not found in currentDigest.module_placements:', item);
                continue;
            }

            // Prepare updated data
            const updatedData = {};
            let needsUpdate = false;
            if (placement.grid_x !== item.x) { updatedData.grid_x = item.x; needsUpdate = true; }
            if (placement.grid_y !== item.y) { updatedData.grid_y = item.y; needsUpdate = true; }
            if (placement.grid_width !== item.w) { updatedData.grid_width = item.w; needsUpdate = true; }
            if (placement.grid_height !== item.h) { updatedData.grid_height = item.h; needsUpdate = true; }
            // TODO: Handle order_in_grid if needed for z-index or layering

            if (needsUpdate) {
                console.log(`Updating placement ${item.id} with data:`, updatedData);
                // Call the API to update placement
                const result = await updateModulePlacement(currentDigest.id, Number(item.id), updatedData);

                if (result && result.success) {
                    console.log('Placement updated successfully in DB.');
                    // Update local reactive state after successful DB update
                    // Find and update the placement in currentDigest.module_placements
                    const localPlacement = currentDigest.module_placements.find(p => p.placement_id === Number(item.id));
                    if (localPlacement) {
                        if ('grid_x' in updatedData) localPlacement.grid_x = updatedData.grid_x;
                        if ('grid_y' in updatedData) localPlacement.grid_y = updatedData.grid_y;
                        if ('grid_width' in updatedData) localPlacement.grid_width = updatedData.grid_width;
                        if ('grid_height' in updatedData) localPlacement.grid_height = updatedData.grid_height;
                        // Trigger Svelte reactivity manually if needed, or refetch
                        // currentDigest = { ...currentDigest }; // Simple way to trigger reactivity
                    }
                    // A full refetch might be safer to ensure local state matches DB including order
                    const updatedDigestData = await getDigest(currentDigest.id);
                    if (updatedDigestData) {
                        currentDigest = updatedDigestData; // Update the reactive state
                    }

                } else {
                    console.error('Failed to update placement in DB:', result);
                    // TODO: Show user error feedback and potentially revert item position
                }
            }
        }
        // TODO: Handle 'added' and 'removed' events explicitly if needed
    }

    // Function to handle adding a selected module to the digest
    async function handleAddModule(event) {
        const selectedModule = event.detail.module; // Module object from DigestModuleSelector
        const digestId = currentDigest.id;

        if (!digestId) {
            console.error('Cannot add module: Digest ID is missing.');
            // TODO: Show user feedback
            return;
        }

        if (!selectedModule || !selectedModule.id) {
             console.error('Cannot add module: Selected module data is incomplete.', selectedModule);
             // TODO: Show user feedback
             return;
        }

        // Call the API to add the module
        const result = await addModuleToDigest(digestId, selectedModule.id);

        if (result && result.success) {
            console.log('Module added successfully:', result);
            // After adding to DB and refetching, the new placement should be in currentDigest.module_placements
            // GridStack.js load() method should pick it up and render if called again.
            // Re-fetch the digest data to update the UI and load into grid
            const updatedDigestData = await getDigest(digestId);
            if (updatedDigestData) {
                 currentDigest = updatedDigestData; // Update the reactive state
                // After updating reactive state, GridStack needs to be told about the new item.
                // This might involve manually adding the item to GridStack using addItem() or calling load() again.
                // Let's try re-loading all items for simplicity for now.
                loadGridStackItems(currentDigest.module_placements);
            }
            // TODO: Add success message feedback
        } else {
            console.error('Failed to add module:', result);
            // TODO: Add error feedback
        }
    }

    // Function to handle removing a module placement from the digest
    async function handleRemoveModule(placementId) {
        const digestId = currentDigest.id;

        if (!digestId) {
            console.error('Cannot remove module: Digest ID is missing.');
            // TODO: Show user feedback
            return;
        }

        if (!placementId) {
             console.error('Cannot remove module: Placement ID is missing.');
             // TODO: Show user feedback
             return;
        }

        // Call the API to remove the module placement
        const result = await removeModulePlacement(digestId, placementId);

        if (result && result.success) {
            console.log('Module placement removed successfully:', result);
            // After removing from DB and refetching, the placement should NOT be in currentDigest.module_placements
            // GridStack needs to be told about the removed item.
            // Manually remove item from GridStack by its ID
            if (gridStackInstance) {
                // Find the grid item by placementId
                const gridItem = gridStackInstance.engine.nodes.find(node => Number(node.id) === placementId);
                if (gridItem) {
                    gridStackInstance.removeWidget(gridItem.el);
                }
            }
            // Re-fetch the digest data to update the UI (removes from Svelte state)
            const updatedDigestData = await getDigest(digestId);
            if (updatedDigestData) {
                 currentDigest = updatedDigestData; // Update the reactive state
            }
            // TODO: Add success message feedback
        } else {
            console.error('Failed to remove module placement:', result);
            // TODO: Add error feedback
        }
    }

    // Function to handle updating a module placement
    async function handleUpdatePlacement(placementId, updatedData) {
        const digestId = currentDigest.id;

        if (!digestId) {
            console.error('Cannot update module placement: Digest ID is missing.');
            // TODO: Show user feedback
            return;
        }

        if (!placementId) {
             console.error('Cannot update module placement: Placement ID is missing.');
             // TODO: Show user feedback
             return;
        }

        if (!updatedData || Object.keys(updatedData).length === 0) {
             console.warn('No update data provided for placement:', placementId);
             return; // Nothing to update
        }

        // Call the API to update the module placement
        const result = await updateModulePlacement(digestId, placementId, updatedData);

        if (result && result.success) {
            console.log('Module placement updated successfully:', result);
            // After updating in DB and refetching, currentDigest is already updated
            // No need to manually update GridStack item position/size here as drag/resize already did it locally.
            // The full refetch and loadGridStackItems should keep GridStack in sync.
            const updatedDigestData = await getDigest(currentDigest.id);
            if (updatedDigestData) {
                 currentDigest = updatedDigestData; // Update the reactive state
                 loadGridStackItems(currentDigest.module_placements); // Re-load all items to sync
            }
            // TODO: Add success message feedback
        } else {
            console.error('Failed to update module placement:', result);
            // TODO: Add error feedback and potentially revert item position in GridStack
            // Revert GridStack item position on failure if needed
            const localPlacement = currentDigest.module_placements.find(p => p.placement_id === placementId);
            if (localPlacement && gridStackInstance) {
                const gridItem = gridStackInstance.engine.nodes.find(node => Number(node.id) === placementId);
                if (gridItem) {
                    gridStackInstance.move(gridItem.el, localPlacement.grid_x, localPlacement.grid_y);
                    gridStackInstance.resize(gridItem.el, localPlacement.grid_width, localPlacement.grid_height);
                }
            }
        }
    }

    // TODO: Implement drag and drop or other UI logic for module placement

</script>

<div class="digest-editor-page">
    <h1>Editing Digest {currentDigest.id}</h1>

    <!-- GridStack container -->
    <div class="grid-stack">
        <!-- GridStack items will be added here by the library -->
        <!-- Svelte will render component content inside these items -->
        {#if currentDigest.module_placements && currentDigest.module_placements.length > 0}
             {#each currentDigest.module_placements as placement (placement.placement_id)}
                <!-- Each grid item wrapper needs gridstack-item class and data attributes -->
                <!-- The GenericModule will be rendered inside the content div -->
                <div class="grid-stack-item" data-gs-id="{placement.placement_id}" data-gs-x="{placement.grid_x}" data-gs-y="{placement.grid_y}" data-gs-w="{placement.grid_width}" data-gs-h="{placement.grid_height}">
                     <div class="grid-stack-item-content">
                         <!-- Render the actual module component -->
                         <GenericModule moduleData={placement} />
                         <!-- Add controls for removing module -->
                         <button class="remove-module-button" onclick={() => handleRemoveModule(placement.placement_id)}>Remove</button>
                         <!-- TODO: Add a move/drag handle if needed -->
                         <!-- <div class="grid-stack-item-content-handle"></div> -->
                     </div>
                </div>
             {/each}
         {/if}
    </div>

    <div class="module-selection-area">
        <DigestModuleSelector on:moduleSelected={handleAddModule} />
    </div>

    <!-- TODO: Add CSS for styling -->
    <style>
        /* Placeholder styles */
        .digest-editor-page {
            /* Page container styles */
        }

        .grid-stack {
            /* GridStack container styles */
             background: #f7f7f7; /* Example background */
             margin-bottom: 20px;
        }

        /* Style the content area within GridStack items */
        .grid-stack-item-content {
            /* Ensure content fills the item */
             padding: 10px; /* Add some padding */
             background-color: white; /* Example background */
             border: 1px solid #ccc;
             overflow: auto; /* Handle overflow */
        }

        /* Style for the remove button within grid items */
        .remove-module-button {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10; /* Ensure button is above module content */
            background-color: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }

        .module-selection-area {
            /* Module selector area */
        }
    </style>
</div> 