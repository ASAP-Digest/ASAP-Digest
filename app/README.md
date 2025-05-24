# ASAP Digest - SvelteKit Frontend

This is the SvelteKit frontend application for ASAP Digest, a digital platform for AI-powered content curation and digest generation.

## Architecture

This SvelteKit application works in conjunction with a WordPress headless CMS backend, featuring:

- **Auto-Login Integration**: Seamless authentication between WordPress and SvelteKit using V6 server-to-server implementation
- **Better Auth**: Modern authentication system with session management
- **Progressive Web App**: Offline capabilities and installable experience
- **GridStack Layout**: Dynamic, responsive grid-based layout system
- **Real-time Updates**: Server-sent events for live data synchronization

## Environment Setup

### Required Environment Variables

Create a `.env` file in the app directory with:

```bash
# Auto-login configuration
BETTER_AUTH_SECRET=development-sync-secret-v6
WP_API_URL=https://asapdigest.local/wp-json

# Database configuration (for Better Auth)
DATABASE_URL=mysql://username:password@localhost:3306/database_name

# Additional configuration as needed
PUBLIC_SITE_URL=https://localhost:5173
```

### WordPress Integration

Ensure the WordPress backend is configured with:
- ASAP Digest Core plugin installed and activated
- Matching `BETTER_AUTH_SECRET` in wp-config.php
- Better Auth database tables created

## Development

Install dependencies:

```bash
npm install
```

Start the development server:

```bash
npm run dev

# or start the server and open the app in a new browser tab
npm run dev -- --open
```

## Auto-Login System

The application features a V6 server-to-server auto-login system that:

1. Checks for active WordPress sessions
2. Automatically creates/syncs Better Auth users
3. Maintains session persistence across page loads
4. Provides seamless authentication experience

### Debugging Auto-Login

Monitor the browser console for `[Layout V6]` prefixed messages to debug auto-login issues. Common troubleshooting steps:

1. Verify environment variables are set correctly
2. Check WordPress debug logs for `[ASAP S2S]` messages
3. Ensure WordPress user has active session tokens
4. Clear session storage flags if needed

For detailed troubleshooting, see: [Auto-Login Troubleshooting Guide](../md-docs/auto-login/troubleshooting.md)

## Building

To create a production version:

```bash
npm run build
```

Preview the production build:

```bash
npm run preview
```

## Key Features

### Layout System
- **GridStack Integration**: Dynamic grid-based layout for widgets and content
- **Responsive Design**: Mobile-first approach with collapsible sidebar
- **Theme System**: Multiple theme support with CSS custom properties

### Authentication
- **Better Auth Integration**: Modern authentication with session management
- **Auto-Login**: Seamless WordPress to SvelteKit authentication
- **Session Persistence**: Maintains login state across browser sessions

### Performance
- **Progressive Web App**: Offline support and installable experience
- **Lazy Loading**: Optimized image and component loading
- **Performance Monitoring**: Built-in performance tracking (dev mode)

## Documentation

- [Main Project README](../README.md)
- [Auto-Login Documentation](../md-docs/auto-login/)
- [Project Changelog](../CHANGELOG.md)

## Deployment

This application is designed to work with the WordPress backend. Ensure both components are deployed and configured with matching environment variables for production use.

> To deploy your app, you may need to install an [adapter](https://svelte.dev/docs/kit/adapters) for your target environment.
