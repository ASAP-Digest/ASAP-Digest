<?php
/**
 * ASAP Digest Better Auth Integration
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Better_Auth
 */

namespace ASAPDigest\Core;

use WP_Error;
use function get_option;
use function update_option;

if (!defined('ABSPATH')) {
    exit;
}

class ASAP_Digest_Better_Auth {
    /**
     * @var string Option name for auth settings
     */
    private $settings_option = 'asap_digest_auth_settings';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        // Set default settings if not exist
        if (!get_option($this->settings_option)) {
            $this->set_default_settings();
        }
    }

    /**
     * Set default auth settings
     */
    private function set_default_settings() {
        $defaults = [
            'session_length' => 3600, // 1 hour
            'refresh_token_length' => 604800, // 7 days
            'max_sessions' => 5
        ];

        update_option($this->settings_option, $defaults);
    }

    /**
     * Get auth status
     *
     * @return array|WP_Error Auth status or error
     */
    public function get_auth_status() {
        $settings = get_option($this->settings_option);
        
        if (!$settings) {
            return new WP_Error(
                'auth_status_error',
                __('Could not retrieve auth status.', 'asap-digest')
            );
        }

        return [
            'is_configured' => true,
            'settings' => $settings,
            'user_id' => get_current_user_id()
        ];
    }

    /**
     * Update auth settings
     *
     * @param array $settings New settings
     * @return array|WP_Error Updated settings or error
     */
    public function update_auth_settings($settings) {
        $current = get_option($this->settings_option);
        
        if (!$current) {
            return new WP_Error(
                'settings_update_error',
                __('Could not retrieve current settings.', 'asap-digest')
            );
        }

        $updated = array_merge($current, $settings);
        
        if (!update_option($this->settings_option, $updated)) {
            return new WP_Error(
                'settings_update_error',
                __('Could not update settings.', 'asap-digest')
            );
        }

        return $updated;
    }

    /**
     * Validate current session
     *
     * @return bool|WP_Error True if valid, error if not
     */
    public function validate_session() {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'invalid_session',
                __('User is not logged in.', 'asap-digest')
            );
        }

        // Add more session validation logic here

        return true;
    }
} 