<?php
/**
 * Better Auth Configuration Class
 * 
 * Handles configuration settings for Better Auth WordPress integration.
 * 
 * @package ASAPDigest_Core
 * @created 05.16.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Auth_Config
 */

namespace ASAPDigest\Core\Auth;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auth Configuration Class
 * 
 * Manages configuration for Better Auth integration
 */
class ASAP_Digest_Auth_Config {
    /**
     * Initialize the configuration
     *
     * @return void
     */
    public static function init() {
        self::define_constants();
    }

    /**
     * Define Better Auth constants
     *
     * @return void
     */
    private static function define_constants() {
        // Define Better Auth database constants if not already defined
        if (!defined('BETTER_AUTH_DB_HOST')) {
            define('BETTER_AUTH_DB_HOST', getenv('BETTER_AUTH_DB_HOST') ?: 'localhost');
        }

        if (!defined('BETTER_AUTH_DB_PORT')) {
            define('BETTER_AUTH_DB_PORT', getenv('BETTER_AUTH_DB_PORT') ?: '3306');
        }

        if (!defined('BETTER_AUTH_DB_NAME')) {
            define('BETTER_AUTH_DB_NAME', getenv('BETTER_AUTH_DB_NAME') ?: DB_NAME);
        }

        if (!defined('BETTER_AUTH_DB_USER')) {
            define('BETTER_AUTH_DB_USER', getenv('BETTER_AUTH_DB_USER') ?: DB_USER);
        }

        if (!defined('BETTER_AUTH_DB_PASSWORD')) {
            define('BETTER_AUTH_DB_PASSWORD', getenv('BETTER_AUTH_DB_PASSWORD') ?: DB_PASSWORD);
        }

        if (!defined('BETTER_AUTH_API_URL')) {
            define('BETTER_AUTH_API_URL', getenv('BETTER_AUTH_API_URL') ?: site_url('/app/api/auth'));
        }

        if (!defined('BETTER_AUTH_SECRET')) {
            define('BETTER_AUTH_SECRET', getenv('BETTER_AUTH_SECRET') ?: SECURE_AUTH_KEY);
        }
    }

    /**
     * Get database configuration as an array
     *
     * @return array Database configuration
     */
    public static function get_db_config() {
        return [
            'host' => BETTER_AUTH_DB_HOST,
            'port' => BETTER_AUTH_DB_PORT,
            'dbname' => BETTER_AUTH_DB_NAME,
            'user' => BETTER_AUTH_DB_USER,
            'password' => BETTER_AUTH_DB_PASSWORD
        ];
    }
} 