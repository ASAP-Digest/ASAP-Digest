<?php
/**
 * @file-marker ASAP_Digest_Core_Activator
 * @location /wp-content/plugins/asapdigest-core/includes/class-activator.php
 */

class ASAP_Digest_Core_Activator {
    /**
     * Run activation tasks: create required tables for Content Ingestion & Indexing System v2
     * Also migrates wp_posts to add asap_fingerprint and asap_quality_score columns (v2.2)
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
        /**
         * Moderation Log Table
         * Tracks all moderation actions for content ingestion (approve/reject, reviewer, timestamp, decision, reason, content reference)
         *
         * Columns:
         * - id: bigint(20) UNSIGNED, primary key
         * - content_id: bigint(20) UNSIGNED, reference to ingested content (e.g., post ID)
         * - source_id: bigint(20) UNSIGNED, reference to content source
         * - action: varchar(20), moderation action (approve/reject)
         * - reviewer: bigint(20) UNSIGNED, user ID of moderator
         * - decision: varchar(20), decision (approved/rejected/flagged)
         * - reason: text, optional reason/comment
         * - created_at: datetime, timestamp of moderation action
         */
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

        // --- [ v2.2: Create custom content index table for deduplication and quality scoring ] ---
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

        dbDelta($sql_sources);
        dbDelta($sql_metrics);
        dbDelta($sql_storage);
        dbDelta($sql_errors);
        dbDelta($sql_moderation_log);
        dbDelta($sql_content_index);
    }
}

// Register activation hook
register_activation_hook(__FILE__, ['ASAP_Digest_Core_Activator', 'activate']); 