<?php
/**
 * @file-marker ASAP_Digest_ScraperAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-scraper-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * Web scraper adapter for the content crawler.
 * Extracts content from web pages using DOM selectors.
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
     * Fetch content from a webpage source
     * 
     * @param object $source Source object
     * @return array Array of content items
     * @throws \Exception If webpage cannot be accessed or parsed
     */
    public function fetch_content($source) {
        // Get source configuration
        $config = maybe_unserialize($source->config);
        
        // Check requirements
        if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
            throw new \Exception("DOM extension is required for web scraping");
        }
        
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
            throw new \Exception("Web request failed: " . $response->get_error_message());
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new \Exception("Webpage returned error code: " . $response_code);
        }
        
        // Get response body
        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            throw new \Exception("Webpage returned empty response");
        }
        
        // Load HTML into DOM
        $dom = new \DOMDocument();
        
        // Suppress errors from malformed HTML
        $previous_value = libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors($previous_value);
        
        $xpath = new \DOMXPath($dom);
        
        // Check if we're scraping multiple items or just the page itself
        if (!empty($config['item_selector'])) {
            // Scrape multiple items from the page
            return $this->scrape_multiple_items($xpath, $config, $source->url);
        } else {
            // Scrape the page as a single item
            return [$this->scrape_single_page($xpath, $config, $source->url)];
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
                
            case 'cookie':
                if (!empty($config['cookies']) && is_array($config['cookies'])) {
                    $cookies = [];
                    foreach ($config['cookies'] as $name => $value) {
                        $cookies[] = $name . '=' . $value;
                    }
                    $args['headers']['Cookie'] = implode('; ', $cookies);
                }
                break;
        }
        
        return $args;
    }
    
    /**
     * Scrape multiple items from a page
     * 
     * @param DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL for resolving relative links
     * @return array Array of content items
     */
    private function scrape_multiple_items($xpath, $config, $base_url) {
        $items = [];
        
        $nodes = $xpath->query($config['item_selector']);
        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                $item = $this->extract_item_from_node($node, $xpath, $config, $base_url);
                if ($item) {
                    $items[] = $item;
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Extract a content item from a DOM node
     * 
     * @param DOMNode $node Node to extract from
     * @param DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL for resolving relative links
     * @return array|null Content item or null if invalid
     */
    private function extract_item_from_node($node, $xpath, $config, $base_url) {
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
            'meta' => []
        ];
        
        // Create a new XPath context for this node
        $nodeXpath = new \DOMXPath($node->ownerDocument);
        
        // Extract each field
        foreach ($fields as $field => $selector) {
            if (empty($selector)) {
                continue;
            }
            
            // Use the node as context for the XPath query
            $fieldNodes = $nodeXpath->query($selector, $node);
            if ($fieldNodes && $fieldNodes->length > 0) {
                if ($field === 'url') {
                    // For URLs, get href attribute or text content
                    $href = $fieldNodes->item(0)->getAttribute('href');
                    $item[$field] = $href ? $this->resolve_url($href, $base_url) : $fieldNodes->item(0)->textContent;
                } elseif ($field === 'image') {
                    // For images, get src attribute
                    $src = $fieldNodes->item(0)->getAttribute('src');
                    $item[$field] = $src ? $this->resolve_url($src, $base_url) : '';
                } else {
                    // For other fields, get text content or inner HTML
                    $item[$field] = $fieldNodes->item(0)->textContent;
                    
                    // For content, get innerHTML if needed
                    if ($field === 'content' && !empty($config['use_inner_html'])) {
                        $html = '';
                        $children = $fieldNodes->item(0)->childNodes;
                        foreach ($children as $child) {
                            $html .= $node->ownerDocument->saveHTML($child);
                        }
                        $item[$field] = $html;
                    }
                }
            }
        }
        
        // If no URL found, use base URL
        if (empty($item['url'])) {
            $item['url'] = $base_url;
        }
        
        // Skip if missing essential data
        if (empty($item['title']) || empty($item['url'])) {
            return null;
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        return $item;
    }
    
    /**
     * Scrape a single page as a content item
     * 
     * @param DOMXPath $xpath XPath object
     * @param array $config Source configuration
     * @param string $base_url Base URL
     * @return array Content item
     */
    private function scrape_single_page($xpath, $config, $base_url) {
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
            'meta' => []
        ];
        
        // Extract each field
        foreach ($fields as $field => $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                if ($field === 'content' && !empty($config['use_inner_html'])) {
                    $html = '';
                    $children = $nodes->item(0)->childNodes;
                    foreach ($children as $child) {
                        $html .= $nodes->item(0)->ownerDocument->saveHTML($child);
                    }
                    $item[$field] = $html;
                } else {
                    $item[$field] = $nodes->item(0)->textContent;
                }
            }
        }
        
        // Format date if needed
        if (!empty($item['publish_date']) && !empty($config['date_format'])) {
            $item['publish_date'] = $this->format_date($item['publish_date'], $config['date_format']);
        }
        
        return $item;
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
        
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        
        if (substr($url, 0, 2) === '//') {
            return parse_url($base_url, PHP_URL_SCHEME) . ':' . $url;
        }
        
        if ($url[0] === '/') {
            $parts = parse_url($base_url);
            return $parts['scheme'] . '://' . $parts['host'] . $url;
        }
        
        $base = dirname($base_url);
        return $base . '/' . $url;
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