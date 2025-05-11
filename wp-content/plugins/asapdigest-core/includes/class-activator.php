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
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        
        // Content Sources Table
        $sql_sources = "CREATE TABLE {$prefix}asap_content_sources (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            url text NOT NULL,
            config longtext NOT NULL,
            content_types text NOT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            last_fetch bigint(20) UNSIGNED DEFAULT NULL,
            last_status varchar(50) DEFAULT NULL,
            fetch_interval int(11) NOT NULL DEFAULT 3600,
            min_interval int(11) NOT NULL DEFAULT 1800,
            max_interval int(11) NOT NULL DEFAULT 86400,
            fetch_count int(11) NOT NULL DEFAULT 0,
            quota_max_items int(11) DEFAULT NULL,
            quota_max_size bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_active (type, active),
            KEY last_fetch (last_fetch)
        ) $charset_collate;";

        // Source Metrics Table
        $sql_metrics = "CREATE TABLE {$prefix}asap_source_metrics (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id bigint(20) UNSIGNED NOT NULL,
            date date NOT NULL,
            items_found int(11) NOT NULL DEFAULT 0,
            items_stored int(11) NOT NULL DEFAULT 0,
            items_rejected int(11) NOT NULL DEFAULT 0,
            processing_time float NOT NULL DEFAULT 0,
            error_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY source_date (source_id, date),
            KEY date (date)
        ) $charset_collate;";

        // Storage Metrics Table
        $sql_storage = "CREATE TABLE {$prefix}asap_storage_metrics (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id bigint(20) UNSIGNED NOT NULL,
            content_type varchar(50) NOT NULL,
            date date NOT NULL,
            item_count int(11) NOT NULL DEFAULT 0,
            total_size bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY source_type_date (source_id, content_type, date),
            KEY date (date)
        ) $charset_collate;";

        // Error Log Table
        $sql_errors = "CREATE TABLE {$prefix}asap_crawler_errors (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            error_type varchar(50) NOT NULL,
            message text NOT NULL,
            context longtext DEFAULT NULL,
            severity varchar(20) NOT NULL DEFAULT 'error',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY error_type (error_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Moderation Log Table
        $sql_moderation_log = "CREATE TABLE {$prefix}asap_moderation_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content_id bigint(20) UNSIGNED NOT NULL,
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(20) NOT NULL,
            reviewer bigint(20) UNSIGNED DEFAULT NULL,
            decision varchar(20) NOT NULL,
            reason text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY content_id (content_id),
            KEY source_id (source_id),
            KEY reviewer (reviewer),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Content Index Table for deduplication and quality scoring
        $sql_content_index = "CREATE TABLE IF NOT EXISTS {$prefix}asap_content_index (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            fingerprint char(64) NOT NULL,
            quality_score tinyint UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_fingerprint (fingerprint),
            UNIQUE KEY uniq_post_id (post_id)
        ) $charset_collate;";

        // Digest Storage Table
        $sql_digests = "CREATE TABLE IF NOT EXISTS {$prefix}asap_digests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content longtext NOT NULL,
            share_link varchar(255) DEFAULT NULL,
            podcast_url varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Notifications Table
        $sql_notifications = "CREATE TABLE IF NOT EXISTS {$prefix}asap_notifications (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            endpoint varchar(255) NOT NULL,
            p256dh varchar(255) NOT NULL,
            auth varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY endpoint (endpoint),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Execute all table creation SQL
        dbDelta($sql_sources);
        dbDelta($sql_metrics);
        dbDelta($sql_storage);
        dbDelta($sql_errors);
        dbDelta($sql_moderation_log);
        dbDelta($sql_content_index);
        dbDelta($sql_digests);
        dbDelta($sql_notifications);
        
        // Set schema version
        update_option('asap_digest_schema_version', ASAP_DIGEST_SCHEMA_VERSION);
        
        // Schedule cleanup of old digests and notifications
        if (!wp_next_scheduled('asap_cleanup_data')) {
            wp_schedule_event(time(), 'daily', 'asap_cleanup_data');
        }
        
        error_log('ASAP Digest Core: Activation complete');
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