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
    
    /**
     * Calculate quality score for content
     * 
     * @param string $text Text to analyze
     * @param array $options Additional options for quality scoring
     * @return array Quality score results with breakdown
     */
    public function calculate_quality_score($text, $options = []);
    
    /**
     * Test connection to the AI provider
     * 
     * @return array Test results with success status, message, and latency
     */
    public function test_connection();
    
    /**
     * Get capabilities of this provider
     * 
     * @return array Provider capabilities including supported operations
     */
    public function get_capabilities();
    
    /**
     * Get available models from this provider
     * 
     * @return array Available models with details
     */
    public function get_models();
    
    /**
     * Get details about the last API response
     * 
     * @return array Response details including status and timing information
     */
    public function get_last_response();
    
    /**
     * Get usage information for billing/monitoring
     * 
     * @return array Usage data including tokens used and estimated cost
     */
    public function get_usage_info();
} 