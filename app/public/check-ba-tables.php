<?php
// Simple script to check if Better Auth tables exist in the database

// Database connection details from PE-CTXT
$host = 'localhost';
$port = 10018;
$db_name = 'local';
$username = 'root';
$password = 'root';
$socket = '/Volumes/Macintosh HD/Users/vsmith/Library/Application Support/Local/run/AFTH2oxzp/mysql/mysqld.sock';

// Try connecting using socket first
try {
    $mysqli = new mysqli(null, $username, $password, $db_name, null, $socket);

    if ($mysqli->connect_error) {
        throw new Exception("Socket connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected successfully using socket\n";
} catch (Exception $e) {
    // If socket connection fails, try TCP
    try {
        $mysqli = new mysqli($host, $username, $password, $db_name, $port);
        
        if ($mysqli->connect_error) {
            throw new Exception("TCP connection failed: " . $mysqli->connect_error);
        }
        
        echo "Connected successfully using TCP\n";
    } catch (Exception $e2) {
        die("All connection attempts failed:\n" . $e->getMessage() . "\n" . $e2->getMessage() . "\n");
    }
}

// Check for Better Auth tables
$tables = ['ba_users', 'ba_sessions', 'ba_accounts', 'ba_verifications'];
$results = [];

foreach ($tables as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $result = $mysqli->query($query);
    
    if ($result) {
        $exists = $result->num_rows > 0;
        $results[$table] = $exists;
        echo "$table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
        
        if ($exists) {
            // Show count of records
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $count_result = $mysqli->query($count_query);
            if ($count_result) {
                $count = $count_result->fetch_assoc()['count'];
                echo "  - Records: $count\n";
            }
        }
    } else {
        echo "Error querying table $table: " . $mysqli->error . "\n";
    }
}

// Summary
$all_exist = array_reduce($results, function($carry, $item) {
    return $carry && $item;
}, true);

echo "\n== Summary ==\n";
echo "All required Better Auth tables " . ($all_exist ? "EXIST" : "DO NOT ALL EXIST") . "\n";

// Close connection
$mysqli->close();
?> 