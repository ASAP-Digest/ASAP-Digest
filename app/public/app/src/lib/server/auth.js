// Create MySQL connection pool with proven settings from PE-CTXT
const pool = mysql.createPool({
    host: DB_HOST || 'localhost',
    port: parseInt(DB_PORT || '10018', 10),
    user: DB_USER || 'root',
    password: DB_PASS || 'root',
    database: DB_NAME || 'local',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 0
});

// Better Auth configuration
export const auth = betterAuth({
    secret: BETTER_AUTH_SECRET,
    baseURL: BETTER_AUTH_URL || 'http://localhost:5173',
    database: {
        type: 'mysql',
        dialect: new MysqlDialect({ pool })
    },
    emailAndPassword: {
        enabled: true,
        autoSignIn: true
    },
    cookies: {
        sessionToken: {
            name: 'asap_session',
            options: {
                httpOnly: true,
                secure: process.env.NODE_ENV === 'production',
                sameSite: 'lax'
            }
        }
    },
    // Keep existing onUserCreated and onSessionCreated hooks
    // ... rest of existing code ...
}); 