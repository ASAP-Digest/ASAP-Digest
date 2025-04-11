// @ts-nocheck
// src/lib/server/syncBroadcaster.js

/**
 * Manages Server-Sent Events (SSE) connections for broadcasting user sync updates.
 * @created 04.10.25 | 01:55 PM PDT
 */

/** @type {Map<string, ReadableStreamDefaultController>} */
const controllers = new Map(); // Stores controllers keyed by a unique identifier (e.g., session ID or user ID if appropriate)

/**
 * Creates a new SSE stream for a client connection.
 * @param {string} clientId - A unique identifier for the client connection.
 * @returns {ReadableStream} A ReadableStream for SSE.
 */
export function createEventStream(clientId) {
    console.log(`[SyncBroadcaster] Creating new event stream for client: ${clientId}`);
    const stream = new ReadableStream({
        start(controller) {
            console.log(`[SyncBroadcaster] Controller started for client: ${clientId}`);
            controllers.set(clientId, controller);
            // Send a confirmation message
            controller.enqueue(`data: ${JSON.stringify({ type: 'connection-ready' })}\n\n`);
        },
        cancel() {
            console.log(`[SyncBroadcaster] Stream cancelled for client: ${clientId}. Removing controller.`);
            controllers.delete(clientId);
        },
    });

    // Keep-alive mechanism (send a comment every 20 seconds)
    const keepAliveInterval = setInterval(() => {
        console.debug(`[SyncBroadcaster] Keep-alive check for client: ${clientId}`);
        if (controllers.has(clientId)) {
            try {
                 // Send a comment line (ignored by EventSource listeners)
                 controllers.get(clientId)?.enqueue(': keepalive\n\n');
            } catch (error) {
                 console.error(`[SyncBroadcaster] Error sending keepalive to client ${clientId}. Removing controller. Error:`, error);
                 controllers.delete(clientId); // Clean up if error occurs
                 clearInterval(keepAliveInterval);
            }
        } else {
            clearInterval(keepAliveInterval); // Stop if client is no longer tracked
        }
    }, 20000); // 20 seconds

    return stream;
}

/**
 * Broadcasts a user synchronization update to all connected clients.
 * 
 * Note: In a real application, you might want to target specific users/sessions
 * instead of broadcasting to everyone, depending on security and privacy needs.
 * For this scenario, we broadcast the updated user ID to all listeners.
 * Frontend clients will need to check if the update pertains to them.
 * 
 * @param {string} userId - The ID of the user whose data was updated.
 * @param {string | null} [updatedAt] - Optional ISO timestamp of the update.
 */
export function broadcastSyncUpdate(userId, updatedAt = null) {
    if (!userId) {
        console.warn('[SyncBroadcaster] Attempted to broadcast update without a userId.');
        return;
    }
    
    // Construct payload, include updatedAt if provided
    const payload = { 
        type: 'user-update', 
        userId: userId 
    };
    if (updatedAt) {
        // @ts-ignore
        payload.updatedAt = updatedAt;
    }

    const message = JSON.stringify(payload);
    console.log(`[SyncBroadcaster] Broadcasting message: ${message}`);
    
    controllers.forEach((controller, clientId) => {
        try {
            console.log(`[SyncBroadcaster] Sending update for user ${userId} to client ${clientId}`);
            controller.enqueue(`data: ${message}\n\n`);
        } catch (error) {
            console.error(`[SyncBroadcaster] Failed to send message to client ${clientId}. Error:`, error);
            // Consider removing the controller here if sending fails consistently
            // controllers.delete(clientId);
        }
    });
}

// Basic cleanup on server shutdown (useful for development)
process.on('exit', () => {
    console.log('[SyncBroadcaster] Server shutting down, closing all controllers.');
    controllers.forEach(controller => {
        try {
            controller.close();
        } catch (e) { /* ignore */ }
    });
    controllers.clear();
});
