<?php
// Database schema upgrade path
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