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
		wp_user_id?: number; // WordPress user ID for API calls
		wpUserId?: number; // Normalized WordPress user ID (same as wp_user_id)
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

		/**
		 * Avatar source preference
		 */
		type AvatarPreference = 'synced' | 'profile' | 'gravatar' | 'default';

		/**
		 * User preferences object
		 */
		interface UserPreferences {
			avatarSource?: AvatarPreference;
			useGravatar?: boolean; // Legacy property
			display?: {
				darkMode?: boolean;
				theme?: string;
			};
			notifications?: {
				digest?: boolean;
				push?: boolean;
				email?: boolean;
			};
			tts?: {
				voice?: string;
				rate?: number;
				language?: string;
				autoPlay?: boolean;
			};
		}

		/**
		 * Subscription plan details
		 */
		interface UserPlan {
			name: string; // Free, Spark, Pulse, Bolt
			level?: string; // Numerical level (1-4)
			startDate?: Date;
			endDate?: Date;
			trialEndDate?: Date;
			isActive?: boolean;
			paymentStatus?: string;
		}

		/**
		 * User analytics and progress data
		 */
		interface UserStats {
			digestsRead?: number;
			widgetsExplored?: number;
			lastActive?: Date;
			usage?: {
				digestsRemaining?: number;
				searchesRemaining?: number;
			};
		}

		/**
		 * Enhanced user object
		 */
		interface User {
			id: string;
			email: string;
			displayName?: string;
			roles?: string[];
			avatarUrl?: string;
			gravatarUrl?: string;
			preferences?: UserPreferences;
			plan?: UserPlan | string; // Support both string (legacy) and object
			stats?: UserStats;
			metadata?: Record<string, any>;
			updatedAt?: string;
			wp_user_id?: number; // WordPress user ID for API calls
			wpUserId?: number; // Normalized WordPress user ID (same as wp_user_id)
		}
	}

	// Add global properties to Window interface
	interface Window {
		asapDigestSseActive?: boolean;
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
