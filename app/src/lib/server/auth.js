import { betterAuth } from "better-auth";
import { toSvelteKitHandler } from "better-auth/svelte-kit";
import mysql from 'mysql2/promise';

// Configure Better Auth with MySQL
export const auth = betterAuth({
    secret: process.env.BETTER_AUTH_SECRET || 'development-secret-key',
    baseURL: process.env.BETTER_AUTH_URL || 'https://asapdigest.local',
    database: {
        type: 'mysql',
        instance: mysql.createPool({
            host: 'localhost',
            port: 10040,
            user: 'root',
            password: 'root',
            database: 'local',
            charset: 'utf8'
        })
    },
    emailAndPassword: {
        enabled: true,
        requireEmailVerification: false
    }
});

// Export handler for SvelteKit endpoint
export const handler = toSvelteKitHandler(auth); 