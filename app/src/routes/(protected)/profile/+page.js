/**
 * Client-side load function for profile page
 * @param {{parent: Function, fetch: Function, depends: Function}} event - The load event
 * @returns {Promise<{user?: Object}>} Page data
 */
export async function load({ parent, depends }) {
  // Explicitly depend on user data
  depends('app:user');
  
  // Get data from parent (layout) - this should already have user data
  const layoutData = await parent();
  
  console.log('[Profile] Load function - user data available:', !!layoutData.user);
  
  // Simply return the layout data - the protected layout should handle auth
  return {
    ...layoutData
  };
} 