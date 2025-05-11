<?php
/**
 * @file-marker ASAP_Digest_RSSAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-rss-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * Enhanced RSS/Atom feed adapter for the content crawler.
 * Uses SimplePie to parse feeds with robust error handling and extended features.
 */
class RSSAdapter implements ContentSourceAdapter {
    /**
     * @var int Maximum items to fetch from a feed
     */
    private $max_items = 50;
    
    /**
     * @var int Cache duration in seconds
     */
    private $cache_duration = 3600; // 1 hour
    
    /**
     * @var bool Whether to attempt feed autodiscovery
     */
    private $enable_autodiscovery = true;
    
    /**
     * @var bool Whether to attempt feed format detection
     */
    private $detect_feed_format = true;
    
    /**
     * @var array Supported feed formats
     */
    private $supported_formats = [
        'rss' => 'RSS',
        'atom' => 'Atom',
        'rdf' => 'RDF/RSS 1.0',
        'rss1' => 'RSS 1.0',
        'rss2' => 'RSS 2.0',
    ];
    
    /**
     * @var array Date formats to try when parsing publication dates
     */
    private $date_formats = [
        'Y-m-d\TH:i:sP', // ISO 8601 with timezone
        'Y-m-d\TH:i:s\Z', // ISO 8601 UTC
        'Y-m-d H:i:s',    // MySQL format
        'D, d M Y H:i:s O', // RFC 2822
        'D, d M Y H:i:s',  // RFC 2822 without timezone
        'Y-m-d',          // Just date
    ];
    
    /**
     * Constructor
     * 
     * @param array $options Optional configuration options
     */
    public function __construct($options = []) {
        if (isset($options['max_items'])) {
            $this->max_items = (int)$options['max_items'];
        }
        
        if (isset($options['cache_duration'])) {
            $this->cache_duration = (int)$options['cache_duration'];
        }
        
        if (isset($options['enable_autodiscovery'])) {
            $this->enable_autodiscovery = (bool)$options['enable_autodiscovery'];
        }
        
        if (isset($options['detect_feed_format'])) {
            $this->detect_feed_format = (bool)$options['detect_feed_format'];
        }
        
        if (isset($options['date_formats']) && is_array($options['date_formats'])) {
            $this->date_formats = array_merge($this->date_formats, $options['date_formats']);
        }
    }
    
    /**
     * Fetch content from an RSS/Atom feed source
     * 
     * @param object $source Source object with URL and configuration
     * @return array Array of content items
     * @throws \Exception If feed cannot be fetched or parsed
     */
    public function fetch_content($source) {
        // Load SimplePie if not already loaded
        if (!class_exists('SimplePie')) {
            require_once(ABSPATH . WPINC . '/class-simplepie.php');
        }
        
        // Create a new SimplePie instance
        $feed = new \SimplePie();
        
        // Set feed URL
        $feed->set_feed_url($source->url);
        
        // Set cache settings
        $feed->set_cache_duration($this->cache_duration);
        $feed->enable_cache(true);
        
        // Set autodiscovery option
        $feed->enable_autodiscovery($this->enable_autodiscovery);
        
        // Set other options
        $feed->set_timeout(20); // Increase timeout for slow feeds
        $feed->force_feed(false); // Don't force feed parsing if not valid
        $feed->set_stupidly_fast(false); // More thorough parsing
        $feed->enable_order_by_date(true); // Order by date
        $feed->set_sanitize_class('SimplePie_Sanitize'); // Use default sanitizer
        
        // Try to initialize the feed
        try {
            $success = $feed->init();
            
            if (!$success) {
                throw new \Exception("Failed to initialize feed: " . $feed->error());
            }
            
            // Detect feed format if enabled
            if ($this->detect_feed_format) {
                $format = $this->detect_format($feed);
                error_log("Feed format detected: {$format} for {$source->url}");
            }
            
            // Get feed items
            $feed_items = $feed->get_items(0, $this->max_items);
            if (empty($feed_items)) {
                return []; // Empty feed, but not an error
            }
            
            // Process items
            $items = [];
            foreach ($feed_items as $feed_item) {
                $item = $this->process_feed_item($feed_item, $source);
                if ($item) {
                    $items[] = $item;
                }
            }
            
            // Log success
            $count = count($items);
            error_log("Successfully processed {$count} items from feed: {$source->url}");
            
            return $items;
            
        } catch (\Exception $e) {
            // Enhanced error handling
            $error_message = "Feed error ({$source->url}): " . $e->getMessage();
            error_log($error_message);
            
            // Try autodiscovery as a fallback if not already enabled
            if (!$this->enable_autodiscovery) {
                try {
                    error_log("Attempting feed autodiscovery fallback for {$source->url}");
                    $feed = new \SimplePie();
                    $feed->set_feed_url($source->url);
                    $feed->enable_autodiscovery(true);
                    $feed->set_cache_duration($this->cache_duration);
                    $feed->enable_cache(true);
                    
                    if ($feed->init()) {
                        $feed_items = $feed->get_items(0, $this->max_items);
                        if (!empty($feed_items)) {
                            $items = [];
                            foreach ($feed_items as $feed_item) {
                                $item = $this->process_feed_item($feed_item, $source);
                                if ($item) {
                                    $items[] = $item;
                                }
                            }
                            
                            $count = count($items);
                            error_log("Autodiscovery fallback succeeded with {$count} items from {$source->url}");
                            return $items;
                        }
                    }
                } catch (\Exception $fallback_e) {
                    error_log("Autodiscovery fallback failed: " . $fallback_e->getMessage());
                }
            }
            
            // If we get here, all attempts failed
            throw new \Exception($error_message, 0, $e);
        }
    }
    
    /**
     * Detect feed format
     * 
     * @param \SimplePie $feed SimplePie feed object
     * @return string Feed format name
     */
    private function detect_format($feed) {
        $format = 'unknown';
        
        // Check for RSS
        if (stripos($feed->get_type(), 'rss') !== false) {
            $format = 'rss';
            $version = $feed->get_version();
            if (stripos($version, '2.0') !== false) {
                $format = 'rss2';
            } elseif (stripos($version, '1.0') !== false) {
                $format = 'rss1';
            }
        }
        // Check for Atom
        elseif (stripos($feed->get_type(), 'atom') !== false) {
            $format = 'atom';
        }
        // Check for RDF
        elseif (stripos($feed->get_type(), 'rdf') !== false) {
            $format = 'rdf';
        }
        
        return $format;
    }
    
    /**
     * Process a single feed item
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @param object $source Source object
     * @return array|null Processed item or null if invalid
     */
    private function process_feed_item($feed_item, $source) {
        // Get basic item data
        $title = $feed_item->get_title();
        $content = $feed_item->get_content();
        $permalink = $feed_item->get_permalink();
        
        // Skip if missing essential data
        if (empty($title) || empty($content) || empty($permalink)) {
            return null;
        }
        
        // Create item array
        $item = [
            'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
            'content' => $content,
            'url' => $permalink,
            'source_url' => $source->url,
            'publish_date' => $this->extract_publication_date($feed_item),
            'type' => 'article', // Default type for RSS items
            'meta' => [
                'feed_id' => $feed_item->get_id(),
                'feed_title' => $feed_item->get_feed()->get_title(),
                'author' => $this->get_author($feed_item),
                'categories' => $this->get_categories($feed_item),
                'language' => $this->detect_language($feed_item),
            ]
        ];
        
        // Extract image if available
        $image = $this->extract_image($feed_item);
        if ($image) {
            $item['image'] = $image;
        }
        
        // Extract summary if available
        $summary = $feed_item->get_description();
        if ($summary && $summary !== $content) {
            $item['summary'] = html_entity_decode(strip_tags($summary), ENT_QUOTES, 'UTF-8');
        }
        
        // Extract media attachments (podcasts, videos, etc.)
        $media = $this->extract_media($feed_item);
        if (!empty($media)) {
            $item['media'] = $media;
        }
        
        // Apply custom source-specific processing
        $item = $this->apply_source_customizations($item, $source);
        
        return $item;
    }
    
    /**
     * Enhanced publication date extraction
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return string|null Publication date in MySQL format (Y-m-d H:i:s)
     */
    private function extract_publication_date($feed_item) {
        // Try the built-in SimplePie date
        $date = $feed_item->get_date('Y-m-d H:i:s');
        if ($date) {
            return $date;
        }
        
        // If that fails, try alternate date elements
        $date_str = null;
        
        // Try updated date (Atom)
        if (method_exists($feed_item, 'get_updated_date')) {
            $date_str = $feed_item->get_updated_date();
        }
        
        // Try Dublin Core date
        if (!$date_str && method_exists($feed_item, 'get_item_tags')) {
            // Dublin Core
            $dc_date = $feed_item->get_item_tags('http://purl.org/dc/elements/1.1/', 'date');
            if ($dc_date && isset($dc_date[0]['data'])) {
                $date_str = $dc_date[0]['data'];
            }
            
            // Try published date (Atom)
            if (!$date_str) {
                $published = $feed_item->get_item_tags('http://www.w3.org/2005/Atom', 'published');
                if ($published && isset($published[0]['data'])) {
                    $date_str = $published[0]['data'];
                }
            }
        }
        
        // Try to parse the date string if we found one
        if ($date_str) {
            // Try each format in our list
            foreach ($this->date_formats as $format) {
                $parsed_date = \DateTime::createFromFormat($format, $date_str);
                if ($parsed_date) {
                    return $parsed_date->format('Y-m-d H:i:s');
                }
            }
            
            // Fallback to strtotime
            $timestamp = strtotime($date_str);
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        // Last resort: use current time
        return current_time('mysql');
    }
    
    /**
     * Extract author information from feed item
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return string|null Author name or null
     */
    private function get_author($feed_item) {
        $author = $feed_item->get_author();
        if ($author) {
            // Try name first
            $name = $author->get_name();
            if ($name) {
                return $name;
            }
            
            // Try email if name is missing
            $email = $author->get_email();
            if ($email) {
                return $email;
            }
        }
        
        // Try Dublin Core creator
        if (method_exists($feed_item, 'get_item_tags')) {
            $dc_creator = $feed_item->get_item_tags('http://purl.org/dc/elements/1.1/', 'creator');
            if ($dc_creator && isset($dc_creator[0]['data'])) {
                return $dc_creator[0]['data'];
            }
        }
        
        return null;
    }
    
    /**
     * Extract categories from feed item
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return array Categories
     */
    private function get_categories($feed_item) {
        $categories = [];
        
        $cats = $feed_item->get_categories();
        if ($cats) {
            foreach ($cats as $cat) {
                $cat_label = $cat->get_label();
                if ($cat_label) {
                    $categories[] = $cat_label;
                } elseif ($cat->get_term()) {
                    $categories[] = $cat->get_term();
                }
            }
        }
        
        // Try Dublin Core subjects as fallback
        if (empty($categories) && method_exists($feed_item, 'get_item_tags')) {
            $dc_subjects = $feed_item->get_item_tags('http://purl.org/dc/elements/1.1/', 'subject');
            if ($dc_subjects) {
                foreach ($dc_subjects as $subject) {
                    if (isset($subject['data'])) {
                        $categories[] = $subject['data'];
                    }
                }
            }
        }
        
        return array_unique($categories);
    }
    
    /**
     * Detect content language
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return string|null Detected language code or null
     */
    private function detect_language($feed_item) {
        // Try to get language from feed
        $feed = $feed_item->get_feed();
        if ($feed) {
            $language = $feed->get_language();
            if ($language) {
                return $language;
            }
        }
        
        // Try Dublin Core language
        if (method_exists($feed_item, 'get_item_tags')) {
            $dc_language = $feed_item->get_item_tags('http://purl.org/dc/elements/1.1/', 'language');
            if ($dc_language && isset($dc_language[0]['data'])) {
                return $dc_language[0]['data'];
            }
        }
        
        // Default to English if we can't detect
        return 'en';
    }
    
    /**
     * Extract image from feed item
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return string|null Image URL or null
     */
    private function extract_image($feed_item) {
        // Try media:thumbnail first (common in RSS 2.0 feeds)
        if (method_exists($feed_item, 'get_item_tags')) {
            $media_thumbnail = $feed_item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
            if ($media_thumbnail && isset($media_thumbnail[0]['attribs']['']['url'])) {
                return $media_thumbnail[0]['attribs']['']['url'];
            }
            
            // Try media:content with image type
            $media_content = $feed_item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
            if ($media_content) {
                foreach ($media_content as $content) {
                    if (isset($content['attribs']['']['type']) && 
                        strpos($content['attribs']['']['type'], 'image/') === 0 &&
                        isset($content['attribs']['']['url'])) {
                        return $content['attribs']['']['url'];
                    }
                }
            }
        }
        
        // Try enclosures
        $enclosures = $feed_item->get_enclosures();
        if ($enclosures) {
            foreach ($enclosures as $enclosure) {
                if (strpos($enclosure->get_type(), 'image/') === 0) {
                    return $enclosure->get_link();
                }
            }
        }
        
        // Try to find image in content
        $content = $feed_item->get_content();
        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract media attachments from feed item
     * 
     * @param \SimplePie_Item $feed_item Feed item
     * @return array Media items (audio, video, etc.)
     */
    private function extract_media($feed_item) {
        $media = [];
        
        // Check enclosures (common for podcasts)
        $enclosures = $feed_item->get_enclosures();
        if ($enclosures) {
            foreach ($enclosures as $enclosure) {
                $type = $enclosure->get_type();
                $length = $enclosure->get_length();
                $url = $enclosure->get_link();
                
                if ($url && $type && $type !== 'image') {
                    $media_type = strpos($type, 'audio/') === 0 ? 'audio' : 
                                 (strpos($type, 'video/') === 0 ? 'video' : 'other');
                    
                    $media[] = [
                        'url' => $url,
                        'type' => $media_type,
                        'mime' => $type,
                        'length' => $length,
                        'title' => $enclosure->get_title() ?: null,
                    ];
                }
            }
        }
        
        // Check for media:content tags (if available)
        if (method_exists($feed_item, 'get_item_tags')) {
            $media_content = $feed_item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
            if ($media_content) {
                foreach ($media_content as $content) {
                    if (isset($content['attribs']['']['url']) && 
                        isset($content['attribs']['']['type']) && 
                        (strpos($content['attribs']['']['type'], 'audio/') === 0 || 
                         strpos($content['attribs']['']['type'], 'video/') === 0)) {
                        
                        $media_type = strpos($content['attribs']['']['type'], 'audio/') === 0 ? 'audio' : 'video';
                        
                        $media[] = [
                            'url' => $content['attribs']['']['url'],
                            'type' => $media_type,
                            'mime' => $content['attribs']['']['type'],
                            'length' => $content['attribs']['']['fileSize'] ?? null,
                            'duration' => $content['attribs']['']['duration'] ?? null,
                            'title' => $content['attribs']['']['title'] ?? null,
                        ];
                    }
                }
            }
        }
        
        return $media;
    }
    
    /**
     * Apply source-specific customizations
     * 
     * @param array $item Item data
     * @param object $source Source object
     * @return array Modified item
     */
    private function apply_source_customizations($item, $source) {
        // Get source config
        $config = is_string($source->config) ? maybe_unserialize($source->config) : $source->config;
        
        if (!is_array($config)) {
            $config = [];
        }
        
        // Custom content type
        if (!empty($config['content_type'])) {
            $item['type'] = $config['content_type'];
        }
        
        // Custom author handling
        if (!empty($config['author_overwrite'])) {
            $item['meta']['author'] = $config['author_overwrite'];
        }
        
        // Custom language
        if (!empty($config['language'])) {
            $item['meta']['language'] = $config['language'];
        }
        
        // Custom content cleaning
        if (!empty($config['content_selector'])) {
            // Use a DOM parser to extract only the part of the content
            // that matches the selector
            if (class_exists('DOMDocument')) {
                try {
                    $dom = new \DOMDocument();
                    @$dom->loadHTML(mb_convert_encoding($item['content'], 'HTML-ENTITIES', 'UTF-8'));
                    $xpath = new \DOMXPath($dom);
                    $nodes = $xpath->query($config['content_selector']);
                    if ($nodes->length > 0) {
                        $item['content'] = $dom->saveHTML($nodes->item(0));
                    }
                } catch (\Exception $e) {
                    error_log("DOM parsing error: " . $e->getMessage());
                }
            }
        }
        
        // Custom content cleaning rules
        if (!empty($config['remove_patterns']) && is_array($config['remove_patterns'])) {
            foreach ($config['remove_patterns'] as $pattern) {
                $item['content'] = preg_replace($pattern, '', $item['content']);
            }
        }
        
        return $item;
    }
} 