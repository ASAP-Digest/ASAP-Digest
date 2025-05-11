<?php
/**
 * Content Processor Class
 * 
 * Handles content processing, normalization, deduplication, and enrichment.
 *
 * @package ASAPDigest_Core
 * @created 05.20.25 | 09:35 AM PDT
 * @file-marker ASAP_Digest_ContentProcessor
 * @location /wp-content/plugins/asapdigest-core/includes/class-content-processor.php
 */

namespace AsapDigest\Crawler;

/**
 * Class ContentProcessor
 * 
 * Processes ingested content through various filters and enrichment steps.
 *
 * @since 2.3.0
 */
class ContentProcessor {
    /**
     * Content storage instance
     *
     * @var ContentStorage
     */
    private $storage;
    
    /**
     * AI client for content enrichment
     *
     * @var mixed
     */
    private $ai_client;
    
    /**
     * Entity extraction service
     *
     * @var mixed
     */
    private $entity_extractor;
    
    /**
     * Duplicate detection service
     *
     * @var mixed
     */
    private $duplicate_detector;
    
    /**
     * Media processing service
     *
     * @var mixed
     */
    private $media_processor;
    
    /**
     * Whether to use the enhanced content processing pipeline
     *
     * @var bool
     */
    private $use_enhanced_pipeline;

    /**
     * Constructor
     *
     * @param ContentStorage $storage Content storage instance
     */
    public function __construct($storage) {
        $this->storage = $storage;
        // Stubs: inject real services as needed
        $this->ai_client = null;
        $this->entity_extractor = null;
        $this->duplicate_detector = null;
        $this->media_processor = null;
        
        // Determine if we should use the enhanced content processing pipeline
        $this->use_enhanced_pipeline = defined('ASAP_USE_ENHANCED_QUALITY_SCORING') && ASAP_USE_ENHANCED_QUALITY_SCORING;
    }

    /**
     * Process a single content item: normalize, deduplicate, enrich, store
     *
     * @since 2.3.0
     * 
     * @param array $item Raw content item to process
     * @return int|false Content ID or false on rejection
     */
    public function process_item($item) {
        $start_time = microtime(true);
        
        // If enhanced pipeline is enabled, use it for processing
        if ($this->use_enhanced_pipeline) {
            return $this->process_item_enhanced($item);
        }

        // Initial filter (stub: always true)
        if (!$this->passes_initial_filter($item)) {
            /**
             * Fires when content is rejected by the initial filter
             *
             * @since 2.3.0
             *
             * @param array  $item   The content item that was rejected
             * @param string $reason The reason for rejection ('initial_filter')
             */
            do_action('asapdigest_content_rejected', $item, 'initial_filter');
            return false;
        }

        // Normalize content
        $normalized = $this->normalize_content($item);

        // Duplicate detection (stub: always false)
        if ($this->duplicate_detector && $this->duplicate_detector->is_duplicate($normalized)) {
            /**
             * Fires when content is rejected as a duplicate
             *
             * @since 2.3.0
             *
             * @param array  $normalized The normalized content item that was rejected
             * @param string $reason     The reason for rejection ('duplicate')
             */
            do_action('asapdigest_content_rejected', $normalized, 'duplicate');
            return false;
        }

        // Language check (stub: only 'en')
        $language = $this->detect_language($normalized['content'] ?? '');
        if ($language !== 'en') {
            /**
             * Fires when content is rejected due to language
             *
             * @since 2.3.0
             *
             * @param array  $normalized The normalized content item that was rejected
             * @param string $reason     The reason for rejection ('language')
             */
            do_action('asapdigest_content_rejected', $normalized, 'language');
            return false;
        }

        // Freshness check (stub: 7 days)
        $max_age = 7;
        if (isset($normalized['publish_date']) && strtotime($normalized['publish_date']) < strtotime("-{$max_age} days")) {
            /**
             * Fires when content is rejected for being too old
             *
             * @since 2.3.0
             *
             * @param array  $normalized The normalized content item that was rejected
             * @param string $reason     The reason for rejection ('too_old')
             */
            do_action('asapdigest_content_rejected', $normalized, 'too_old');
            return false;
        }

        // AI summarization/entity extraction (stub)
        if ($this->ai_client && empty($normalized['summary']) && !empty($normalized['content'])) {
            $normalized['summary'] = '[AI summary placeholder]';
        }
        if ($this->entity_extractor && !empty($normalized['content'])) {
            $normalized['entities'] = ['[entity extraction placeholder]'];
        }

        // Media processing (stub)
        if ($this->media_processor && !empty($normalized['media'])) {
            $normalized['media'] = $this->media_processor->process($normalized['media']);
        }

        $normalized['processing_time'] = microtime(true) - $start_time;

        // Store the processed content
        $result = $this->storage->store($normalized);
        
        /**
         * Fires when content has been successfully processed and stored
         *
         * @since 2.3.0
         *
         * @param array    $normalized The normalized content item that was processed
         * @param int|bool $result     The result of storing the content (content ID or false on failure)
         */
        do_action('asapdigest_content_processed', $normalized, $result);
        
        return $result;
    }
    
    /**
     * Process a single content item using the enhanced processing pipeline
     * 
     * @since 2.3.0
     * 
     * @param array $item Raw content item to process
     * @return int|false Content ID or false on rejection
     */
    private function process_item_enhanced($item) {
        $start_time = microtime(true);
        
        // Normalize content
        $normalized = $this->normalize_content($item);
        
        /**
         * Filter for enhanced content processing
         *
         * @since 2.3.0
         *
         * @param array $normalized The normalized content to be processed
         * @return array Processing result with keys: success, content_id, message, errors
         */
        $process_result = apply_filters('asapdigest_content_enhanced_process', $normalized);
        
        // Check if processing was successful
        if (!empty($process_result) && isset($process_result['success']) && $process_result['success']) {
            // Record processing time
            $processing_time = microtime(true) - $start_time;
            
            /**
             * Fires when content has been successfully processed through the enhanced pipeline
             *
             * @since 2.3.0
             *
             * @param array $normalized  The normalized content item that was processed
             * @param int   $content_id  The ID of the stored content
             */
            do_action('asapdigest_content_processed', $normalized, $process_result['content_id']);
            
            return $process_result['content_id'];
        } else {
            // Handle processing failure
            $reason = isset($process_result['message']) ? $process_result['message'] : 'enhanced_processing_failed';
            
            /**
             * Fires when content is rejected by the enhanced processing pipeline
             *
             * @since 2.3.0
             *
             * @param array  $normalized The normalized content item that was rejected
             * @param string $reason     The reason for rejection
             */
            do_action('asapdigest_content_rejected', $normalized, $reason);
            
            // Log specific errors if available
            if (isset($process_result['errors']) && !empty($process_result['errors'])) {
                foreach ($process_result['errors'] as $key => $error) {
                    error_log("Content processing error ({$key}): " . (is_string($error) ? $error : json_encode($error)));
                }
            }
            
            return false;
        }
    }

    // --- Helper methods ---
    
    /**
     * Check if content passes initial filtering criteria
     *
     * @param array $item Content item to check
     * @return bool True if content passes initial filter
     */
    private function passes_initial_filter($item) { 
        return true; 
    }
    
    /**
     * Normalize content to standard format
     *
     * @param array $item Raw content item
     * @return array Normalized content
     */
    private function normalize_content($item) { 
        return $item; 
    }
    
    /**
     * Detect language of content
     *
     * @param string $content Content to analyze
     * @return string Language code (e.g., 'en')
     */
    private function detect_language($content) { 
        return 'en'; 
    }
    
    /**
     * Get configuration for a specific content source
     *
     * @param int $source_id Source ID
     * @return array Source configuration
     */
    private function get_source_config($source_id) { 
        return []; 
    }
} 