# User Data Integration Cheat Sheet

## User Store Setup
```javascript
// In $lib/stores/user.js
import { writable } from 'svelte/store';
export const user = writable(null);
```

## Component Integration

### Access User Data (Svelte 5 Runes)
```javascript
import { user as userStore } from '$lib/stores/user.js';
import { onDestroy } from 'svelte';

// Pattern 1: Subscribe with onDestroy cleanup
let userValue = null;
const unsubscribe = userStore.subscribe(value => { userValue = value; });
onDestroy(unsubscribe);

// Pattern 2: For debugging user changes
userStore.subscribe(value => { 
  console.log('[DEBUG] User updated:', value);
});
```

### Conditional Rendering
```svelte
<!-- Always use optional chaining -->
{#if userValue?.roles?.includes('administrator')}
  <AdminPanel />
{/if}

<!-- For nested properties -->
{#if userValue?.metadata?.preferences?.emailNotifications}
  <NotificationBadge />
{/if}
```

### Property Access with Defaults
```svelte
<div class="username">{userValue?.displayName || 'Guest'}</div>
<div class="email">{userValue?.email || 'No email provided'}</div>
<div class="plan">{userValue?.plan || 'Free Plan'}</div>
<img src={userValue?.avatarUrl || '/default-avatar.svg'} alt="User avatar" />
```

## API Integration

### Setting User Data in Store
```javascript
// In +layout.js or auth handler
import { user } from '$lib/stores/user.js';

function setUserData(userData) {
  // Never set raw data - always normalize
  const normalizedUser = normalizeUser(userData);
  user.set(normalizedUser);
}
```

### User Normalization Pattern
```javascript
function normalizeUser(inputUser) {
  if (!inputUser) return {
    id: '',
    email: '',
    displayName: '',
    avatarUrl: '',
    roles: [],
    metadata: {},
    plan: 'Free',
    updatedAt: new Date().toISOString()
  };
  
  return {
    id: inputUser.id || inputUser.ID || '',
    email: inputUser.email || inputUser.EMAIL || '',
    displayName: inputUser.displayName || inputUser.DISPLAYNAME || '',
    avatarUrl: inputUser.avatarUrl || inputUser.AVATARURL || '',
    roles: inputUser.roles || inputUser.ROLES || [],
    metadata: inputUser.metadata || {},
    plan: inputUser.plan || 'Free',
    updatedAt: inputUser.updatedAt || new Date().toISOString()
  };
}
```

### API Response Pattern
```javascript
// In API endpoint
return json({
  success: true,
  user: {
    id: session.userId,
    email: userData.email,
    displayName: userData.displayName,
    avatarUrl: userData.avatarUrl,
    roles: userData.roles || [] // Always include, even if empty
  }
});
```

## Icon Usage Pattern
```svelte
<!-- Always use Icon component, never direct icon components -->
<Icon icon={Bell} class="h-5 w-5" />

<!-- Incorrect: -->
<Bell class="h-5 w-5" />
```

## Common Tasks

### Clear User on Logout
```javascript
// In logout handler
import { user } from '$lib/stores/user.js';
user.set(null);
```

### Update User Field
```javascript
// To update a single field
userStore.update(current => {
  if (!current) return current;
  return {
    ...current,
    displayName: newDisplayName
  };
});
```

### Role-Based UI Checks
```javascript
// Helper function in component
function hasRole(role) {
  return userValue?.roles?.includes(role) || false;
}

// In template
{#if hasRole('administrator')}
  <AdminControls />
{/if}
```

## Common Errors & Solutions

| Error | Solution |
|-------|----------|
| `undefined is not an object (evaluating 'user.roles')` | Use optional chaining: `user?.roles` |
| Admin menu not showing | Check API response includes roles array |
| Hydration mismatch errors | Ensure server and client render same fallbacks |
| Type errors | Follow type-definition-management-protocol |

## Default Values Reference

| Property | Default |
|----------|---------|
| displayName | `'Guest'` or `''` |
| email | `''` |
| avatarUrl | `'/default-avatar.svg'` or `''` |
| roles | `[]` |
| plan | `'Free'` |
| metadata | `{}` |

## Best Practices

1. Always use optional chaining (`?.`)
2. Always provide default values
3. Always normalize user data before storing
4. Clean up store subscriptions with `onDestroy`
5. Use consistent role checking patterns 