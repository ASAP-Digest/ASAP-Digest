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
    
    // Add future upgrade paths here
}
?> 