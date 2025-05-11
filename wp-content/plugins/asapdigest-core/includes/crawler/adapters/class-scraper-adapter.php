<?php
/**
 * @file-marker ASAP_Digest_ScraperAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-scraper-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * Enhanced web scraper adapter for the content crawler.
 * Extracts content from web pages using configurable DOM/CSS selectors with
 * content cleaning, error handling, and advanced extraction capabilities.
 * 
 * @since 1.0.0
 * @updated 1.1.0 - Added configurable selectors, advanced extraction, and content cleaning
 */
class ScraperAdapter implements ContentSourceAdapter {
    /**
     * @var int Request timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * @var array Default request headers
     */
    private $default_headers = [
        'User-Agent' => 'ASAP Digest Content Crawler/1.0',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
    ];
    
    /**
     * @var array Supported selector types
     */
    private $supported_selector_types = [
        'xpath',     // XPath selectors (//div[@class="content"])
        'css',       // CSS selectors (div.content)
        'json',      // JSON path selectors (data.articles[0].title)
        'regex',     // Regular expression (/<title>(.*?)<\/title>/s)
        'schema'     // Schema.org extraction (title, description, image, etc.)
    ];
    
    /**
     * @var array Supported content cleaning options
     */
    private $content_cleaning_options = [
        'remove_scripts',      // Remove <script> tags
        'remove_styles',       // Remove <style> tags
        'remove_comments',     // Remove HTML comments
        'remove_empty_tags',   // Remove empty tags
        'normalize_whitespace',// Normalize whitespace
        'fix_encoding',        // Fix character encoding
        'sanitize_html',       // Sanitize HTML (WordPress sanitization)
        'remove_attributes',   // Remove specified attributes
        'extract_text_only'    // Extract text content only (no HTML)
    ];
    
    /**
     * @var array Supported authentication types
     */
    private $supported_auth_types = [
        'basic',    // Basic HTTP authentication (username/password)
        'cookie',   // Cookie-based authentication
        'header'    // Custom header authentication
    ];
    
    /**
     * @var bool Enable debug logging for scraper operations
     */
    private $debug_logging = false;
    
    /**
     * @var array Default field selectors (when no config is provided)
     */
    private $default_selectors = [
        'xpath' => [
            'title' => '//title',
            'content' => '//article | //main | //div[@class="content"] | //div[@id="content"] | //div[@role="main"]',
            'image' => '//meta[@property="og:image"]/@content | //link[@rel="image_src"]/@href',
            'publish_date' => '//meta[@property="article:published_time"]/@content | //time[1]/@datetime',
            'author' => '//meta[@name="author"]/@content | //meta[@property="article:author"]/@content',
            'summary' => '//meta[@name="description"]/@content | //meta[@property="og:description"]/@content'
        ],
        'css' => [
            'title' => 'title',
            'content' => 'article, main, .content, #content, [role="main"]',
            'image' => 'meta[property="og:image"], link[rel="image_src"]',
            'publish_date' => 'time',
            'author' => 'meta[name="author"], .author',
            'summary' => 'meta[name="description"], meta[property="og:description"]'
        ]
    ];
    
    /**
     * @var array Schema.org types and properties to extract
     */
    private $schema_mappings = [
        'Article' => [
            'title' => 'headline',
            'content' => 'articleBody',
            'publish_date' => 'datePublished',
            'author' => 'author.name',
            'image' => 'image',
            'summary' => 'description'
        ],
        'BlogPosting' => [
            'title' => 'headline',
            'content' => 'articleBody',
            'publish_date' => 'datePublished',
            'author' => 'author.name',
            'image' => 'image',
            'summary' => 'description'
        ],
        'NewsArticle' => [
            'title' => 'headline',
            'content' => 'articleBody',
            'publish_date' => 'datePublished',
            'author' => 'author.name',
            'image' => 'image',
            'summary' => 'description'
        ]
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
        
        if (isset($options['debug_logging'])) {
            $this->debug_logging = (bool)$options['debug_logging'];
        }
    }
    
    /**
     * Fetch content from a webpage source
     * 
     * @param object $source Source object
     * @return array Array of content items
     * @throws \Exception If webpage cannot be accessed or parsed
     */
    public function fetch_content($source) {
        try {
        // Get source configuration
            $config = is_string($source->config) ? maybe_unserialize($source->config) : $source->config;
            
            if (!is_array($config)) {
                $config = [];
            }
        
        // Check requirements
        if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
            throw new \Exception("DOM extension is required for web scraping");
        }
            
            // Normalize the configuration
            $config = $this->normalize_config($config);
            
            // Debug log
            if ($this->debug_logging) {
                error_log("ScraperAdapter: Fetching content from {$source->url} with selector type: {$config['selector_type']}");
        }
        
        // Prepare request arguments
        $args = [
            'timeout' => $this->timeout,
            'headers' => $this->get_request_headers($config),
                'sslverify' => true,
                'user-agent' => !empty($config['user_agent']) ? $config['user_agent'] : $this->default_headers['User-Agent']
        ];
        
        // Add authentication if configured
        if (!empty($config['auth_type'])) {
            $args = $this->add_authentication($args, $config);
        }
        
        // Make the request
        $response = wp_remote_get($source->url, $args);
        
            // Process the response and handle errors
            $html = $this->process_response($response, $source->url);
            
            // Check if we should try to render JavaScript
            if (!empty($config['render_javascript']) && $config['render_javascript']) {
                $html = $this->render_javascript($html, $source->url, $config);
            }
            
            // Handle different content types
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            
            // If it's JSON
            if (strpos($content_type, 'application/json') !== false) {
                return $this->process_json_content($html, $config, $source->url);
            }
            
            // Otherwise, process as HTML
            return $this->process_html_content($html, $config, $source->url);
            
        } catch (\Exception $e) {
            if ($this->debug_logging) {
                error_log("ScraperAdapter Error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Normalize and validate configuration with defaults
     * 
     * @param array $config Source configuration
     * @return array Normalized configuration
     */
    private function normalize_config($config) {
        // Set default selector type
        if (empty($config['selector_type']) || !in_array($config['selector_type'], $this->supported_selector_types)) {
            $config['selector_type'] = 'xpath'; // Default to XPath
        }
        
        // Set default field selectors based on selector type if not specified
        $selector_key = ($config['selector_type'] === 'css') ? 'css' : 'xpath';
        
        foreach ($this->default_selectors[$selector_key] as $field => $default_selector) {
            $selector_key = $field . '_selector';
            if (empty($config[$selector_key])) {
                $config[$selector_key] = $default_selector;
            }
        }
        
        // Initialize content cleaning options
        if (!isset($config['content_cleaning'])) {
            $config['content_cleaning'] = [
                'remove_scripts' => true,
                'remove_styles' => true,
                'remove_comments' => true,
                'normalize_whitespace' => true
            ];
        }
        
        // Default content type
        if (empty($config['content_type'])) {
            $config['content_type'] = 'article';
        }
        
        return $config;
    }
    
    /**
     * Process API response, handling errors and validation
     * 
     * @param array|\WP_Error $response Response from wp_remote_get
     * @param string $url The requested URL (for error messages)
     * @return string Processed HTML content
     * @throws \Exception If there are errors with the response
     */
    private function process_response($response, $url) {
        // Check for request errors
        if (is_wp_error($response)) {
            throw new \Exception("Web request failed for {$url}: " . $response->get_error_message());
        }
        
        // Check HTTP status code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new \Exception("Webpage {$url} returned error code: {$response_code}");
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new \Exception("Webpage {$url} returned empty response");
        }
        
        return $body;
    }
    
    /**
     * Process HTML content using the appropriate parser
     * 
     * @param string $html Raw HTML content
     * @param array $config Scraper configuration
     * @param string $base_url Base URL for resolving relative URLs
     * @return array Array of content items
     */
    private function process_html_content($html, $config, $base_url) {
        // Create DOM document
        $dom = $this->create_dom_document($html);
        
        // Check whether to extract Schema.org data
        if ($config['selector_type'] === 'schema') {
            return $this->extract_schema_data($dom, $config, $base_url);
        }
        
        // For XPath selectors
        if ($config['selector_type'] === 'xpath') {
        $xpath = new \DOMXPath($dom);
        
        // Check if we're scraping multiple items or just the page itself
        if (!empty($config['item_selector'])) {
            // Scrape multiple items from the page
                return $this->scrape_multiple_items_xpath($xpath, $config, $base_url);
        } else {
            // Scrape the page as a single item
                return [$this->scrape_single_page_xpath($xpath, $config, $base_url)];
            }
        }
        
        // For CSS selectors
        if ($config['selector_type'] === 'css') {
            // Check if we're scraping multiple items or just the page itself
            if (!empty($config['item_selector'])) {
                // Scrape multiple items from the page
                return $this->scrape_multiple_items_css($dom, $config, $base_url);
            } else {
                // Scrape the page as a single item
                return [$this->scrape_single_page_css($dom, $config, $base_url)];
            }
        }
        
        // For regex selectors
        if ($config['selector_type'] === 'regex') {
            return $this->extract_with_regex($html, $config, $base_url);
        }
        
        // Default: return empty array if no supported selector type
        return [];
    }
    
    /**
     * Create a DOMDocument from HTML content
     * 
     * @param string $html HTML content
     * @return \DOMDocument The created DOMDocument
     */
    private function create_dom_document($html) {
        $dom = new \DOMDocument();
        
        // Suppress errors from malformed HTML
        $previous_value = libxml_use_internal_errors(true);
        
        // Clean up HTML by ensuring proper UTF-8 encoding
        $clean_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        
        // Add a basic HTML structure if it doesn't exist
        if (strpos($clean_html, '<html') === false) {
            $clean_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $clean_html . '</body></html>';
        }
        
        // Load HTML
        @$dom->loadHTML($clean_html);
        
        // Reset error handling
        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);
        
        return $dom;
    }
    
    /**
     * Process JSON content
     * 
     * @param string $json_str JSON content string
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array Array of extracted content items
     * @throws \Exception If JSON cannot be parsed
     */
    private function process_json_content($json_str, $config, $base_url) {
        // Parse JSON
        $data = json_decode($json_str, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse JSON: " . json_last_error_msg());
        }
        
        // Extract items using JSON path
        $items = [];
        
        if (!empty($config['item_selector'])) {
            // For multiple items
            $json_path = $config['item_selector'];
            $extracted_items = $this->extract_json_path($data, $json_path);
            
            if (is_array($extracted_items)) {
                foreach ($extracted_items as $item_data) {
                    $mapped_item = $this->map_json_item($item_data, $config, $base_url);
                    if ($mapped_item) {
                        $items[] = $mapped_item;
                    }
                }
            }
        } else {
            // For single item
            $mapped_item = $this->map_json_item($data, $config, $base_url);
            if ($mapped_item) {
                $items[] = $mapped_item;
            }
        }
        
        return $items;
    }
    
    /**
     * Extract data using a JSON path
     * 
     * @param array $data JSON data array
     * @param string $path JSON path (dot notation with array syntax)
     * @return mixed Extracted data
     */
    private function extract_json_path($data, $path) {
        if (empty($path)) {
            return $data;
        }
        
        // Handle array index notation: data.items[0].title
        if (preg_match('/^(.*?)\[(\d+)\](.*)$/', $path, $matches)) {
            $prefix = $matches[1];
            $index = (int)$matches[2];
            $suffix = $matches[3];
            
            $array_data = $this->extract_json_path($data, $prefix);
            
            if (is_array($array_data) && isset($array_data[$index])) {
                if (empty($suffix)) {
                    return $array_data[$index];
                } else {
                    return $this->extract_json_path($array_data[$index], ltrim($suffix, '.'));
                }
            }
            
            return null;
        }
        
        // Standard dot notation
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
     * Map a JSON item to the standard content format
     * 
     * @param array $item_data JSON item data
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array|null Mapped content item or null if invalid
     */
    private function map_json_item($item_data, $config, $base_url) {
        $fields = [
            'title' => !empty($config['title_selector']) ? $config['title_selector'] : '',
            'content' => !empty($config['content_selector']) ? $config['content_selector'] : '',
            'url' => !empty($config['url_selector']) ? $config['url_selector'] : '',
            'image' => !empty($config['image_selector']) ? $config['image_selector'] : '',
            'publish_date' => !empty($config['date_selector']) ? $config['date_selector'] : '',
            'author' => !empty($config['author_selector']) ? $config['author_selector'] : '',
            'summary' => !empty($config['summary_selector']) ? $config['summary_selector'] : '',
        ];
        
        $item = [
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => []
        ];
        
        // Extract each field using JSON path
        foreach ($fields as $field => $json_path) {
            if (empty($json_path)) {
                continue;
            }
            
            $value = $this->extract_json_path($item_data, $json_path);
            if ($value !== null) {
                if ($field === 'url' && !empty($value)) {
                    $item[$field] = $this->resolve_url($value, $base_url);
                } elseif ($field === 'image' && !empty($value)) {
                    $item[$field] = $this->resolve_url($value, $base_url);
                } else {
                    $item[$field] = $value;
                }
            }
        }
        
        // If no URL found, use base URL
        if (empty($item['url'])) {
            $item['url'] = $base_url;
        }
        
        // Skip if missing essential data
        if (empty($item['title'])) {
            return null;
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        return $item;
    }
    
    /**
     * Extract Schema.org structured data from a webpage
     * 
     * @param \DOMDocument $dom DOM document
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array Array of extracted content items
     */
    private function extract_schema_data($dom, $config, $base_url) {
        $items = [];
        $xpath = new \DOMXPath($dom);
        
        // Look for JSON-LD schema data
        $jsonld_nodes = $xpath->query('//script[@type="application/ld+json"]');
        if ($jsonld_nodes && $jsonld_nodes->length > 0) {
            foreach ($jsonld_nodes as $node) {
                $json = $node->textContent;
                try {
                    $data = json_decode($json, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Handle @graph array
                        if (isset($data['@graph']) && is_array($data['@graph'])) {
                            foreach ($data['@graph'] as $graph_item) {
                                $mapped_item = $this->map_schema_item($graph_item, $config, $base_url);
                                if ($mapped_item) {
                                    $items[] = $mapped_item;
                                }
                            }
                        } else {
                            $mapped_item = $this->map_schema_item($data, $config, $base_url);
                            if ($mapped_item) {
                                $items[] = $mapped_item;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    if ($this->debug_logging) {
                        error_log("ScraperAdapter: Error parsing JSON-LD: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Look for microdata
        if (empty($items)) {
            // TODO: Implement microdata extraction if needed
            // For now, fall back to standard XPath extraction
            return [$this->scrape_single_page_xpath(new \DOMXPath($dom), $config, $base_url)];
        }
        
        return $items;
    }
    
    /**
     * Map Schema.org item to the standard content format
     * 
     * @param array $schema_item Schema.org data
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array|null Mapped content item or null if invalid
     */
    private function map_schema_item($schema_item, $config, $base_url) {
        // Only process certain types (Article, BlogPosting, NewsArticle, etc.)
        $type = isset($schema_item['@type']) ? $schema_item['@type'] : '';
        
        // Handle array of types
        if (is_array($type)) {
            foreach ($type as $t) {
                if (isset($this->schema_mappings[$t])) {
                    $type = $t;
                    break;
                }
            }
            if (is_array($type)) {
                $type = reset($type); // Just use the first type if none matched
            }
        }
        
        if (!isset($this->schema_mappings[$type])) {
            return null;
        }
        
        $mapping = $this->schema_mappings[$type];
        $item = [
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => [
                'schema_type' => $type
            ]
        ];
        
        // Map standard fields from schema
        foreach ($mapping as $field => $schema_property) {
            $value = $this->extract_schema_property($schema_item, $schema_property);
            if ($value !== null) {
                if ($field === 'url' && !empty($value)) {
                    $item[$field] = $this->resolve_url($value, $base_url);
                } elseif ($field === 'image' && !empty($value)) {
                    // Handle image which might be an object or string
                    if (is_array($value) && isset($value['url'])) {
                        $item[$field] = $this->resolve_url($value['url'], $base_url);
                    } else {
                        $item[$field] = $this->resolve_url($value, $base_url);
                    }
                } else {
                    $item[$field] = $value;
                }
            }
        }
        
        // If no URL found, check for schema URL or use base URL
        if (empty($item['url'])) {
            $url = $this->extract_schema_property($schema_item, 'url');
            $item['url'] = $url ? $this->resolve_url($url, $base_url) : $base_url;
        }
        
        // Skip if missing essential data
        if (empty($item['title'])) {
            return null;
        }
        
        return $item;
    }
    
    /**
     * Extract a property from a Schema.org item (handles nested properties)
     * 
     * @param array $schema_item Schema.org data
     * @param string $property Property path (dot notation)
     * @return mixed Property value or null if not found
     */
    private function extract_schema_property($schema_item, $property) {
        if (empty($property)) {
            return null;
        }
        
        // Handle dot notation for nested properties
        if (strpos($property, '.') !== false) {
            $segments = explode('.', $property);
            $current = $schema_item;
            
            foreach ($segments as $segment) {
                if (!isset($current[$segment])) {
                    return null;
                }
                $current = $current[$segment];
            }
            
            return $current;
        }
        
        // Simple property
        return isset($schema_item[$property]) ? $schema_item[$property] : null;
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
                
            case 'cookie':
                if (!empty($config['cookies']) && is_array($config['cookies'])) {
                    $cookies = [];
                    foreach ($config['cookies'] as $name => $value) {
                        $cookies[] = $name . '=' . $value;
                    }
                    $args['headers']['Cookie'] = implode('; ', $cookies);
                }
                break;
                
            case 'header':
                if (!empty($config['auth_header_name']) && !empty($config['auth_header_value'])) {
                    $args['headers'][$config['auth_header_name']] = $config['auth_header_value'];
                }
                break;
        }
        
        return $args;
    }
    
    /**
     * Scrape multiple items from a page using XPath
     * 
     * @param \DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL for resolving relative links
     * @return array Array of content items
     */
    private function scrape_multiple_items_xpath($xpath, $config, $base_url) {
        $items = [];
        
        try {
        $nodes = $xpath->query($config['item_selector']);
        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                    // Create a context-specific XPath for this node
                    $nodeXpath = new \DOMXPath($node->ownerDocument);
                    
                    $item = $this->extract_item_with_xpath($node, $nodeXpath, $config, $base_url);
                if ($item) {
                    $items[] = $item;
                }
                }
            }
        } catch (\Exception $e) {
            if ($this->debug_logging) {
                error_log("ScraperAdapter: Error in XPath extraction: " . $e->getMessage());
            }
        }
        
        return $items;
    }
    
    /**
     * Extract a content item from a DOM node using XPath
     * 
     * @param \DOMNode $context_node Node to use as context
     * @param \DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL for resolving relative links
     * @return array|null Content item or null if invalid
     */
    private function extract_item_with_xpath($context_node, $xpath, $config, $base_url) {
        $fields = [
            'title' => !empty($config['title_selector']) ? $config['title_selector'] : '',
            'content' => !empty($config['content_selector']) ? $config['content_selector'] : '',
            'url' => !empty($config['url_selector']) ? $config['url_selector'] : '',
            'image' => !empty($config['image_selector']) ? $config['image_selector'] : '',
            'publish_date' => !empty($config['date_selector']) ? $config['date_selector'] : '',
            'author' => !empty($config['author_selector']) ? $config['author_selector'] : '',
            'summary' => !empty($config['summary_selector']) ? $config['summary_selector'] : '',
        ];
        
        $item = [
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => []
        ];
        
        // Extract each field using XPath
        foreach ($fields as $field => $selector) {
            if (empty($selector)) {
                continue;
            }
            
            try {
                // Use the context node for the XPath query
                $fieldNodes = $xpath->query($selector, $context_node);
            if ($fieldNodes && $fieldNodes->length > 0) {
                    // For URL field, handle differently
                if ($field === 'url') {
                        // Try href attribute first, then src, then text content
                        $node = $fieldNodes->item(0);
                        if ($node instanceof \DOMElement) {
                            $href = $node->getAttribute('href');
                            if ($href) {
                                $item[$field] = $this->resolve_url($href, $base_url);
                } else {
                                $src = $node->getAttribute('src');
                                if ($src) {
                                    $item[$field] = $this->resolve_url($src, $base_url);
                                } else {
                                    $item[$field] = $node->textContent;
                                }
                            }
                        } else {
                            // Handle attribute nodes or text nodes
                            $item[$field] = $node->nodeValue;
                        }
                    } 
                    // For image field, get src attribute
                    elseif ($field === 'image') {
                        $node = $fieldNodes->item(0);
                        if ($node instanceof \DOMElement) {
                            $src = $node->getAttribute('src');
                            if ($src) {
                                $item[$field] = $this->resolve_url($src, $base_url);
                            } else {
                                // Try content attribute for meta tags
                                $content = $node->getAttribute('content');
                                if ($content) {
                                    $item[$field] = $this->resolve_url($content, $base_url);
                                }
                            }
                        } else if ($node instanceof \DOMAttr) {
                            // If it's an attribute node (e.g., from a query like @content)
                            $item[$field] = $this->resolve_url($node->nodeValue, $base_url);
                        } else {
                            // For text or other nodes
                            $item[$field] = $this->resolve_url($node->nodeValue, $base_url);
                        }
                    } 
                    // For content field, optionally get innerHTML
                    elseif ($field === 'content') {
                        if (!empty($config['use_inner_html'])) {
                        $html = '';
                        $children = $fieldNodes->item(0)->childNodes;
                        foreach ($children as $child) {
                                $html .= $context_node->ownerDocument->saveHTML($child);
                            }
                            // Apply content cleaning if configured
                            $item[$field] = $this->clean_content($html, $config);
                        } else {
                            $item[$field] = $this->clean_content($fieldNodes->item(0)->textContent, $config);
                        }
                    } 
                    // For other fields, get text content
                    else {
                        $node = $fieldNodes->item(0);
                        // Check for attribute value first for meta tags
                        if ($node instanceof \DOMElement) {
                            $attr_content = $node->getAttribute('content');
                            if ($attr_content) {
                                $item[$field] = $attr_content;
                            } else {
                                $item[$field] = $node->textContent;
                            }
                            
                            // For dates, also check datetime attribute
                            if ($field === 'publish_date' && empty($item[$field])) {
                                $datetime = $node->getAttribute('datetime');
                                if ($datetime) {
                                    $item[$field] = $datetime;
                                }
                            }
                        } else if ($node instanceof \DOMAttr) {
                            // If it's an attribute node (e.g., from a query like @content)
                            $item[$field] = $node->nodeValue;
                        } else {
                            // For text or other nodes
                            $item[$field] = $node->nodeValue;
                        }
                    }
                }
            } catch (\Exception $e) {
                if ($this->debug_logging) {
                    error_log("ScraperAdapter: Error extracting {$field} with XPath: " . $e->getMessage());
                }
            }
        }
        
        // If no URL found, use base URL
        if (empty($item['url'])) {
            $item['url'] = $base_url;
        }
        
        // Skip if missing essential data
        if (empty($item['title'])) {
            return null;
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        // Extract additional meta fields if configured
        if (!empty($config['meta_selectors']) && is_array($config['meta_selectors'])) {
            foreach ($config['meta_selectors'] as $meta_key => $meta_selector) {
                try {
                    $metaNodes = $xpath->query($meta_selector, $context_node);
                    if ($metaNodes && $metaNodes->length > 0) {
                        $item['meta'][$meta_key] = $metaNodes->item(0)->textContent;
                    }
                } catch (\Exception $e) {
                    // Ignore errors in meta extraction
                }
            }
        }
        
        return $item;
    }
    
    /**
     * Scrape a single page as a content item using XPath
     * 
     * @param \DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL
     * @return array Content item
     */
    private function scrape_single_page_xpath($xpath, $config, $base_url) {
        $fields = [
            'title' => !empty($config['title_selector']) ? $config['title_selector'] : '//title',
            'content' => !empty($config['content_selector']) ? $config['content_selector'] : '//body',
            'image' => !empty($config['image_selector']) ? $config['image_selector'] : '//meta[@property="og:image"]/@content',
            'publish_date' => !empty($config['date_selector']) ? $config['date_selector'] : '//meta[@property="article:published_time"]/@content',
            'author' => !empty($config['author_selector']) ? $config['author_selector'] : '//meta[@name="author"]/@content',
            'summary' => !empty($config['summary_selector']) ? $config['summary_selector'] : '//meta[@name="description"]/@content',
        ];
        
        $item = [
            'url' => $base_url,
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => []
        ];
        
        // Extract each field
        foreach ($fields as $field => $selector) {
            try {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                if ($field === 'content' && !empty($config['use_inner_html'])) {
                    $html = '';
                    $children = $nodes->item(0)->childNodes;
                    foreach ($children as $child) {
                        $html .= $nodes->item(0)->ownerDocument->saveHTML($child);
                    }
                        $item[$field] = $this->clean_content($html, $config);
                    } elseif ($field === 'image') {
                        // Handle image URL resolution
                        $src = $nodes->item(0)->textContent;
                        if ($src) {
                            $item[$field] = $this->resolve_url($src, $base_url);
                        }
                } else {
                    $item[$field] = $nodes->item(0)->textContent;
                    }
                }
            } catch (\Exception $e) {
                if ($this->debug_logging) {
                    error_log("ScraperAdapter: Error extracting {$field} from page: " . $e->getMessage());
                }
            }
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        // Extract additional meta fields if configured
        if (!empty($config['meta_selectors']) && is_array($config['meta_selectors'])) {
            foreach ($config['meta_selectors'] as $meta_key => $meta_selector) {
                try {
                    $metaNodes = $xpath->query($meta_selector);
                    if ($metaNodes && $metaNodes->length > 0) {
                        $item['meta'][$meta_key] = $metaNodes->item(0)->textContent;
                    }
                } catch (\Exception $e) {
                    // Ignore errors in meta extraction
                }
            }
        }
        
        return $item;
    }
    
    /**
     * Scrape multiple items from a page using CSS selectors
     * 
     * @param \DOMDocument $dom DOM document
     * @param array $config Source configuration
     * @param string $base_url Base URL for resolving relative links
     * @return array Array of content items
     */
    private function scrape_multiple_items_css($dom, $config, $base_url) {
        $items = [];
        
        // Convert CSS selector to XPath (simplified approach)
        $item_xpath = $this->css_to_xpath($config['item_selector']);
        
        // Create XPath object
        $xpath = new \DOMXPath($dom);
        
        // Execute query
        try {
            $nodes = $xpath->query($item_xpath);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    // Convert all CSS selectors to XPath
                    $field_selectors = [];
                    foreach (['title', 'content', 'url', 'image', 'publish_date', 'author', 'summary'] as $field) {
                        $selector_key = $field . '_selector';
                        if (!empty($config[$selector_key])) {
                            $field_selectors[$field] = $this->css_to_xpath($config[$selector_key]);
                        }
                    }
                    
                    // Create temporary config with XPath selectors
                    $xpath_config = array_merge($config, $field_selectors);
                    $xpath_config['selector_type'] = 'xpath';
                    
                    // Extract item using XPath methods
                    $nodeXpath = new \DOMXPath($node->ownerDocument);
                    $item = $this->extract_item_with_xpath($node, $nodeXpath, $xpath_config, $base_url);
                    if ($item) {
                        $items[] = $item;
                    }
                }
            }
        } catch (\Exception $e) {
            if ($this->debug_logging) {
                error_log("ScraperAdapter: Error in CSS selector extraction: " . $e->getMessage());
            }
        }
        
        return $items;
    }
    
    /**
     * Scrape a single page as a content item using CSS selectors
     * 
     * @param \DOMDocument $dom DOM document
     * @param array $config Source configuration
     * @param string $base_url Base URL
     * @return array Content item
     */
    private function scrape_single_page_css($dom, $config, $base_url) {
        // Convert all CSS selectors to XPath
        $field_selectors = [];
        foreach (['title', 'content', 'url', 'image', 'publish_date', 'author', 'summary'] as $field) {
            $selector_key = $field . '_selector';
            if (!empty($config[$selector_key])) {
                $field_selectors[$field . '_selector'] = $this->css_to_xpath($config[$selector_key]);
            }
        }
        
        // Create temporary config with XPath selectors
        $xpath_config = array_merge($config, $field_selectors);
        $xpath_config['selector_type'] = 'xpath';
        
        // Use XPath method to extract data
        return $this->scrape_single_page_xpath(new \DOMXPath($dom), $xpath_config, $base_url);
    }
    
    /**
     * Extract content using regular expressions
     * 
     * @param string $html HTML content
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array Array of extracted content items
     */
    private function extract_with_regex($html, $config, $base_url) {
        $items = [];
        
        // If item selector is provided, extract multiple items
        if (!empty($config['item_selector'])) {
            $pattern = $config['item_selector'];
            preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $item = $this->extract_item_with_regex($match, $config, $base_url);
                    if ($item) {
                        $items[] = $item;
                    }
                }
            }
        } else {
            // Extract a single item
            $item = $this->extract_single_page_with_regex($html, $config, $base_url);
            if ($item) {
                $items[] = $item;
            }
        }
        
        return $items;
    }
    
    /**
     * Extract a content item using regex matches
     * 
     * @param array $matches Regex match array
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array|null Content item or null if invalid
     */
    private function extract_item_with_regex($matches, $config, $base_url) {
        $item = [
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => []
        ];
        
        // Fields to extract
        $fields = ['title', 'content', 'url', 'image', 'publish_date', 'author', 'summary'];
        
        // Named capture groups approach
        foreach ($fields as $field) {
            if (isset($matches[$field])) {
                $value = trim($matches[$field]);
                
                if ($field === 'url' || $field === 'image') {
                    $item[$field] = $this->resolve_url($value, $base_url);
                } elseif ($field === 'content') {
                    $item[$field] = $this->clean_content($value, $config);
                } else {
                    $item[$field] = $value;
                }
            }
        }
        
        // Numbered capture groups approach (fallback)
        if (empty($item['title']) && count($matches) > 1) {
            // Assume the first capture group is the title
            $item['title'] = trim($matches[1]);
            
            // If we have more capture groups, try to map them
            if (count($matches) > 2) {
                $item['content'] = !empty($matches[2]) ? $this->clean_content(trim($matches[2]), $config) : '';
            }
            if (count($matches) > 3) {
                $item['url'] = !empty($matches[3]) ? $this->resolve_url(trim($matches[3]), $base_url) : $base_url;
            }
        }
        
        // If no URL, use base URL
        if (empty($item['url'])) {
            $item['url'] = $base_url;
        }
        
        // Skip if missing essential data
        if (empty($item['title'])) {
            return null;
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        return $item;
    }
    
    /**
     * Extract content from a single page using regex
     * 
     * @param string $html HTML content
     * @param array $config Scraper configuration
     * @param string $base_url Base URL
     * @return array|null Content item or null if extraction fails
     */
    private function extract_single_page_with_regex($html, $config, $base_url) {
        $item = [
            'url' => $base_url,
            'type' => !empty($config['content_type']) ? $config['content_type'] : 'article',
            'source_url' => $base_url,
            'meta' => []
        ];
        
        // Fields to extract
        $fields = ['title', 'content', 'image', 'publish_date', 'author', 'summary'];
        
        // Try to extract each field with its regex
        foreach ($fields as $field) {
            $selector_key = $field . '_selector';
            if (!empty($config[$selector_key])) {
                $pattern = $config[$selector_key];
                if (preg_match($pattern, $html, $matches)) {
                    if (isset($matches[1])) {
                        $value = trim($matches[1]);
                        
                        if ($field === 'image') {
                            $item[$field] = $this->resolve_url($value, $base_url);
                        } elseif ($field === 'content') {
                            $item[$field] = $this->clean_content($value, $config);
                        } else {
                            $item[$field] = $value;
                        }
                    }
                }
            }
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        // If no title was found, try fallback selectors
        if (empty($item['title'])) {
            // Try <title> tag
            if (preg_match('/<title>(.*?)<\/title>/si', $html, $matches)) {
                $item['title'] = trim($matches[1]);
            }
            
            // Still no title? Try og:title
            if (empty($item['title']) && preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\'](.*?)["\']/si', $html, $matches)) {
                $item['title'] = trim($matches[1]);
            }
        }
        
        // Skip if still missing essential data
        if (empty($item['title'])) {
            return null;
        }
        
        return $item;
    }
    
    /**
     * Convert a CSS selector to XPath
     * 
     * @param string $css_selector CSS selector
     * @return string XPath selector
     */
    private function css_to_xpath($css_selector) {
        // Simple implementation for common cases
        // For a more complete solution, consider including a dedicated CSS-to-XPath library
        
        $xpath = $css_selector;
        
        // Handle ID selectors (#id)
        $xpath = preg_replace('/\#([a-zA-Z0-9_-]+)/', '*[@id=\'$1\']', $xpath);
        
        // Handle class selectors (.class)
        $xpath = preg_replace('/\.([a-zA-Z0-9_-]+)/', '*[contains(@class,\'$1\')]', $xpath);
        
        // Handle attribute selectors [attr=value]
        $xpath = preg_replace('/\[([a-zA-Z0-9_-]+)=([^\]]+)\]/', '[@$1=$2]', $xpath);
        
        // Handle attribute selectors [attr]
        $xpath = preg_replace('/\[([a-zA-Z0-9_-]+)\]/', '[@$1]', $xpath);
        
        // Handle direct child selectors (>)
        $xpath = preg_replace('/\s*>\s*/', '/', $xpath);
        
        // Handle descendant selectors (space)
        $xpath = preg_replace('/\s+/', '//', $xpath);
        
        // Ensure it starts with // if not already
        if (strpos($xpath, '/') !== 0) {
            $xpath = '//' . $xpath;
        }
        
        return $xpath;
    }
    
    /**
     * Clean HTML content according to configuration
     * 
     * @param string $content Raw HTML content
     * @param array $config Cleaning configuration
     * @return string Cleaned content
     */
    private function clean_content($content, $config) {
        if (empty($content)) {
            return '';
        }
        
        $cleaning_options = !empty($config['content_cleaning']) ? $config['content_cleaning'] : [];
        
        // Remove scripts
        if (!empty($cleaning_options['remove_scripts'])) {
            $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        }
        
        // Remove styles
        if (!empty($cleaning_options['remove_styles'])) {
            $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        }
        
        // Remove comments
        if (!empty($cleaning_options['remove_comments'])) {
            $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);
        }
        
        // Remove empty tags
        if (!empty($cleaning_options['remove_empty_tags'])) {
            $content = preg_replace('/<(\w+)[^>]*>\s*<\/\\1>/', '', $content);
        }
        
        // Normalize whitespace
        if (!empty($cleaning_options['normalize_whitespace'])) {
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
        }
        
        // Fix encoding
        if (!empty($cleaning_options['fix_encoding'])) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }
        
        // Sanitize HTML
        if (!empty($cleaning_options['sanitize_html'])) {
            $allowed_html = wp_kses_allowed_html('post');
            $content = wp_kses($content, $allowed_html);
        }
        
        // Remove specified attributes
        if (!empty($cleaning_options['remove_attributes']) && is_array($cleaning_options['remove_attributes'])) {
            if (class_exists('DOMDocument')) {
                // Use DOM to remove attributes
                $dom = new \DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                
                // Process each attribute to remove
                foreach ($cleaning_options['remove_attributes'] as $attr) {
                    $xpath = new \DOMXPath($dom);
                    $nodes = $xpath->query("//*[@{$attr}]");
                    
                    foreach ($nodes as $node) {
                        if ($node instanceof \DOMElement) {
                            $node->removeAttribute($attr);
                        }
                    }
                }
                
                $content = $dom->saveHTML();
            } else {
                // Fallback with regex (not as reliable)
                foreach ($cleaning_options['remove_attributes'] as $attr) {
                    $content = preg_replace('/\s' . preg_quote($attr) . '=([\'"])[^\'"]*\1/', '', $content);
                }
            }
        }
        
        // Extract text only (no HTML)
        if (!empty($cleaning_options['extract_text_only'])) {
            $content = strip_tags($content);
        }
        
        return $content;
    }
    
    /**
     * Resolve a relative URL against a base URL
     * 
     * @param string $url URL to resolve
     * @param string $base_url Base URL
     * @return string Resolved URL
     */
    private function resolve_url($url, $base_url) {
        if (empty($url)) {
            return $base_url;
        }
        
        // Already absolute URL
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        
        // Protocol-relative URL
        if (substr($url, 0, 2) === '//') {
            return parse_url($base_url, PHP_URL_SCHEME) . ':' . $url;
        }
        
        // Root-relative URL
        if ($url[0] === '/') {
            $parts = parse_url($base_url);
            return $parts['scheme'] . '://' . $parts['host'] . $url;
        }
        
        // Relative URL (preserving query string in base URL)
        $base_parts = parse_url($base_url);
        $base_path = isset($base_parts['path']) ? $base_parts['path'] : '/';
        $base_dir = dirname($base_path);
        if ($base_dir !== '/' && $base_dir !== '\\') {
            $base_dir .= '/';
        }
        
        // Handle ".." in URL
        $url_parts = explode('/', $url);
        $base_parts = explode('/', rtrim($base_dir, '/'));
        $result_parts = $base_parts;
        
        foreach ($url_parts as $part) {
            if ($part === '..') {
                array_pop($result_parts);
            } elseif ($part !== '.') {
                $result_parts[] = $part;
            }
        }
        
        $result_path = implode('/', $result_parts);
        
        return $base_parts['scheme'] . '://' . $base_parts['host'] . '/' . ltrim($result_path, '/');
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
            // Fallback to strtotime for common formats
            $timestamp = strtotime($date);
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        return $date;
    }
    
    /**
     * Render JavaScript in a page for advanced scraping (placeholder/stub)
     * 
     * @param string $html Original HTML
     * @param string $url URL being scraped
     * @param array $config Configuration
     * @return string Rendered HTML
     */
    private function render_javascript($html, $url, $config) {
        // This is a stub method where JS rendering would be implemented
        // Real implementation would use an external service (e.g., Puppeteer, Playwright)
        
        if ($this->debug_logging) {
            error_log("ScraperAdapter: JavaScript rendering requested for {$url} but not implemented");
        }
        
        // Log that this feature requires an external service
        if (!empty($config['js_renderer_url'])) {
            if ($this->debug_logging) {
                error_log("ScraperAdapter: Attempting to use external JS renderer at {$config['js_renderer_url']}");
            }
            
            // Example integration with an external rendering service
            // This would need to be implemented according to the actual service
            try {
                $request_body = json_encode([
                    'url' => $url,
                    'waitForSelector' => !empty($config['wait_for_selector']) ? $config['wait_for_selector'] : null,
                    'waitTime' => !empty($config['wait_time']) ? $config['wait_time'] : 5000
                ]);
                
                $response = wp_remote_post($config['js_renderer_url'], [
                    'timeout' => max($this->timeout * 2, 60),
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $request_body
                ]);
                
                if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    
                    if (isset($data['html'])) {
                        return $data['html'];
                    }
                }
            } catch (\Exception $e) {
                if ($this->debug_logging) {
                    error_log("ScraperAdapter: Error using external JS renderer: " . $e->getMessage());
                }
            }
        }
        
        return $html; // Return original HTML if rendering fails
    }
    
    /**
     * Get source configuration schema for admin UI
     * 
     * @return array Configuration schema
     */
    public static function get_config_schema() {
        return [
            'selector_type' => [
                'type' => 'select',
                'label' => 'Selector Type',
                'options' => [
                    'xpath' => 'XPath (Advanced)',
                    'css' => 'CSS Selector (Simple)',
                    'regex' => 'Regular Expression',
                    'json' => 'JSON Path (for API responses)',
                    'schema' => 'Schema.org Data'
                ],
                'default' => 'xpath',
                'description' => 'Type of selector to use for extracting content'
            ],
            'item_selector' => [
                'type' => 'text',
                'label' => 'Item Selector',
                'description' => 'Selector to find multiple items on the page (leave empty to scrape the whole page as one item)',
                'depends_on' => ['selector_type' => ['xpath', 'css', 'regex']]
            ],
            'title_selector' => [
                'type' => 'text',
                'label' => 'Title Selector',
                'default' => '//title',
                'description' => 'Selector to extract the title'
            ],
            'content_selector' => [
                'type' => 'text',
                'label' => 'Content Selector',
                'default' => '//article | //main',
                'description' => 'Selector to extract the main content'
            ],
            'url_selector' => [
                'type' => 'text',
                'label' => 'URL Selector',
                'description' => 'Selector to extract the URL (optional, uses page URL if empty)'
            ],
            'image_selector' => [
                'type' => 'text',
                'label' => 'Image Selector',
                'default' => '//meta[@property="og:image"]/@content',
                'description' => 'Selector to extract the main image'
            ],
            'date_selector' => [
                'type' => 'text',
                'label' => 'Date Selector',
                'default' => '//meta[@property="article:published_time"]/@content',
                'description' => 'Selector to extract the publication date'
            ],
            'author_selector' => [
                'type' => 'text',
                'label' => 'Author Selector',
                'default' => '//meta[@name="author"]/@content',
                'description' => 'Selector to extract the author'
            ],
            'summary_selector' => [
                'type' => 'text',
                'label' => 'Summary Selector',
                'default' => '//meta[@name="description"]/@content',
                'description' => 'Selector to extract the summary'
            ],
            'use_inner_html' => [
                'type' => 'checkbox',
                'label' => 'Use Inner HTML',
                'default' => true,
                'description' => 'If checked, extracts HTML content rather than just text'
            ],
            'date_format' => [
                'type' => 'text',
                'label' => 'Date Format',
                'description' => 'Format of dates in source (e.g., Y-m-d\\TH:i:sP)',
                'default' => ''
            ],
            'content_cleaning' => [
                'type' => 'multicheck',
                'label' => 'Content Cleaning Options',
                'options' => [
                    'remove_scripts' => 'Remove Scripts',
                    'remove_styles' => 'Remove Styles',
                    'remove_comments' => 'Remove Comments',
                    'remove_empty_tags' => 'Remove Empty Tags',
                    'normalize_whitespace' => 'Normalize Whitespace',
                    'fix_encoding' => 'Fix Encoding',
                    'sanitize_html' => 'Sanitize HTML',
                    'extract_text_only' => 'Extract Text Only (No HTML)'
                ],
                'default' => [
                    'remove_scripts' => true,
                    'remove_styles' => true,
                    'remove_comments' => true,
                    'normalize_whitespace' => true
                ],
                'description' => 'Options for cleaning extracted content'
            ],
            'auth_type' => [
                'type' => 'select',
                'label' => 'Authentication Type',
                'options' => [
                    'none' => 'None',
                    'basic' => 'Basic Auth',
                    'cookie' => 'Cookie Auth',
                    'header' => 'Custom Header'
                ],
                'default' => 'none',
                'description' => 'Type of authentication to use'
            ],
            'auth_username' => [
                'type' => 'text',
                'label' => 'Username',
                'depends_on' => ['auth_type' => ['basic']]
            ],
            'auth_password' => [
                'type' => 'password',
                'label' => 'Password',
                'depends_on' => ['auth_type' => ['basic']]
            ],
            'auth_header_name' => [
                'type' => 'text',
                'label' => 'Header Name',
                'depends_on' => ['auth_type' => ['header']]
            ],
            'auth_header_value' => [
                'type' => 'text',
                'label' => 'Header Value',
                'depends_on' => ['auth_type' => ['header']]
            ],
            'cookies' => [
                'type' => 'keyvalue',
                'label' => 'Cookies',
                'depends_on' => ['auth_type' => ['cookie']],
                'description' => 'Cookie name-value pairs'
            ],
            'headers' => [
                'type' => 'keyvalue',
                'label' => 'Custom Headers',
                'description' => 'Additional HTTP headers to send with requests'
            ],
            'user_agent' => [
                'type' => 'text',
                'label' => 'User Agent',
                'default' => 'ASAP Digest Content Crawler/1.0',
                'description' => 'Custom User-Agent header'
            ],
            'render_javascript' => [
                'type' => 'checkbox',
                'label' => 'Render JavaScript',
                'default' => false,
                'description' => 'Attempt to render JavaScript (requires configuration)'
            ],
            'js_renderer_url' => [
                'type' => 'text',
                'label' => 'JavaScript Renderer URL',
                'depends_on' => ['render_javascript' => true],
                'description' => 'URL of an external service for JavaScript rendering'
            ],
            'wait_for_selector' => [
                'type' => 'text',
                'label' => 'Wait for Selector',
                'depends_on' => ['render_javascript' => true],
                'description' => 'Wait until this selector appears before capturing content'
            ],
            'wait_time' => [
                'type' => 'number',
                'label' => 'Wait Time (ms)',
                'default' => 5000,
                'depends_on' => ['render_javascript' => true],
                'description' => 'Maximum time to wait for JavaScript rendering (milliseconds)'
            ],
            'content_type' => [
                'type' => 'select',
                'label' => 'Content Type',
                'options' => [
                    'article' => 'Article',
                    'podcast' => 'Podcast',
                    'twitter' => 'Twitter Post',
                    'reddit' => 'Reddit Post',
                    'event' => 'Event',
                    'custom' => 'Custom'
                ],
                'default' => 'article',
                'description' => 'Type of content being scraped'
            ],
            'meta_selectors' => [
                'type' => 'mapping',
                'label' => 'Additional Meta Selectors',
                'description' => 'Extract additional fields into meta data'
            ]
        ];
    }
} 