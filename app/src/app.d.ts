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

	namespace App {
		// interface Error {}
		interface Locals {
			user?: User;
			session?: Session;
		}
		// interface PageData {}
		// interface PageState {}
		// interface Platform {}
	}
}

export {};
