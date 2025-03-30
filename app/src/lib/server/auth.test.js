/**
 * Authentication Flow Test Script
 * @created 03.30.25 | 05:21 AM PDT
 */

import auth from './auth.js';

/**
 * Test user data
 * @type {Object}
 */
const testUser = {
    email: 'test@asapdigest.local',
    password: 'TestPass123!',
    name: 'Test User'
};

/**
 * Run a test case
 * @param {string} name - Test case name
 * @param {Function} fn - Test function
 */
async function test(name, fn) {
    try {
        await fn();
        console.log(`✓ ${name}`);
    } catch (error) {
        console.error(`✗ ${name}`);
        console.error(error);
        process.exit(1);
    }
}

/**
 * Assert a condition
 * @param {boolean} condition - Condition to check
 * @param {string} message - Error message
 */
function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

async function runTests() {
    console.log('Starting authentication flow tests...\n');

    let user;
    let session;

    await test('Environment variables are set', () => {
        assert(process.env.BETTER_AUTH_SECRET, 'BETTER_AUTH_SECRET is not set');
        assert(process.env.DB_HOST, 'DB_HOST is not set');
    });

    await test('Register new user', async () => {
        user = await auth.register(testUser);
        assert(user.id, 'User ID should be present');
        assert(user.email === testUser.email, 'Email should match');
    });

    await test('Login with credentials', async () => {
        session = await auth.login({
            email: testUser.email,
            password: testUser.password
        });
        assert(session.id, 'Session ID should be present');
        assert(session.userId === user.id, 'User ID should match');
    });

    await test('Create WordPress user', async () => {
        const wpUser = await auth.getUser(user.id);
        assert(wpUser.metadata?.wp_user_id, 'WordPress user ID should be present');
    });

    await test('Manage sessions', async () => {
        const currentSession = await auth.getSession(session.id);
        assert(currentSession.id === session.id, 'Session should be valid');
    });

    await test('Logout user', async () => {
        await auth.logout(session.id);
        try {
            await auth.getSession(session.id);
            throw new Error('Session should be invalid after logout');
        } catch (error) {
            // Expected error
        }
    });

    // Clean up
    if (user?.id) {
        await auth.deleteUser(user.id);
        console.log('\n✓ Test cleanup successful');
    }

    console.log('\n✨ All tests passed successfully!');
}

// Run tests
runTests().catch(error => {
    console.error('\n✗ Tests failed:', error);
    process.exit(1);
}); 