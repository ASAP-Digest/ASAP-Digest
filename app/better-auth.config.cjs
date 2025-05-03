const { betterAuth } = require("better-auth");
// Load environment based on NODE_ENV
const path = process.env.NODE_ENV === 'production' ? '.env.production' : '.env.local';
require('dotenv').config({ path });

// Validate required environment variables
const requiredVars = {
    DB_HOST: process.env.DB_HOST || 'localhost',
    DB_PORT: parseInt(process.env.DB_PORT || '10018', 10),
    DB_USER: process.env.DB_USER || 'root',
    DB_PASS: process.env.DB_PASS || 'root',
    DB_NAME: process.env.DB_NAME || 'local',
    BETTER_AUTH_SECRET: process.env.BETTER_AUTH_SECRET || 'development-sync-secret-v6', // Provide fallback for builds
    BETTER_AUTH_URL: process.env.BETTER_AUTH_URL || 'https://localhost:5173'
};

// Validate environment variables
Object.entries(requiredVars).forEach(([key, value]) => {
    if (!value) {
        throw new Error(`${key} environment variable is required but not set`);
    }
});

const config = betterAuth({
    database: {
        type: "mysql",
        host: requiredVars.DB_HOST,
        port: requiredVars.DB_PORT,
        user: requiredVars.DB_USER,
        password: requiredVars.DB_PASS,
        database: requiredVars.DB_NAME
    },
    secret: requiredVars.BETTER_AUTH_SECRET,
    baseURL: requiredVars.BETTER_AUTH_URL,
    emailAndPassword: {
        enabled: true,
        verifyEmail: true,
        passwordResetEnabled: true
    },
    oauth: {
        providers: [
            {
                id: 'google',
                name: 'Google',
                type: 'oauth',
                clientId: process.env.GOOGLE_CLIENT_ID,
                clientSecret: process.env.GOOGLE_CLIENT_SECRET,
                enabled: !!process.env.GOOGLE_CLIENT_ID
            }
        ]
    },
    hooks: {
        onUserCreated: async (user) => {
            // Create corresponding WordPress user
            try {
                const response = await fetch(`${process.env.VITE_WORDPRESS_API_URL}/asap/v1/auth/create-wp-user`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Better-Auth-Timestamp': Date.now().toString(),
                        'X-Better-Auth-Signature': process.env.BETTER_AUTH_SECRET
                    },
                    body: JSON.stringify({
                        id: user.id,
                        email: user.email,
                        username: user.username || user.email.split('@')[0],
                        name: user.name || user.username || user.email.split('@')[0]
                    })
                });
                
                if (!response.ok) {
                    console.error('Failed to create WordPress user:', await response.text());
                }
            } catch (error) {
                console.error('Error creating WordPress user:', error);
            }
        }
    }
});

module.exports = config; 