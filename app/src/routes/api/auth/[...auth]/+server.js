import { auth } from '$lib/server/auth';
import { toSvelteKitHandler } from 'better-auth';

// Create a handler that processes all Better Auth requests
const betterAuthHandle = toSvelteKitHandler(auth, {
    // Configure options for the handler
    options: {
        // Use the request URL as is
        useOriginalUrl: true,
        // Don't strip the /api/auth prefix
        stripPrefix: false
    }
});

// Export all HTTP methods to handle any type of request
export const GET = betterAuthHandle;
export const POST = betterAuthHandle;
export const PUT = betterAuthHandle;
export const DELETE = betterAuthHandle;
export const PATCH = betterAuthHandle;
export const OPTIONS = betterAuthHandle; 