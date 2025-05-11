<?php
/**
 * Database Schema for ASAP Digest
 *
 * @package ASAP_Digest
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create or update database tables for the plugin
 */
function asap_digest_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Array of table create SQL statements
    $create_tables = [];
    
    // Content Sources Table
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_content_sources (
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
    
    // Content Metrics Table
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_source_metrics (
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
    
    // Crawler Errors Table
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_crawler_errors (
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
    
    // Activity Log Table
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_activity_log (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        action_type varchar(50) NOT NULL,
        object_id bigint(20) UNSIGNED DEFAULT NULL,
        object_type varchar(50) DEFAULT NULL,
        details text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY action_type (action_type),
        KEY object_id (object_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Ingested Content Table (Main content storage)
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_ingested_content (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type varchar(50) NOT NULL DEFAULT 'article',
        title text NOT NULL,
        content longtext NOT NULL,
        summary text DEFAULT NULL,
        source_url text NOT NULL,
        source_id varchar(255) DEFAULT NULL,
        publish_date datetime DEFAULT NULL,
        ingestion_date datetime NOT NULL,
        fingerprint varchar(64) NOT NULL,
        quality_score int(11) NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'pending',
        extra longtext DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY type (type),
        KEY status (status),
        KEY publish_date (publish_date),
        KEY fingerprint (fingerprint),
        KEY quality_score (quality_score),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Content Index Table (For deduplication and quality scoring)
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_content_index (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ingested_content_id bigint(20) UNSIGNED NOT NULL,
        fingerprint varchar(64) NOT NULL,
        quality_score int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ingested_content_id (ingested_content_id),
        KEY fingerprint (fingerprint),
        KEY quality_score (quality_score)
    ) $charset_collate;";
    
    // Duplicate Log Table (For tracking and resolving duplicates)
    $create_tables[] = "CREATE TABLE {$wpdb->prefix}asap_duplicate_log (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        content_id bigint(20) NOT NULL,
        duplicate_id bigint(20) NOT NULL,
        fingerprint varchar(64) NOT NULL,
        resolution varchar(20) DEFAULT NULL,
        created_at datetime NOT NULL,
        resolved_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY content_id (content_id),
        KEY duplicate_id (duplicate_id),
        KEY fingerprint (fingerprint),
        KEY resolution (resolution)
    ) $charset_collate;";
    
    // Include WordPress database upgrade functions
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create or update each table
    foreach ($create_tables as $sql) {
        dbDelta($sql);
    }
}

/**
 * Check if content processing tables exist
 * 
 * @return bool True if tables exist, false otherwise
 */
function asap_digest_check_tables_exist() {
    global $wpdb;
    
    // Check for required tables
    $required_tables = [
        $wpdb->prefix . 'asap_ingested_content',
        $wpdb->prefix . 'asap_content_index'
    ];
    
    foreach ($required_tables as $table) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        
        if (!$table_exists) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get list of content processing tables for uninstall
 * 
 * @return array List of table names
 */
function asap_digest_get_content_processing_tables() {
    global $wpdb;
    
    return [
        $wpdb->prefix . 'asap_ingested_content',
        $wpdb->prefix . 'asap_content_index',
        $wpdb->prefix . 'asap_duplicate_log'
    ];
} 