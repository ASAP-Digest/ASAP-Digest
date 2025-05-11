<?php
/**
 * ASAP Digest Core Activator Class
 * 
 * Handles database table creation and cleanup during plugin activation/deactivation.
 * 
 * @package ASAPDigest_Core
 * @created 04.17.25 | 10:45 AM PDT
 * @file-marker ASAP_Digest_Core_Activator
 * @location /wp-content/plugins/asapdigest-core/includes/class-activator.php
 */

namespace ASAPDigest\Core;

/**
 * Class ASAP_Digest_Activator
 * 
 * @since 1.0.0
 */
class ASAP_Digest_Activator {
    /**
     * Run activation tasks: create required tables for Content Ingestion & Indexing System
     * 
     * @since 1.0.0
     * @return void
     */
    public static function activate() {
        global $wpdb;
        
        try {
            // First, create the basic tables using our comprehensive Database class
            $database = new ASAP_Digest_Database();
            $result = $database->create_tables();
            
            if (!$result) {
                throw new \Exception("Failed to create database tables");
            }
            
            // Then handle migrations for version updates
            $migration_result = $database->handle_migrations();
            
            if (!$migration_result) {
                error_log('ASAP Digest Core: Database migrations encountered issues. Some features may not work correctly.');
            }
            
            // Schedule cleanup of old digests and notifications
            if (!wp_next_scheduled('asap_cleanup_data')) {
                wp_schedule_event(time(), 'daily', 'asap_cleanup_data');
            }
            
            // Load schema file for backward compatibility with existing code
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            require_once(ASAP_DIGEST_PLUGIN_DIR . 'includes/schema.php');
            
            // Verify the tables exist
            if (!asap_digest_check_tables_exist()) {
                error_log('ASAP Digest Core: Table verification failed after activation.');
            }
            
            // Set schema version in case it wasn't set by the Database class
            update_option('asap_digest_schema_version', ASAP_DIGEST_SCHEMA_VERSION);
            
            error_log('ASAP Digest Core: Activation complete');
        } catch (\Exception $e) {
            error_log('ASAP Digest Core: Activation error: ' . $e->getMessage());
            
            // Rethrow the exception to notify admin
            throw $e;
        }
    }
    
    /**
     * Run deactivation tasks: clean up scheduled tasks and temporary options
     * 
     * @since 1.0.0
     * @return void
     */
    public static function deactivate() {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            // Remove scheduled cleanup
            wp_clear_scheduled_hook('asap_cleanup_data');
            
            // Remove debug options
            delete_option('sms_digest_time');
            
            error_log('ASAP Digest Core: Deactivation complete');
        }
    }
} 