/**
 * Loads initial data for the root layout.
 * @param {object} event The load event object.
 * @param {App.Locals} event.locals Contains locally scoped data, including the user object if authenticated.
 * @returns {Promise<{ user: User | undefined }>} Layout data including the user.
 */
export async function load({ locals }) {
    // locals should now be correctly typed via the @param annotation above.
    console.log('[+layout.server.js] load function executing. Locals:', locals);
    const user = locals.user;
    console.log('[+layout.server.js] User from locals:', user);
    return {
        user: user
    };
} 