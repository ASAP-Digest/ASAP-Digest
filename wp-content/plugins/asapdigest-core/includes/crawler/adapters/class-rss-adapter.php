<?php
/**
 * @file-marker ASAP_Digest_RSSAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/adapters/class-rss-adapter.php
 */

namespace AsapDigest\Crawler\Adapters;

use AsapDigest\Crawler\Interfaces\ContentSourceAdapter;

/**
 * RSS/Atom feed adapter for the content crawler.
 * Uses SimplePie to parse feeds.
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
    }
    
    /**
     * Fetch content from an RSS/Atom feed source
     * 
     * @param object $source Source object
     * @return array Array of content items
     * @throws \Exception If feed cannot be fetched or parsed
     */
    public function fetch_content($source) {
        if (!class_exists('SimplePie')) {
            require_once(ABSPATH . WPINC . '/class-simplepie.php');
        }
        
        // Create a new SimplePie instance
        $feed = new \SimplePie();
        $feed->set_feed_url($source->url);
        $feed->set_cache_duration($this->cache_duration);
        $feed->enable_cache(true);
        
        // Try to get the feed
        $success = $feed->init();
        if (!$success) {
            throw new \Exception("Failed to fetch feed: " . $feed->error());
        }
        
        // Get feed items
        $feed_items = $feed->get_items(0, $this->max_items);
        if (empty($feed_items)) {
            return [];
        }
        
        // Process items
        $items = [];
        foreach ($feed_items as $feed_item) {
            $item = $this->process_feed_item($feed_item, $source);
            if ($item) {
                $items[] = $item;
            }
        }
        
        return $items;
    }
    
    /**
     * Process a single feed item
     * 
     * @param SimplePie_Item $feed_item Feed item
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
            'publish_date' => $feed_item->get_date('Y-m-d H:i:s'),
            'type' => 'article', // Default type for RSS items
            'meta' => [
                'feed_id' => $feed_item->get_id(),
                'feed_title' => $feed_item->get_feed()->get_title(),
                'author' => $this->get_author($feed_item),
                'categories' => $this->get_categories($feed_item),
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
        
        // Apply custom source-specific processing
        $item = $this->apply_source_customizations($item, $source);
        
        return $item;
    }
    
    /**
     * Extract author information from feed item
     * 
     * @param SimplePie_Item $feed_item Feed item
     * @return string|null Author name or null
     */
    private function get_author($feed_item) {
        $author = $feed_item->get_author();
        if ($author) {
            return $author->get_name();
        }
        return null;
    }
    
    /**
     * Extract categories from feed item
     * 
     * @param SimplePie_Item $feed_item Feed item
     * @return array Categories
     */
    private function get_categories($feed_item) {
        $categories = [];
        
        $cats = $feed_item->get_categories();
        if ($cats) {
            foreach ($cats as $cat) {
                $categories[] = $cat->get_label();
            }
        }
        
        return $categories;
    }
    
    /**
     * Extract image from feed item
     * 
     * @param SimplePie_Item $feed_item Feed item
     * @return string|null Image URL or null
     */
    private function extract_image($feed_item) {
        // Try to find image in enclosures
        $enclosures = $feed_item->get_enclosures();
        if ($enclosures) {
            foreach ($enclosures as $enclosure) {
                if ($enclosure->get_medium() === 'image') {
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
     * Apply source-specific customizations
     * 
     * @param array $item Item data
     * @param object $source Source object
     * @return array Modified item
     */
    private function apply_source_customizations($item, $source) {
        // Get source config
        $config = maybe_unserialize($source->config);
        
        // Custom content type
        if (!empty($config['content_type'])) {
            $item['type'] = $config['content_type'];
        }
        
        // Custom author handling
        if (!empty($config['author_overwrite'])) {
            $item['meta']['author'] = $config['author_overwrite'];
        }
        
        // Custom content cleaning
        if (!empty($config['content_selector'])) {
            // This would use a DOM parser to extract only the part of the content
            // that matches the selector. A simplified example:
            if (class_exists('DOMDocument')) {
                $dom = new \DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($item['content'], 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query($config['content_selector']);
                if ($nodes->length > 0) {
                    $item['content'] = $dom->saveHTML($nodes->item(0));
                }
            }
        }
        
        return $item;
    }
} 