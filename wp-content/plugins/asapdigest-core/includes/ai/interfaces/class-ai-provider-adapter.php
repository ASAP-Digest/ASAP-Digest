<?php
/**
 * @file-marker ASAP_Digest_AIProviderAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/ai/interfaces/class-ai-provider-adapter.php
 */

namespace AsapDigest\AI\Interfaces;

/**
 * Interface for AI provider adapters.
 * All AI service adapters must implement this interface.
 */
interface AIProviderAdapter {
    /**
     * Generate a summary of the provided text
     * 
     * @param string $text Text to summarize
     * @param array $options Additional options for summarization
     * @return string The generated summary
     */
    public function summarize($text, $options = []);
    
    /**
     * Extract entities (people, places, organizations, etc.) from text
     * 
     * @param string $text Text to analyze
     * @param array $options Additional options for entity extraction
     * @return array Extracted entities
     */
    public function extract_entities($text, $options = []);
    
    /**
     * Classify content into categories
     * 
     * @param string $text Text to classify
     * @param array $categories Categories to classify into
     * @param array $options Additional options for classification
     * @return array Classification results
     */
    public function classify($text, $categories = [], $options = []);
    
    /**
     * Generate keywords from content
     * 
     * @param string $text Text to analyze
     * @param array $options Additional options for keyword generation
     * @return array Generated keywords
     */
    public function generate_keywords($text, $options = []);
} 