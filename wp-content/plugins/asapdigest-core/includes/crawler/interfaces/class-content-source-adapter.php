<?php
/**
 * @file-marker ASAP_Digest_ContentSourceAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/interfaces/class-content-source-adapter.php
 */

namespace ASAPDigest\Crawler\Interfaces;

/**
 * Interface for content source adapters.
 * All adapters must implement this interface to be used by the crawler.
 */
interface ContentSourceAdapter {
    /**
     * Fetch content from a source
     * 
     * @param object $source Source object from database
     * @return array Array of content items
     * @throws \Exception If content cannot be fetched
     */
    public function fetch_content($source);
} 