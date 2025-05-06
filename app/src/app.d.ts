// See https://svelte.dev/docs/kit/types#app.d.ts
// for information about these interfaces
declare global {
	interface User {
		id: string;
		betterAuthId: string;
		displayName?: string;
		email?: string;
		username?: string;
		name?: string;
		avatarUrl?: string;
		roles?: string[];
		syncStatus?: string;
		sessionToken?: string;
		updatedAt?: string; // Timestamp of last update from ba_users (ISO 8601 format)
		metadata?: {
			wp_user_id?: number;
			roles?: string[];
			[key: string]: any;
		};
	}

	/**
	 * @description Basic session structure, adjust based on better-auth needs
	 */
	interface Session {
		id?: string;
		sessionId: string;
		userId: string;
		token?: string;
		expiresAt?: Date;
		createdAt?: Date;
		// Add other relevant session properties if known (e.g., expiresAt, active)
	}

	/**
	 * @description WordPress to SvelteKit user sync data structure
	 * Used in the /api/auth/wp-user-sync endpoint
	 */
	interface WpUserSync {
		wpUserId: number;
		email: string;
		username: string;
		name: string;
	}

	/**
	 * @description Response from the WordPress to SvelteKit user sync endpoint
	 */
	interface WpUserSyncResponse {
		success: boolean;
		userId?: string;
		error?: string;
	}

	/**
	 * @description Defines the shape of expected public environment variables
	 */
	interface PublicEnv {
		PUBLIC_WP_API_URL: string;
		PUBLIC_WP_GRAPHQL_URL: string; // GraphQL endpoint URL
		// Add other public env vars here
	}

	/**
	 * @description Defines the shape of expected private environment variables
	 * Accessed via import { VARIABLE } from '$env/dynamic/private';
	 */
	interface PrivateEnv {
		ASAP_SK_SYNC_SECRET: string;
		// Add other private env vars here (e.g., DB connection, Better Auth secret)
		BETTER_AUTH_SECRET: string;
		MYSQL_HOST: string;
		MYSQL_USER: string;
		MYSQL_PASSWORD: string;
		MYSQL_DATABASE: string;
	}

	namespace App {
		interface User {
			id: string;
			email: string;
			displayName?: string;
			avatarUrl?: string;
			roles?: string[];
			plan?: string;
			updatedAt?: string;
		}

		// Locals contain server-side data available to all requests
		interface Locals {
			user?: User;
			session?: {
				userId: string;
				token: string;
				expiresAt: string;
			};
		}

		// interface Error {}
		// interface Superforms {}
		// interface PageData {}
		// interface Platform {}
	}
}

/**
 * Svelte component type declaration
 * This eliminates the need for @ts-ignore comments on Svelte imports
 */
declare module '*.svelte' {
	import type { ComponentType, SvelteComponent } from 'svelte';
	
	const component: ComponentType<SvelteComponent>;
	export default component;
}

export {};
