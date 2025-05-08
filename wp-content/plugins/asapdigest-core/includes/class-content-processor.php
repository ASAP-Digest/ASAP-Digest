<?php
/**
 * @file-marker ASAP_Digest_ContentProcessor
 * @location /wp-content/plugins/asapdigest-core/includes/class-content-processor.php
 */

namespace AsapDigest\Crawler;

class ContentProcessor {
    private $storage;
    // Stubs for AI, entity extraction, duplicate detection, media
    private $ai_client;
    private $entity_extractor;
    private $duplicate_detector;
    private $media_processor;

    public function __construct($storage) {
        $this->storage = $storage;
        // Stubs: inject real services as needed
        $this->ai_client = null;
        $this->entity_extractor = null;
        $this->duplicate_detector = null;
        $this->media_processor = null;
    }

    /**
     * Process a single content item: normalize, deduplicate, enrich, store.
     * @param array $item
     * @return int|false WP Post ID or false on rejection
     */
    public function process_item($item) {
        $start_time = microtime(true);

        // Initial filter (stub: always true)
        if (!$this->passes_initial_filter($item)) {
            do_action('asap_digest_content_rejected', $item, 'initial_filter');
            return false;
        }

        // Normalize content
        $normalized = $this->normalize_content($item);

        // Duplicate detection (stub: always false)
        if ($this->duplicate_detector && $this->duplicate_detector->is_duplicate($normalized)) {
            do_action('asap_digest_content_rejected', $normalized, 'duplicate');
            return false;
        }

        // Language check (stub: only 'en')
        $language = $this->detect_language($normalized['content'] ?? '');
        if ($language !== 'en') {
            do_action('asap_digest_content_rejected', $normalized, 'language');
            return false;
        }

        // Freshness check (stub: 7 days)
        $max_age = 7;
        if (isset($normalized['publish_date']) && strtotime($normalized['publish_date']) < strtotime("-{$max_age} days")) {
            do_action('asap_digest_content_rejected', $normalized, 'too_old');
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
        do_action('asap_digest_content_processed', $normalized, $result);
        return $result;
    }

    // --- Helper methods (stubs) ---
    private function passes_initial_filter($item) { return true; }
    private function normalize_content($item) { return $item; }
    private function detect_language($content) { return 'en'; }
    private function get_source_config($source_id) { return []; }
} 