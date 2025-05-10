<?php
/**
 * Example Client for Consuming the Ingested Content API
 * 
 * This example demonstrates how clients can use the enhanced REST API to:
 * 1. Fetch content with filtering by type and quality score
 * 2. Find similar content for a given item
 * 3. Leverage the improved content processing features
 *
 * Usage: include this file in a WordPress context, or run as a standalone script 
 * with proper WordPress bootstrapping
 *
 * @package ASAP_Digest
 * @subpackage Examples
 * @since 2.2.0
 */

// Bootstrap WordPress if running as standalone script
if (!function_exists('wp_remote_get')) {
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress environment not found. Please run this script in a WordPress context.');
    }
}

/**
 * Example class for consuming the Ingested Content API
 */
class ASAP_Digest_API_Consumer_Example {
    
    /**
     * Base URL for the API
     *
     * @var string
     */
    private $api_base_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = rest_url('asap/v1/ingested-content');
    }
    
    /**
     * Run the example
     */
    public function run() {
        echo "<h1>ASAP Digest Content API Consumer Example</h1>";
        echo "<p>Demonstrating how to consume the enhanced Ingested Content API</p>";
        
        $this->example_get_high_quality_content();
        $this->example_search_content();
        $this->example_find_similar_content();
    }
    
    /**
     * Example: Get high-quality content
     * Demonstrates filtering by quality score
     */
    private function example_get_high_quality_content() {
        echo "<h2>Example 1: Get High-Quality Content</h2>";
        
        // Build request URL with parameters
        $args = [
            'min_quality_score' => 80, // Only content with quality score >= 80
            'per_page' => 5,           // Limit to 5 items
            'orderby' => 'quality_score', // Order by quality score
            'order' => 'DESC',         // Highest first
        ];
        
        $url = add_query_arg($args, $this->api_base_url);
        
        // Make the request
        $response = wp_remote_get($url);
        
        // Check for errors
        if (is_wp_error($response)) {
            echo '<div class="error"><p>Error: ' . esc_html($response->get_error_message()) . '</p></div>';
            return;
        }
        
        // Parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Display the results
        if (empty($data)) {
            echo '<p>No high-quality content found.</p>';
        } else {
            echo '<p>Found ' . count($data) . ' high-quality content items:</p>';
            echo '<ul>';
            foreach ($data as $item) {
                echo '<li>';
                echo '<strong>' . esc_html($item['title']) . '</strong> ';
                echo '[Type: ' . esc_html($item['type']) . '] ';
                echo '[Quality Score: ' . esc_html($item['quality_score']) . '] ';
                echo '<a href="' . esc_url($item['source_url']) . '" target="_blank">Source</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    /**
     * Example: Search content
     * Demonstrates keyword search and multiple types
     */
    private function example_search_content() {
        echo "<h2>Example 2: Search Content</h2>";
        
        // Build request URL with parameters
        $args = [
            'search' => 'technology', // Search for content with "technology"
            'type' => ['article', 'podcast'], // Multiple content types
            'per_page' => 5,          // Limit to 5 items
        ];
        
        $url = add_query_arg($args, $this->api_base_url);
        
        // Make the request
        $response = wp_remote_get($url);
        
        // Check for errors
        if (is_wp_error($response)) {
            echo '<div class="error"><p>Error: ' . esc_html($response->get_error_message()) . '</p></div>';
            return;
        }
        
        // Parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Display the results
        if (empty($data)) {
            echo '<p>No content found matching the search criteria.</p>';
        } else {
            echo '<p>Found ' . count($data) . ' content items matching "technology":</p>';
            echo '<ul>';
            foreach ($data as $item) {
                echo '<li>';
                echo '<strong>' . esc_html($item['title']) . '</strong> ';
                echo '[Type: ' . esc_html($item['type']) . '] ';
                echo '[Quality Score: ' . esc_html($item['quality_score']) . '] ';
                echo '<a href="' . esc_url($item['source_url']) . '" target="_blank">Source</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    /**
     * Example: Find similar content
     * Demonstrates using the similar content endpoint
     */
    private function example_find_similar_content() {
        echo "<h2>Example 3: Find Similar Content</h2>";
        
        // First, get a sample content item to find similar content for
        $args = [
            'per_page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];
        
        $url = add_query_arg($args, $this->api_base_url);
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            echo '<div class="error"><p>Error: ' . esc_html($response->get_error_message()) . '</p></div>';
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            echo '<p>No content found to use as a reference.</p>';
            return;
        }
        
        // Use the first item as our reference
        $reference_item = $data[0];
        $reference_id = $reference_item['id'];
        
        echo '<p>Finding content similar to: <strong>' . esc_html($reference_item['title']) . '</strong></p>';
        
        // Now request similar content
        $similar_url = trailingslashit($this->api_base_url) . $reference_id . '/similar';
        $args = [
            'limit' => 5,
            'min_similarity' => 30, // Minimum similarity score of 30%
        ];
        
        $similar_url = add_query_arg($args, $similar_url);
        $similar_response = wp_remote_get($similar_url);
        
        if (is_wp_error($similar_response)) {
            echo '<div class="error"><p>Error: ' . esc_html($similar_response->get_error_message()) . '</p></div>';
            return;
        }
        
        $similar_body = wp_remote_retrieve_body($similar_response);
        $similar_data = json_decode($similar_body, true);
        
        // Display the results
        if (empty($similar_data)) {
            echo '<p>No similar content found.</p>';
        } else {
            echo '<p>Found ' . count($similar_data) . ' similar content items:</p>';
            echo '<ul>';
            foreach ($similar_data as $item) {
                $similarity = isset($item['similarity_score']) ? $item['similarity_score'] : 'N/A';
                
                echo '<li>';
                echo '<strong>' . esc_html($item['title']) . '</strong> ';
                echo '[Similarity: ' . esc_html($similarity) . '%] ';
                echo '[Type: ' . esc_html($item['type']) . '] ';
                echo '[Quality Score: ' . esc_html($item['quality_score']) . '] ';
                echo '<a href="' . esc_url($item['source_url']) . '" target="_blank">Source</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
    }
}

// Run the example if this is being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    $example = new ASAP_Digest_API_Consumer_Example();
    $example->run();
} 