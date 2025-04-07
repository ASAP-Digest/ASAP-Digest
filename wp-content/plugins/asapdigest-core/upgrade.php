<?php
/**
 * ASAP Digest Database Upgrade Functions
 * 
 * Handles database schema upgrades for the ASAP Digest Core plugin.
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * @file-marker ASAP_Digest_Upgrade
 */

use ASAPDigest\Core\ASAP_Digest_Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Upgrade database schema based on version
 * 
 * @param string $from_version The version to upgrade from
 * @return void
 */
function asap_digest_upgrade_db($from_version) {
    global $wpdb;
    
    if (version_compare($from_version, '1.0.2', '<')) {
        $digests_table = $wpdb->prefix . 'asap_digests';
        $wpdb->query("ALTER TABLE $digests_table ADD COLUMN ip_address VARCHAR(45) NOT NULL DEFAULT '' AFTER user_id");
        
        $notifications_table = $wpdb->prefix . 'asap_notifications';
        $wpdb->query("ALTER TABLE $notifications_table ADD COLUMN user_agent TEXT NOT NULL AFTER endpoint");
    }
    
    // Fix Better Auth tables AUTO_INCREMENT
    if (version_compare($from_version, '1.0.3', '<')) {
        // Backup existing data
        $tables = ['ba_users', 'ba_sessions', 'ba_verifications', 'ba_wp_user_map'];
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $backup_table = $table_name . '_backup';
            $wpdb->query("CREATE TABLE IF NOT EXISTS $backup_table LIKE $table_name");
            $wpdb->query("INSERT INTO $backup_table SELECT * FROM $table_name");
        }
        
        try {
            // Drop existing tables (in reverse order due to foreign keys)
            $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
            foreach (array_reverse($tables) as $table) {
                $table_name = $wpdb->prefix . $table;
                $wpdb->query("DROP TABLE IF EXISTS $table_name");
            }
            $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Recreate tables with correct schema
            require_once(plugin_dir_path(__FILE__) . 'includes/class-database.php');
            
            $database = new ASAP_Digest_Database();
            $database->create_tables();
            
            // Restore data from backups
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . $table;
                $backup_table = $table_name . '_backup';
                $wpdb->query("INSERT INTO $table_name SELECT * FROM $backup_table");
                $wpdb->query("DROP TABLE IF EXISTS $backup_table");
            }
        } catch (Exception $e) {
            error_log('Failed to upgrade Better Auth tables: ' . $e->getMessage());
            // Restore from backups if upgrade failed
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . $table;
                $backup_table = $table_name . '_backup';
                if ($wpdb->get_var("SHOW TABLES LIKE '$backup_table'") == $backup_table) {
                    $wpdb->query("DROP TABLE IF EXISTS $table_name");
                    $wpdb->query("RENAME TABLE $backup_table TO $table_name");
                }
            }
            throw $e;
        }
    }
}
?> 