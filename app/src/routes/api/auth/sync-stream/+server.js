import { createEventStream } from '$lib/server/syncBroadcaster';
import { randomUUID } from 'node:crypto';

/** @type {import('./$types').RequestHandler} */
export function GET({ request }) {
    // Generate a unique ID for this client connection
    const clientId = randomUUID(); 
    console.log(`[Sync Stream API] Received GET request. Assigning client ID: ${clientId}`);

    const stream = createEventStream(clientId);

    return new Response(stream, {
        headers: {
            'Content-Type': 'text/event-stream',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
        },
    });
}

// Placeholder for potentially handling POST requests if needed in the future
/** @type {import('./$types').RequestHandler} */
export async function POST({ request }) {
     console.warn('[Sync Stream API] Received unexpected POST request.');
     return new Response(JSON.stringify({ error: 'Method not allowed' }), { status: 405 });
 }
