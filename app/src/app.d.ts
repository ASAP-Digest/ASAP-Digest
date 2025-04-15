// See https://svelte.dev/docs/kit/types#app.d.ts
// for information about these interfaces
declare global {
	interface User {
		id: string;
		betterAuthId: string;
		displayName?: string;
		email?: string;
		avatarUrl?: string;
		roles?: string[];
		syncStatus?: string;
		sessionToken?: string;
		updatedAt?: string; // Timestamp of last update from ba_users (ISO 8601 format)
	}

	/**
	 * @description Basic session structure, adjust based on better-auth needs
	 */
	interface Session {
		sessionId: string;
		userId: string;
		// Add other relevant session properties if known (e.g., expiresAt, active)
	}

	/**
	 * @description Defines the shape of expected public environment variables
	 */
	interface PublicEnv {
		PUBLIC_WP_API_URL: string;
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
