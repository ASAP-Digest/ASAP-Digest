# User Data UI Integration Guide

## Overview

This guide documents best practices for integrating user data into the ASAP Digest UI, specifically focusing on:

1. Centralized user data management using Svelte stores
2. Conditional rendering based on user roles
3. Type-safe handling of user objects
4. Proper error handling for missing or undefined user properties

## 1. Centralized User State Management

### 1.1 User Store Implementation

We use a centralized Svelte store in `$lib/stores/user.js` to manage user data across the application:

```javascript
import { writable } from 'svelte/store';

/**
 * @typedef {Object} User
 * @property {string} id
 * @property {string=} displayName
 * @property {string[]=} roles
 * @property {string} [avatarUrl]
 * @property {string} [plan]
 * @property {string} [email]
 */

/** @type {import('svelte/store').Writable<User|null>} */
export const user = writable(null);
```

### 1.2 User Data Normalization 

All user data should be normalized before setting it in the store. This is done in `+layout.js`:

```javascript
function normalizeUser(inputUser) {
  // Create an empty user object with default values
  const emptyUser = {
    id: '',
    email: '',
    displayName: '',
    avatarUrl: '',
    roles: [],
    metadata: {},
    plan: 'Free',
    updatedAt: new Date().toISOString()
  };
  
  if (!inputUser) return emptyUser;
  
  // Standardize roles from multiple sources
  const metadataRoles = inputUser.metadata?.roles || [];
  const directRoles = inputUser.roles || inputUser.ROLES || [];
  const combinedRoles = [...new Set([...directRoles, ...metadataRoles])];
  
  // Return normalized user with proper defaults
  return {
    id: inputUser.id || inputUser.ID || '',
    email: inputUser.email || inputUser.EMAIL || '',
    displayName: inputUser.displayName || inputUser.DISPLAYNAME || '',
    avatarUrl: inputUser.avatarUrl || inputUser.AVATARURL || '',
    roles: combinedRoles,
    metadata: inputUser.metadata || {},
    plan: inputUser.plan || 'Free',
    updatedAt: inputUser.updatedAt || new Date().toISOString()
  };
}
```

## 2. Component Integration with User Store

### 2.1 Reading User Data in Components

Use `subscribe` in components to get user data:

```javascript
import { onDestroy } from 'svelte';
import { user as userStore } from '$lib/stores/user.js';

// Local user variable with proper subscription cleanup
let userValue = null;
const unsubscribe = userStore.subscribe(value => { userValue = value; });
onDestroy(unsubscribe);
```

### 2.2 Optional Chaining and Default Values

Always use optional chaining and provide default values when accessing user properties:

```svelte
<!-- In Svelte template -->
<div class="username">{userValue?.displayName || 'Guest'}</div>
<div class="plan">{userValue?.plan || 'Free Plan'}</div>

<!-- For avatar images -->
<img 
  src={userValue?.avatarUrl || '/default-avatar.svg'} 
  alt={userValue?.displayName || 'User'} 
/>
```

### 2.3 Conditional Rendering Based on Role

```svelte
{#if userValue?.roles && userValue.roles.includes('administrator')}
  <!-- Admin-only content -->
  <div class="admin-panel">
    <h2>Admin Controls</h2>
    <!-- Admin controls content -->
  </div>
{/if}
```

## 3. API Integration

### 3.1 Including User Roles in API Responses

When creating API endpoints that return user data, always include the `roles` array:

```javascript
// Example API endpoint response
return json({
  success: true,
  user: {
    id: user.id,
    email: user.email,
    displayName: user.displayName,
    avatarUrl: user.avatarUrl,
    roles: user.roles || [] // Always include roles, even if empty
  }
});
```

### 3.2 WordPress User Synchronization

When syncing WordPress users, ensure roles are properly included:

```php
// In PHP, ensure roles are included in the user data
$user_data = array(
  'id' => $user->ID,
  'email' => $user->user_email,
  'displayName' => $user->display_name,
  'avatarUrl' => get_avatar_url($user->ID),
  'roles' => $user->roles // Include roles from WordPress
);
```

## 4. Debugging User Data Issues

### 4.1 Logging User Data

Add temporary debug logging to track user data flow:

```javascript
// In component
$effect(() => {
  console.log('[DEBUG] User data:', userValue);
  console.log('[DEBUG] User roles:', userValue?.roles);
  console.log('[DEBUG] Is admin:', userValue?.roles?.includes('administrator'));
});

// In API response handlers
console.log('[DEBUG] API user response:', response.user);
```

### 4.2 Common User Data Issues and Solutions

| Issue | Possible Cause | Solution |
|-------|---------------|----------|
| Admin menu not visible | Roles array missing or empty | Ensure roles are included in API responses and properly normalized |
| Undefined user properties in UI | Missing optional chaining | Use optional chaining (`?.`) and provide default values |
| Hydration warnings for avatar | Server/client mismatch | Use appropriate fallbacks and error handlers |
| Type errors with user object | Improper typing | Use proper JSDoc typedefs and ensure normalization follows types |

## 5. Icon Component Integration

Always use the Icon component pattern for icons:

```svelte
<Icon icon={IconName} class="h-5 w-5" />
```

Instead of directly using icon components:

```svelte
<!-- Incorrect -->
<IconName class="h-5 w-5" />

<!-- Correct -->
<Icon icon={IconName} class="h-5 w-5" />
```

## 6. Route Organization

### 6.1 Protected Routes Structure

Protected routes should be organized in a logical hierarchy:

```
(protected)/
├── settings/
│   ├── +page.svelte       # Settings hub
│   ├── account/
│   │   └── +page.svelte   # Account settings
│   ├── notifications/
│   │   └── +page.svelte   # Notification settings
│   └── security/
│       └── +page.svelte   # Security settings
└── ...
```

### 6.2 Settings Hub Pattern

Create a central settings hub that links to specialized settings pages:

```svelte
<!-- settings/+page.svelte -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  {#each settingsOptions as option}
    <a href={option.href} class="block group">
      <Card class="h-full transition-all duration-200 hover:shadow-md">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Icon icon={option.icon} class="h-5 w-5 text-primary" />
            {option.title}
          </CardTitle>
          <CardDescription>{option.description}</CardDescription>
        </CardHeader>
      </Card>
    </a>
  {/each}
</div>
```

## 7. Consistent Navigation and Error Handling

### 7.1 Back Navigation

Always include a way back to parent pages:

```svelte
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold">Account Settings</h1>
  <a href="/settings" class="text-sm text-blue-600 hover:underline">← Back to Settings</a>
</div>
```

### 7.2 Form Submission Feedback

Provide clear feedback for form submissions:

```svelte
<Button type="submit" disabled={saving}>
  {saving ? 'Saving...' : 'Save Settings'}
</Button>

{#if error}
  <div class="p-3 bg-red-100 text-red-600 rounded-md flex items-center">
    <Icon icon={AlertCircle} class="h-5 w-5 mr-1" />
    {error}
  </div>
{/if}

{#if success}
  <div class="p-3 bg-green-100 text-green-600 rounded-md">
    Settings saved successfully!
  </div>
{/if}
```

## 8. Future Improvements

- Implement reactive derived fields for computed user properties
- Add role-based route guards at the layout level
- Develop unified user preferences system
- Create a user permission system based on roles
- Implement user session management improvements

## 9. Common Pitfalls to Avoid

1. **Direct Mutation**: Never directly mutate the user store; use the `set` method
2. **Missing Cleanup**: Always use `onDestroy` to unsubscribe from stores
3. **Premature Access**: Don't assume user data exists immediately
4. **No Default Values**: Always provide fallbacks for optional properties
5. **Ignored Role Structure**: Ensure you understand the role structure from backend
6. **Improper Icon Usage**: Always use the Icon component pattern
7. **Missing Route Organization**: Maintain consistent route organization

By following these guidelines, we can ensure consistent user data handling throughout the application and avoid common issues with conditional rendering based on user roles. 