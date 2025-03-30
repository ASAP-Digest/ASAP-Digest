<?php
/**
 * Test Session Synchronization
 * 
 * @package ASAPDigest_Core
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TestSessionSync
 * Tests session synchronization between Better Auth and WordPress
 */
class TestSessionSync {
    /**
     * @var int Test user ID
     */
    private $test_user_id;

    /**
     * Set up test environment
     */
    public function setUp() {
        // Create test user
        $this->test_user_id = wp_create_user(
            'test_user_' . time(),
            wp_generate_password(),
            'test_' . time() . '@example.com'
        );
    }

    /**
     * Clean up test environment
     */
    public function tearDown() {
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
    }

    /**
     * Test WordPress session creation
     * 
     * @return array Test results
     */
    public function test_create_wp_session() {
        $results = [];
        
        // Test 1: Create session for valid user
        $result = asap_create_wp_session_core($this->test_user_id);
        $results['valid_user'] = [
            'test' => 'Create session for valid user',
            'passed' => $result === true,
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        // Test 2: Try to create session for non-existent user
        $result = asap_create_wp_session_core(999999);
        $results['invalid_user'] = [
            'test' => 'Create session for invalid user',
            'passed' => is_wp_error($result),
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        return $results;
    }

    /**
     * Test session validation
     * 
     * @return array Test results
     */
    public function test_check_wp_session() {
        $results = [];

        // Create mock request with valid token
        $request = new WP_REST_Request();
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp, BETTER_AUTH_SHARED_SECRET);
        $token = $timestamp . '.' . $signature;
        $request->set_header('X-Better-Auth-Token', $token);

        // Test 1: Check valid session
        asap_create_wp_session_core($this->test_user_id);
        wp_set_current_user($this->test_user_id);
        $result = asap_check_wp_session($request);
        $results['valid_session'] = [
            'test' => 'Check valid session',
            'passed' => $result === true,
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        // Test 2: Check invalid token
        $request->set_header('X-Better-Auth-Token', 'invalid.token');
        $result = asap_check_wp_session($request);
        $results['invalid_token'] = [
            'test' => 'Check invalid token',
            'passed' => is_wp_error($result),
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        return $results;
    }

    /**
     * Test token exchange
     * 
     * @return array Test results
     */
    public function test_token_exchange() {
        $results = [];

        // Create mock request with valid token
        $request = new WP_REST_Request();
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp, BETTER_AUTH_SHARED_SECRET);
        $token = $timestamp . '.' . $signature;
        $request->set_header('X-Better-Auth-Token', $token);
        $request->set_header('X-Better-Auth-Timestamp', $timestamp);

        // Test 1: Exchange token for valid user
        wp_set_current_user($this->test_user_id);
        $result = asap_handle_token_exchange($request);
        $response_data = $result->get_data();
        $results['valid_exchange'] = [
            'test' => 'Exchange token for valid user',
            'passed' => isset($response_data['wp_token']) && isset($response_data['better_auth_token']),
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        // Test 2: Exchange with missing token
        $request->set_header('X-Better-Auth-Token', '');
        $result = asap_handle_token_exchange($request);
        $results['missing_token'] = [
            'test' => 'Exchange with missing token',
            'passed' => is_wp_error($result),
            'error' => is_wp_error($result) ? $result->get_error_message() : null
        ];

        return $results;
    }

    /**
     * Run all tests
     * 
     * @return array All test results
     */
    public function run_tests() {
        $this->setUp();
        
        $results = [
            'session_creation' => $this->test_create_wp_session(),
            'session_validation' => $this->test_check_wp_session(),
            'token_exchange' => $this->test_token_exchange()
        ];
        
        $this->tearDown();
        
        return $results;
    }
}

// Create test runner function
function asap_run_session_sync_tests() {
    $tester = new TestSessionSync();
    return $tester->run_tests();
} 