<?php
/**
 * @file-marker ASAP_Digest_APIAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-api-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * Enhanced API endpoint adapter for the content crawler.
 * Handles REST API and JSON API content sources with robust error handling,
 * flexible authentication, configurable pagination, and rate limiting awareness.
 * 
 * @since 1.0.0
 * @updated 1.1.0 - Added OAuth support, pagination strategies, rate limiting
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
     * @var array Supported authentication types
     */
    private $supported_auth_types = [
        'basic',      // Basic Auth (username + password)
        'bearer',     // Bearer token (Authorization: Bearer TOKEN)
        'api_key',    // API Key (in header or query)
        'oauth2',     // OAuth 2.0 (client credentials flow)
        'digest',     // HTTP Digest authentication
        'aws_sig4'    // AWS Signature V4
    ];
    
    /**
     * @var array Supported pagination strategies
     */
    private $pagination_strategies = [
        'page',       // Traditional page number (page=X)
        'offset',     // Offset-based pagination (offset=X&limit=Y)
        'cursor',     // Cursor-based pagination (cursor=X)
        'link',       // Link header pagination (RFC 5988)
        'custom'      // Custom pagination using response path extraction
    ];
    
    /**
     * @var int Maximum number of items to fetch (across all pagination requests)
     */
    private $max_items = 100;
    
    /**
     * @var int Maximum number of pagination requests to make
     */
    private $max_pages = 5;
    
    /**
     * @var array Rate limiting state storage
     */
    private $rate_limit_state = [
        'remaining' => null,     // Remaining requests allowed
        'reset' => null,         // Timestamp when limit resets
        'limit' => null,         // Total requests limit
        'retry_after' => null,   // Retry-After header value
        'backoff_until' => null  // Timestamp until which we should back off
    ];
    
    /**
     * @var array OAuth tokens cache
     */
    private $oauth_tokens = [];
    
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
        
        if (isset($options['max_items'])) {
            $this->max_items = (int)$options['max_items'];
        }
        
        if (isset($options['max_pages'])) {
            $this->max_pages = (int)$options['max_pages'];
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
        $config = is_string($source->config) ? maybe_unserialize($source->config) : $source->config;
        
        if (!is_array($config)) {
            $config = [];
        }
        
        // Initialize pagination state
        $pagination = $this->initialize_pagination($config);
        
        // Initialize result aggregation
        $all_items = [];
        $page_count = 0;
        
        // Check if we need to respect a backoff period
        if (!empty($this->rate_limit_state['backoff_until']) && time() < $this->rate_limit_state['backoff_until']) {
            $wait_time = $this->rate_limit_state['backoff_until'] - time();
            error_log("APIAdapter: Respecting backoff period for {$source->url}. Waiting {$wait_time} seconds.");
            
            // If backoff is more than 5 minutes, throw an exception
            if ($wait_time > 300) {
                throw new \Exception("API rate limit exceeded. Backoff period of {$wait_time} seconds required.");
            }
            
            // Otherwise, just wait
            sleep(min($wait_time, 30)); // Max 30 seconds wait
        }
        
        // Pagination loop
        do {
            try {
                // Prepare request URL with pagination parameters
                $request_url = $this->prepare_paginated_url($source->url, $pagination);
                
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
                $response = wp_remote_get($request_url, $args);
                
                // Process response
                $processed_response = $this->process_api_response($response, $config);
                
                // Update rate limiting state
                $this->update_rate_limit_state($response);
                
                // Extract items using the configured path
                $items_path = !empty($config['items_path']) ? $config['items_path'] : '';
                $items = $this->extract_items($processed_response, $items_path);
                
                // Process items according to the mapping
                foreach ($items as $item) {
                    $processed_item = $this->map_item($item, $config);
                    if ($processed_item) {
                        $all_items[] = $processed_item;
                        
                        // Check if we've reached the maximum items
                        if (count($all_items) >= $this->max_items) {
                            break 2; // Exit both foreach and do-while
                        }
                    }
                }
                
                // Update pagination state for next request
                $pagination = $this->update_pagination_state($pagination, $processed_response, $config);
                $page_count++;
                
                // Add a small delay between requests to be nice to the API
                usleep(200000); // 200ms
                
            } catch (\Exception $e) {
                // If this is a rate limit exception, handle it specially
                if (strpos($e->getMessage(), 'rate limit') !== false) {
                    // Implement exponential backoff
                    $backoff_seconds = min(pow(2, $page_count), 3600); // Max 1 hour
                    $this->rate_limit_state['backoff_until'] = time() + $backoff_seconds;
                    
                    error_log("APIAdapter: Rate limit hit for {$source->url}. Backing off for {$backoff_seconds} seconds.");
                    
                    // If we have some items already, return them
                    if (!empty($all_items)) {
                        break;
                    }
                    
                    // Otherwise, rethrow
                    throw $e;
                }
                
                // For other exceptions, log and rethrow
                error_log("APIAdapter Error: " . $e->getMessage());
                throw $e;
            }
            
        } while ($pagination['has_more'] && $page_count < $this->max_pages);
        
        // Return all collected items
        return $all_items;
    }
    
    /**
     * Initialize pagination state based on config
     * 
     * @param array $config Source configuration
     * @return array Pagination state
     */
    private function initialize_pagination($config) {
        $strategy = !empty($config['pagination_strategy']) ? $config['pagination_strategy'] : 'none';
        
        $pagination = [
            'strategy' => $strategy,
            'has_more' => true,
            'current_page' => 1,
            'per_page' => !empty($config['per_page']) ? (int)$config['per_page'] : 20,
            'offset' => 0,
            'cursor' => null,
            'next_url' => null,
            'total_items' => null,
            'total_pages' => null,
            'params' => []
        ];
        
        // Set strategy-specific parameters
        switch ($strategy) {
            case 'page':
                $pagination['params'] = [
                    'page_param' => !empty($config['page_param']) ? $config['page_param'] : 'page',
                    'per_page_param' => !empty($config['per_page_param']) ? $config['per_page_param'] : 'per_page'
                ];
                break;
                
            case 'offset':
                $pagination['params'] = [
                    'offset_param' => !empty($config['offset_param']) ? $config['offset_param'] : 'offset',
                    'limit_param' => !empty($config['limit_param']) ? $config['limit_param'] : 'limit'
                ];
                break;
                
            case 'cursor':
                $pagination['params'] = [
                    'cursor_param' => !empty($config['cursor_param']) ? $config['cursor_param'] : 'cursor',
                    'cursor_path' => !empty($config['cursor_path']) ? $config['cursor_path'] : 'meta.next_cursor'
                ];
                break;
                
            case 'custom':
                $pagination['params'] = [
                    'has_more_path' => !empty($config['has_more_path']) ? $config['has_more_path'] : 'meta.has_more',
                    'next_page_path' => !empty($config['next_page_path']) ? $config['next_page_path'] : 'meta.next_page'
                ];
                break;
        }
        
        return $pagination;
    }
    
    /**
     * Prepare URL with pagination parameters
     * 
     * @param string $url Base URL
     * @param array $pagination Pagination state
     * @return string URL with pagination parameters
     */
    private function prepare_paginated_url($url, $pagination) {
        // If we have a next_url from a previous request, use that directly
        if (!empty($pagination['next_url'])) {
            return $pagination['next_url'];
        }
        
        // Otherwise, add parameters based on strategy
        $params = [];
        
        switch ($pagination['strategy']) {
            case 'page':
                $params[$pagination['params']['page_param']] = $pagination['current_page'];
                $params[$pagination['params']['per_page_param']] = $pagination['per_page'];
                break;
                
            case 'offset':
                $params[$pagination['params']['offset_param']] = $pagination['offset'];
                $params[$pagination['params']['limit_param']] = $pagination['per_page'];
                break;
                
            case 'cursor':
                if (!empty($pagination['cursor'])) {
                    $params[$pagination['params']['cursor_param']] = $pagination['cursor'];
                }
                break;
        }
        
        // Add parameters to URL
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        return $url;
    }
    
    /**
     * Process API response
     * 
     * @param array|\WP_Error $response Response from wp_remote_get
     * @param array $config Source configuration
     * @return array Processed response data
     * @throws \Exception If API request fails
     */
    private function process_api_response($response, $config) {
        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("APIAdapter: Request error - {$error_message}");
            throw new \Exception("API request failed: {$error_message}");
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Handle rate limiting response codes
        if (in_array($response_code, [429, 403, 503])) {
            $retry_after = wp_remote_retrieve_header($response, 'retry-after');
            
            if ($retry_after) {
                $this->rate_limit_state['retry_after'] = is_numeric($retry_after) ? 
                    time() + (int)$retry_after : 
                    strtotime($retry_after);
                
                throw new \Exception("API rate limit exceeded. Retry after: {$retry_after}");
            } else {
                throw new \Exception("API rate limit exceeded (HTTP {$response_code})");
            }
        }
        
        // Handle other error codes
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_detail = !empty($body) ? " Body: {$body}" : '';
            error_log("APIAdapter: HTTP error {$response_code} for request.{$error_detail}");
            throw new \Exception("API returned error code: {$response_code}");
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new \Exception("API returned empty response");
        }
        
        // Parse JSON
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json_error = json_last_error_msg();
            error_log("APIAdapter: JSON parsing error - {$json_error}. Response body: {$body}");
            throw new \Exception("Invalid JSON response: {$json_error}");
        }
        
        return $data;
    }
    
    /**
     * Update pagination state based on response
     * 
     * @param array $pagination Current pagination state
     * @param array $response Processed response data
     * @param array $config Source configuration
     * @return array Updated pagination state
     */
    private function update_pagination_state($pagination, $response, $config) {
        // Default to no more pages
        $pagination['has_more'] = false;
        
        switch ($pagination['strategy']) {
            case 'page':
                // Increment page number
                $pagination['current_page']++;
                
                // Check if we have more pages
                if (!empty($pagination['total_pages'])) {
                    $pagination['has_more'] = $pagination['current_page'] <= $pagination['total_pages'];
                } else if (!empty($pagination['total_items'])) {
                    $items_so_far = ($pagination['current_page'] - 1) * $pagination['per_page'];
                    $pagination['has_more'] = $items_so_far < $pagination['total_items'];
                } else {
                    // If we don't have total info, use presence of items in response
                    $items_path = !empty($config['items_path']) ? $config['items_path'] : '';
                    $items = $this->extract_items($response, $items_path);
                    $pagination['has_more'] = !empty($items) && count($items) >= $pagination['per_page'];
                }
                break;
                
            case 'offset':
                // Increment offset
                $pagination['offset'] += $pagination['per_page'];
                
                // Check if we have more items
                if (!empty($pagination['total_items'])) {
                    $pagination['has_more'] = $pagination['offset'] < $pagination['total_items'];
                } else {
                    // If we don't have total info, use presence of items in response
                    $items_path = !empty($config['items_path']) ? $config['items_path'] : '';
                    $items = $this->extract_items($response, $items_path);
                    $pagination['has_more'] = !empty($items) && count($items) >= $pagination['per_page'];
                }
                break;
                
            case 'cursor':
                // Extract next cursor from response
                $cursor_path = $pagination['params']['cursor_path'];
                $next_cursor = $this->extract_value_from_path($response, $cursor_path);
                
                $pagination['cursor'] = $next_cursor;
                $pagination['has_more'] = !empty($next_cursor);
                break;
                
            case 'link':
                // Check for Link header with rel="next"
                $link_header = wp_remote_retrieve_header($response, 'link');
                $next_url = $this->extract_next_url_from_link_header($link_header);
                
                $pagination['next_url'] = $next_url;
                $pagination['has_more'] = !empty($next_url);
                break;
                
            case 'custom':
                // Extract has_more flag from response
                $has_more_path = $pagination['params']['has_more_path'];
                $has_more = $this->extract_value_from_path($response, $has_more_path);
                
                // Extract next page info if available
                $next_page_path = $pagination['params']['next_page_path'];
                $next_page = $this->extract_value_from_path($response, $next_page_path);
                
                if (is_numeric($next_page)) {
                    $pagination['current_page'] = (int)$next_page;
                } else if (is_string($next_page) && filter_var($next_page, FILTER_VALIDATE_URL)) {
                    $pagination['next_url'] = $next_page;
                }
                
                $pagination['has_more'] = $has_more ? true : false;
                break;
        }
        
        return $pagination;
    }
    
    /**
     * Extract a value from a response using dot notation path
     * 
     * @param array $data Response data
     * @param string $path Path in dot notation (e.g., "meta.pagination.next")
     * @return mixed|null Extracted value or null if not found
     */
    private function extract_value_from_path($data, $path) {
        if (empty($path)) {
            return null;
        }
        
        $segments = explode('.', $path);
        $current = $data;
        
        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return null;
            }
            $current = $current[$segment];
        }
        
        return $current;
    }
    
    /**
     * Extract next URL from Link header
     * 
     * @param string $link_header Link header value
     * @return string|null Next URL or null if not found
     */
    private function extract_next_url_from_link_header($link_header) {
        if (empty($link_header)) {
            return null;
        }
        
        // Parse Link header
        $links = explode(',', $link_header);
        foreach ($links as $link) {
            if (strpos($link, 'rel="next"') !== false || strpos($link, "rel='next'") !== false) {
                preg_match('/<([^>]+)>/', $link, $matches);
                if (!empty($matches[1])) {
                    return $matches[1];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Update rate limiting state from response headers
     * 
     * @param array $response Response from wp_remote_get
     * @return void
     */
    private function update_rate_limit_state($response) {
        // Common rate limit headers
        $headers = [
            'remaining' => ['x-ratelimit-remaining', 'x-rate-limit-remaining', 'ratelimit-remaining'],
            'limit' => ['x-ratelimit-limit', 'x-rate-limit-limit', 'ratelimit-limit'],
            'reset' => ['x-ratelimit-reset', 'x-rate-limit-reset', 'ratelimit-reset']
        ];
        
        foreach ($headers as $state_key => $header_keys) {
            foreach ($header_keys as $header) {
                $value = wp_remote_retrieve_header($response, $header);
                if (!empty($value)) {
                    $this->rate_limit_state[$state_key] = is_numeric($value) ? (int)$value : $value;
                    break;
                }
            }
        }
        
        // Handle Retry-After header
        $retry_after = wp_remote_retrieve_header($response, 'retry-after');
        if (!empty($retry_after)) {
            $this->rate_limit_state['retry_after'] = is_numeric($retry_after) ? 
                time() + (int)$retry_after : 
                strtotime($retry_after);
        }
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
                
            case 'oauth2':
                try {
                    $token = $this->get_oauth_token($config);
                    if ($token) {
                        $args['headers']['Authorization'] = 'Bearer ' . $token;
                    }
                } catch (\Exception $e) {
                    error_log("OAuth2 authentication error: " . $e->getMessage());
                    // Continue without authentication
                }
                break;
                
            case 'digest':
                if (!empty($config['auth_username']) && !empty($config['auth_password'])) {
                    // WP_Http doesn't support digest auth directly, so we need to set it up
                    $args['headers']['Authorization'] = 'Digest username="' . $config['auth_username'] . '"';
                    $args['digest_auth'] = true;
                    $args['auth_username'] = $config['auth_username'];
                    $args['auth_password'] = $config['auth_password'];
                }
                break;
                
            case 'aws_sig4':
                if (!empty($config['aws_access_key']) && !empty($config['aws_secret_key'])) {
                    // AWS Signature v4 is complex and may require a dedicated library
                    // For now, log that this would require additional implementation
                    error_log("AWS Signature v4 authentication not implemented yet");
                }
                break;
        }
        
        return $args;
    }
    
    /**
     * Get OAuth 2.0 token (client credentials flow)
     * 
     * @param array $config Source configuration
     * @return string|null OAuth token or null on failure
     * @throws \Exception If token request fails
     */
    private function get_oauth_token($config) {
        $source_id = !empty($config['source_id']) ? $config['source_id'] : 'default';
        
        // Check if we already have a valid token
        if (!empty($this->oauth_tokens[$source_id]) && $this->oauth_tokens[$source_id]['expires'] > time()) {
            return $this->oauth_tokens[$source_id]['access_token'];
        }
        
        // Check required config
        if (empty($config['oauth_token_url']) || empty($config['oauth_client_id']) || empty($config['oauth_client_secret'])) {
            throw new \Exception("Missing required OAuth configuration");
        }
        
        // Prepare token request
        $token_url = $config['oauth_token_url'];
        $grant_type = !empty($config['oauth_grant_type']) ? $config['oauth_grant_type'] : 'client_credentials';
        
        $body = [
            'grant_type' => $grant_type,
            'client_id' => $config['oauth_client_id'],
            'client_secret' => $config['oauth_client_secret']
        ];
        
        // Add scope if present
        if (!empty($config['oauth_scope'])) {
            $body['scope'] = $config['oauth_scope'];
        }
        
        // Make token request
        $response = wp_remote_post($token_url, [
            'body' => $body,
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            throw new \Exception("OAuth token request failed: " . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new \Exception("OAuth token request failed with code {$response_code}: {$body}");
        }
        
        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['access_token'])) {
            throw new \Exception("OAuth token response missing access_token");
        }
        
        // Calculate expiration time
        $expires = time() + (!empty($data['expires_in']) ? (int)$data['expires_in'] : 3600);
        
        // Store token
        $this->oauth_tokens[$source_id] = [
            'access_token' => $data['access_token'],
            'expires' => $expires,
            'token_type' => !empty($data['token_type']) ? $data['token_type'] : 'Bearer'
        ];
        
        return $data['access_token'];
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
            'source_url' => !empty($config['source_url']) ? $config['source_url'] : null,
            'meta' => []
        ];
        
        // Support for nested properties using dot notation
        foreach ($mapping as $target => $source) {
            $value = $this->extract_value_from_path($item, $source);
            
            if ($value !== null) {
                if ($target === 'meta') {
                    if (is_array($value)) {
                        $mapped_item['meta'] = array_merge($mapped_item['meta'], $value);
                    }
                } else {
                    $mapped_item[$target] = $value;
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
        
        // Add additional metadata (source name, fetch time)
        $mapped_item['meta']['source_name'] = !empty($config['source_name']) ? $config['source_name'] : null;
        $mapped_item['meta']['fetch_time'] = current_time('mysql');
        
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
    
    /**
     * Get source configuration schema for admin UI
     * 
     * @return array Configuration schema
     */
    public static function get_config_schema() {
        return [
            'auth_type' => [
                'type' => 'select',
                'label' => 'Authentication Type',
                'options' => [
                    'none' => 'None',
                    'basic' => 'Basic Auth',
                    'bearer' => 'Bearer Token',
                    'api_key' => 'API Key',
                    'oauth2' => 'OAuth 2.0',
                    'digest' => 'HTTP Digest',
                    'aws_sig4' => 'AWS Signature v4'
                ],
                'default' => 'none'
            ],
            'auth_username' => [
                'type' => 'text',
                'label' => 'Username',
                'depends_on' => ['auth_type' => ['basic', 'digest']]
            ],
            'auth_password' => [
                'type' => 'password',
                'label' => 'Password',
                'depends_on' => ['auth_type' => ['basic', 'digest']]
            ],
            'auth_token' => [
                'type' => 'password',
                'label' => 'Token',
                'depends_on' => ['auth_type' => ['bearer']]
            ],
            'auth_key_name' => [
                'type' => 'text',
                'label' => 'API Key Name',
                'depends_on' => ['auth_type' => ['api_key']]
            ],
            'auth_key_value' => [
                'type' => 'password',
                'label' => 'API Key Value',
                'depends_on' => ['auth_type' => ['api_key']]
            ],
            'auth_key_in' => [
                'type' => 'select',
                'label' => 'API Key Location',
                'options' => ['header' => 'Header', 'query' => 'Query Parameter'],
                'default' => 'header',
                'depends_on' => ['auth_type' => ['api_key']]
            ],
            'oauth_token_url' => [
                'type' => 'text',
                'label' => 'Token URL',
                'depends_on' => ['auth_type' => ['oauth2']]
            ],
            'oauth_client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'depends_on' => ['auth_type' => ['oauth2']]
            ],
            'oauth_client_secret' => [
                'type' => 'password',
                'label' => 'Client Secret',
                'depends_on' => ['auth_type' => ['oauth2']]
            ],
            'oauth_grant_type' => [
                'type' => 'select',
                'label' => 'Grant Type',
                'options' => [
                    'client_credentials' => 'Client Credentials',
                    'password' => 'Password',
                    'authorization_code' => 'Authorization Code'
                ],
                'default' => 'client_credentials',
                'depends_on' => ['auth_type' => ['oauth2']]
            ],
            'pagination_strategy' => [
                'type' => 'select',
                'label' => 'Pagination Strategy',
                'options' => [
                    'none' => 'None',
                    'page' => 'Page Number',
                    'offset' => 'Offset',
                    'cursor' => 'Cursor',
                    'link' => 'Link Header',
                    'custom' => 'Custom'
                ],
                'default' => 'none'
            ],
            'per_page' => [
                'type' => 'number',
                'label' => 'Items Per Page',
                'default' => 20,
                'depends_on' => ['pagination_strategy' => ['page', 'offset']]
            ],
            'items_path' => [
                'type' => 'text',
                'label' => 'Items Path',
                'description' => 'Path to items in response (dot notation)',
                'default' => ''
            ],
            'field_mapping' => [
                'type' => 'mapping',
                'label' => 'Field Mapping',
                'description' => 'Map API fields to content fields',
                'fields' => [
                    'title' => 'Title',
                    'content' => 'Content',
                    'url' => 'URL',
                    'publish_date' => 'Publication Date',
                    'image' => 'Image URL',
                    'summary' => 'Summary',
                    'author' => 'Author'
                ]
            ],
            'content_type' => [
                'type' => 'select',
                'label' => 'Content Type',
                'options' => [
                    'article' => 'Article',
                    'podcast' => 'Podcast',
                    'twitter' => 'Twitter Post',
                    'reddit' => 'Reddit Post',
                    'custom' => 'Custom'
                ],
                'default' => 'article'
            ],
            'date_format' => [
                'type' => 'text',
                'label' => 'Date Format',
                'description' => 'Format of dates in API response (e.g., Y-m-d\\TH:i:sP)',
                'default' => ''
            ]
        ];
    }
} 