<?php
/**
 * Test script to verify database class loading
 */

// Define ABSPATH so WordPress classes will load correctly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}

error_log('TEST: Starting database class test');

// Try to include the database class
$database_path = __DIR__ . '/includes/class-database.php';
error_log('TEST: Attempting to include: ' . $database_path);

if (file_exists($database_path)) {
    error_log('TEST: File exists, including it');
    require_once $database_path;
    
    // Check if class exists
    if (class_exists('ASAPDigest\\Core\\ASAP_Digest_Database')) {
        error_log('TEST: Class exists! ASAPDigest\\Core\\ASAP_Digest_Database');
        echo "SUCCESS: Database class exists!";
    } else {
        error_log('TEST: Class NOT found: ASAPDigest\\Core\\ASAP_Digest_Database');
        echo "ERROR: Database class not found!";
    }
} else {
    error_log('TEST: File does not exist: ' . $database_path);
    echo "ERROR: Database file not found!";
}

error_log('TEST: Database class test complete'); 