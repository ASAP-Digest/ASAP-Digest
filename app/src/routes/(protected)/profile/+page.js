/**
 * Client-side load function for profile page
 * @param {{parent: Function, fetch: Function, depends: Function}} event - The load event
 * @returns {Promise<{user?: Object}>} Page data
 */
export async function load({ parent, fetch, depends }) {
  // Explicitly depend on user data
  depends('app:user');
  
  // Get data from parent (layout)
  const layoutData = await parent();
  
  // If user data is not yet loaded (from cookie), trigger a data invalidation
  if (!layoutData.user) {
    // Log the issue for debugging
    console.warn('[Profile] User data not available in layout data. Triggering invalidation.');
    // This will call the server to ensure fresh data
    const response = await fetch('/api/auth/session-check', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      if (data.success && data.user) {
        console.log('[Profile] Session check successful, received user data:', data.user.email);
        // Force a second data refresh to ensure UI components update
        depends('app:user');
        return {
          ...layoutData,
          user: data.user
        };
      }
    }
    
    // This will cause the page to re-render with fresh data
    depends('app:user');
  }
  
  // If retry on failure for better UX - check again after delay
  if (!layoutData.user) {
    // Set a flag to avoid retry loop
    const retryKey = 'profile_retry_count';
    const retryCount = Number(sessionStorage?.getItem?.(retryKey) || '0');
    
    if (retryCount < 3) {
      if (typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem(retryKey, String(retryCount + 1));
      }
      // Try one more time after a short delay
      await new Promise(resolve => setTimeout(resolve, 500));
      const response = await fetch('/api/auth/session-check');
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.user) {
          console.log('[Profile] Retry session check successful');
          // Force data refresh
          depends('app:user');
          return {
            ...layoutData,
            user: data.user
          };
        }
      }
    } else if (typeof sessionStorage !== 'undefined') {
      // Reset retry count
      sessionStorage.removeItem(retryKey);
    }
  }
  
  return {
    // Return layout data plus any profile-specific data
    ...layoutData
  };
} 