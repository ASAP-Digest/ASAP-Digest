<?php
/**
 * @file-marker ASAP_Digest_APIAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-api-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * API endpoint adapter for the content crawler.
 * Handles REST API and JSON API content sources.
 */
class APIAdapter implements ContentSourceAdapter {
    /**
     * @var int Request timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * @var array Default request headers
     */
    private $default_headers = [
        'User-Agent' => 'ASAP Digest Content Crawler/1.0',
        'Accept' => 'application/json'
    ];
    
    /**
     * Constructor
     * 
     * @param array $options Optional configuration options
     */
    public function __construct($options = []) {
        if (isset($options['timeout'])) {
            $this->timeout = (int)$options['timeout'];
        }
        
        if (isset($options['headers']) && is_array($options['headers'])) {
            $this->default_headers = array_merge($this->default_headers, $options['headers']);
        }
    }
    
    /**
     * Fetch content from an API source
     * 
     * @param object $source Source object
     * @return array Array of content items
     * @throws \Exception If API cannot be accessed or returns invalid data
     */
    public function fetch_content($source) {
        // Get source configuration
        $config = maybe_unserialize($source->config);
        
        // Prepare request arguments
        $args = [
            'timeout' => $this->timeout,
            'headers' => $this->get_request_headers($config),
            'sslverify' => true
        ];
        
        // Add authentication if configured
        if (!empty($config['auth_type'])) {
            $args = $this->add_authentication($args, $config);
        }
        
        // Make the request
        $response = wp_remote_get($source->url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            throw new \Exception("API request failed: " . $response->get_error_message());
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new \Exception("API returned error code: " . $response_code);
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new \Exception("API returned empty response");
        }
        
        // Parse JSON
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        // Extract items using the configured path
        $items_path = !empty($config['items_path']) ? $config['items_path'] : '';
        $items = $this->extract_items($data, $items_path);
        
        // Process items according to the mapping
        $processed_items = [];
        foreach ($items as $item) {
            $processed_item = $this->map_item($item, $config);
            if ($processed_item) {
                $processed_items[] = $processed_item;
            }
        }
        
        return $processed_items;
    }
    
    /**
     * Get request headers including any custom headers from config
     * 
     * @param array $config Source configuration
     * @return array Headers
     */
    private function get_request_headers($config) {
        $headers = $this->default_headers;
        
        // Add custom headers from config
        if (!empty($config['headers']) && is_array($config['headers'])) {
            foreach ($config['headers'] as $key => $value) {
                $headers[$key] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Add authentication to request arguments
     * 
     * @param array $args Request arguments
     * @param array $config Source configuration
     * @return array Modified request arguments
     */
    private function add_authentication($args, $config) {
        $auth_type = $config['auth_type'];
        
        switch ($auth_type) {
            case 'basic':
                if (!empty($config['auth_username']) && !empty($config['auth_password'])) {
                    $args['headers']['Authorization'] = 'Basic ' . base64_encode($config['auth_username'] . ':' . $config['auth_password']);
                }
                break;
                
            case 'bearer':
                if (!empty($config['auth_token'])) {
                    $args['headers']['Authorization'] = 'Bearer ' . $config['auth_token'];
                }
                break;
                
            case 'api_key':
                if (!empty($config['auth_key_name']) && !empty($config['auth_key_value'])) {
                    if (!empty($config['auth_key_in']) && $config['auth_key_in'] === 'query') {
                        // Add API key to URL query string
                        $args['query'] = [$config['auth_key_name'] => $config['auth_key_value']];
                    } else {
                        // Default: add as header
                        $args['headers'][$config['auth_key_name']] = $config['auth_key_value'];
                    }
                }
                break;
        }
        
        return $args;
    }
    
    /**
     * Extract items from response data using a path
     * 
     * @param array $data Response data
     * @param string $path Path to items (dot notation)
     * @return array Extracted items
     */
    private function extract_items($data, $path) {
        if (empty($path)) {
            return is_array($data) ? $data : [];
        }
        
        $segments = explode('.', $path);
        $current = $data;
        
        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return [];
            }
            $current = $current[$segment];
        }
        
        return is_array($current) ? $current : [];
    }
    
    /**
     * Map an API item to the standard content format
     * 
     * @param array $item Raw API item
     * @param array $config Source configuration
     * @return array|null Mapped item or null if invalid
     */
    private function map_item($item, $config) {
        // Get field mapping
        $mapping = !empty($config['field_mapping']) ? $config['field_mapping'] : [];
        
        // Default mapping if not specified
        if (empty($mapping)) {
            $mapping = [
                'title' => 'title',
                'content' => 'content',
                'url' => 'url',
                'publish_date' => 'date',
                'image' => 'image',
                'summary' => 'summary',
                'author' => 'author'
            ];
        }
        
        // Create mapped item
        $mapped_item = [
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'meta' => []
        ];
        
        // Map standard fields
        foreach ($mapping as $target => $source) {
            if (isset($item[$source])) {
                if ($target === 'meta') {
                    if (is_array($item[$source])) {
                        $mapped_item['meta'] = array_merge($mapped_item['meta'], $item[$source]);
                    }
                } else {
                    $mapped_item[$target] = $item[$source];
                }
            }
        }
        
        // Validate required fields
        if (empty($mapped_item['title']) || empty($mapped_item['url'])) {
            return null;
        }
        
        // Format date if needed
        if (!empty($mapped_item['publish_date']) && !empty($config['date_format'])) {
            $mapped_item['publish_date'] = $this->format_date($mapped_item['publish_date'], $config['date_format']);
        }
        
        return $mapped_item;
    }
    
    /**
     * Format a date according to the given format
     * 
     * @param string $date Input date
     * @param string $format Input date format
     * @return string Formatted date (Y-m-d H:i:s)
     */
    private function format_date($date, $format) {
        if (empty($date) || empty($format)) {
            return $date;
        }
        
        try {
            $datetime = \DateTime::createFromFormat($format, $date);
            if ($datetime) {
                return $datetime->format('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            // Fallback to strtotime
            $timestamp = strtotime($date);
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        return $date;
    }
} 