<?php
/**
 * Simple script to test template creation
 * Access via: /test-template-creation.php
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<h1>ASAP Digest Template Creation Test</h1>";

// Check if WordPress is loaded
if (!function_exists('wp_insert_post')) {
    echo "<p style='color: red;'>Error: WordPress not loaded properly.</p>";
    exit;
}

echo "<p>WordPress loaded successfully.</p>";

// Check if the plugin is active
if (!class_exists('\\ASAPDigest\\CPT\\ASAP_Digest_Template_CPT')) {
    echo "<p style='color: red;'>Error: ASAP Digest plugin not loaded or template CPT class not found.</p>";
    exit;
}

echo "<p>ASAP Digest plugin loaded successfully.</p>";

// Check existing templates
$existing_templates = get_posts([
    'post_type' => 'asap_digest_template',
    'post_status' => 'any',
    'numberposts' => -1
]);

echo "<h2>Existing Templates</h2>";
if (empty($existing_templates)) {
    echo "<p>No templates found.</p>";
} else {
    echo "<ul>";
    foreach ($existing_templates as $template) {
        echo "<li><strong>{$template->post_title}</strong> (ID: {$template->ID}, Status: {$template->post_status})</li>";
    }
    echo "</ul>";
}

// Test template creation
if (isset($_GET['create'])) {
    echo "<h2>Creating Templates...</h2>";
    
    try {
        $template_cpt = new \ASAPDigest\CPT\ASAP_Digest_Template_CPT();
        $template_cpt->force_create_default_templates();
        echo "<p style='color: green;'>Templates created successfully!</p>";
        
        // Refresh the page to show new templates
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating templates: " . esc_html($e->getMessage()) . "</p>";
    }
} else {
    echo "<p><a href='?create=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Create Default Templates</a></p>";
}

// Test REST API endpoint
echo "<h2>REST API Test</h2>";
$api_url = rest_url('asap/v1/digest-builder/layouts');
echo "<p>API Endpoint: <a href='{$api_url}' target='_blank'>{$api_url}</a></p>";

// Test if we can make a local API call
$response = wp_remote_get($api_url);
if (is_wp_error($response)) {
    echo "<p style='color: red;'>API Error: " . $response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<p>API Status: {$status_code}</p>";
    if ($status_code === 200) {
        $data = json_decode($body, true);
        if ($data) {
            echo "<p style='color: green;'>API working! Found " . count($data) . " templates.</p>";
            echo "<details><summary>API Response</summary><pre>" . esc_html(json_encode($data, JSON_PRETTY_PRINT)) . "</pre></details>";
        } else {
            echo "<p style='color: orange;'>API returned invalid JSON.</p>";
        }
    } else {
        echo "<p style='color: red;'>API Error: " . esc_html($body) . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='/wp-admin/tools.php?page=asap-template-test'>Go to Admin Template Test Page</a></p>";
echo "<p><a href='/test-api'>Go to Frontend API Test Page</a></p>";
echo "<p><a href='/digest/create'>Go to Digest Creation Page</a></p>";
?> 