<?php
/**
 * Entity Extractor Class
 *
 * Provides enhanced entity extraction from content with improved accuracy and categorization.
 *
 * @package ASAPDigest_Core
 * @subpackage AI\Processors
 * @since 3.1.0
 * @file-marker ASAP_Digest_EntityExtractor
 * @created 05/07/25 | 04:15 PM PDT
 */

namespace ASAPDigest\AI\Processors;

use ASAPDigest\AI\AIServiceManager;
use ASAPDigest\Core\ErrorLogger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to extract and categorize entities from content with improved accuracy
 */
class EntityExtractor {
    /**
     * AI Service Manager instance
     *
     * @var AIServiceManager
     */
    private $ai_service;
    
    /**
     * Entity types to extract
     *
     * @var array
     */
    private $entity_types = [
        'person',
        'organization',
        'location',
        'date',
        'event',
        'product',
        'technology',
        'concept',
        'topic'
    ];
    
    /**
     * Cache of extracted entities
     *
     * @var array
     */
    private $entity_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_service = new AIServiceManager();
    }
    
    /**
     * Set entity types to extract
     *
     * @param array $types Entity types to focus on
     * @return void
     */
    public function set_entity_types($types) {
        if (is_array($types) && !empty($types)) {
            $this->entity_types = array_map('sanitize_text_field', $types);
        }
    }
    
    /**
     * Extract entities from content with enhanced accuracy
     *
     * @param string $content The content to analyze
     * @param array $options Additional options for extraction
     * @return array Extracted entities organized by type
     */
    public function extract($content, $options = []) {
        // Generate a cache key for this content
        $cache_key = md5($content) . '_' . md5(serialize($options));
        
        // Return cached results if available
        if (isset($this->entity_cache[$cache_key])) {
            return $this->entity_cache[$cache_key];
        }
        
        try {
            // Prepare extraction options
            $extraction_options = [
                'entity_types' => $this->entity_types,
                'min_confidence' => isset($options['min_confidence']) ? (float) $options['min_confidence'] : 0.7,
                'hierarchical' => isset($options['hierarchical']) ? (bool) $options['hierarchical'] : true,
                'context_aware' => isset($options['context_aware']) ? (bool) $options['context_aware'] : true
            ];
            
            // Extract entities using AI service
            $raw_entities = $this->ai_service->extract_entities($content, $extraction_options);
            
            // Process and enhance the extracted entities
            $processed_entities = $this->process_entities($raw_entities, $content, $extraction_options);
            
            // Cache the results
            $this->entity_cache[$cache_key] = $processed_entities;
            
            return $processed_entities;
        } catch (\Exception $e) {
            ErrorLogger::log('entity_extraction', 'extraction_error', $e->getMessage(), [
                'content_length' => strlen($content),
                'options' => $options
            ], 'error');
            
            return [
                'entities' => [],
                'entity_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process and enhance extracted entities
     *
     * @param array $raw_entities Entities from AI service
     * @param string $content Original content for context
     * @param array $extraction_options Options used for extraction
     * @return array Processed and enhanced entities
     */
    private function process_entities($raw_entities, $content, $extraction_options) {
        $entities_by_type = [];
        $all_entities = [];
        
        // Normalize entity structure
        foreach ($raw_entities as $entity) {
            // Ensure entity has all required fields
            $processed_entity = [
                'text' => isset($entity['entity']) ? $entity['entity'] : (isset($entity['text']) ? $entity['text'] : ''),
                'type' => isset($entity['type']) ? $entity['type'] : 'unknown',
                'confidence' => isset($entity['confidence']) ? (float) $entity['confidence'] : 0.8,
                'mentions' => 1
            ];
            
            // Skip entities with confidence below minimum threshold
            if ($processed_entity['confidence'] < $extraction_options['min_confidence']) {
                continue;
            }
            
            // Normalize entity type to lowercase
            $processed_entity['type'] = strtolower($processed_entity['type']);
            
            // Check if this entity already exists (possibly mentioned multiple times)
            $entity_exists = false;
            foreach ($all_entities as &$existing_entity) {
                if (strtolower($existing_entity['text']) === strtolower($processed_entity['text'])) {
                    // Update existing entity with higher confidence if applicable
                    if ($processed_entity['confidence'] > $existing_entity['confidence']) {
                        $existing_entity['confidence'] = $processed_entity['confidence'];
                    }
                    $existing_entity['mentions']++;
                    $entity_exists = true;
                    break;
                }
            }
            
            if (!$entity_exists) {
                // Add context for the entity if enabled
                if ($extraction_options['context_aware']) {
                    $processed_entity['context'] = $this->extract_entity_context($processed_entity['text'], $content);
                }
                
                $all_entities[] = $processed_entity;
            }
        }
        
        // Organize entities by type
        foreach ($all_entities as $entity) {
            $type = $entity['type'];
            
            if (!isset($entities_by_type[$type])) {
                $entities_by_type[$type] = [];
            }
            
            $entities_by_type[$type][] = $entity;
        }
        
        // Sort entities within each type by confidence
        foreach ($entities_by_type as $type => $entities) {
            usort($entities_by_type[$type], function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });
        }
        
        // Calculate statistics
        $entity_count = count($all_entities);
        $type_counts = [];
        foreach ($entities_by_type as $type => $entities) {
            $type_counts[$type] = count($entities);
        }
        
        // Create hierarchical structure if requested
        $hierarchical_entities = $extraction_options['hierarchical'] ? 
            $this->create_entity_hierarchy($entities_by_type) : null;
        
        return [
            'entities' => $all_entities,
            'entities_by_type' => $entities_by_type,
            'hierarchical' => $hierarchical_entities,
            'entity_count' => $entity_count,
            'type_counts' => $type_counts
        ];
    }
    
    /**
     * Extract context for an entity from the content
     *
     * @param string $entity_text Entity text
     * @param string $content Original content
     * @return string|null Context snippet or null if not found
     */
    private function extract_entity_context($entity_text, $content) {
        // Find position of entity in content
        $pos = stripos($content, $entity_text);
        if ($pos === false) {
            return null;
        }
        
        // Extract context (50 chars before and after)
        $start = max(0, $pos - 50);
        $length = min(strlen($content) - $start, strlen($entity_text) + 100);
        $context = substr($content, $start, $length);
        
        // Trim to complete sentences if possible
        $context = preg_replace('/^[^.!?]*[.!?]/', '', $context);
        $context = preg_replace('/[.!?][^.!?]*$/', '', $context);
        
        return trim($context);
    }
    
    /**
     * Create a hierarchical structure of entities
     *
     * @param array $entities_by_type Entities organized by type
     * @return array Hierarchical entity structure
     */
    private function create_entity_hierarchy($entities_by_type) {
        $hierarchy = [
            'topics' => [],
            'concepts' => [],
            'entities' => []
        ];
        
        // Extract topics (highest level)
        if (isset($entities_by_type['topic'])) {
            foreach ($entities_by_type['topic'] as $topic) {
                $hierarchy['topics'][] = [
                    'name' => $topic['text'],
                    'confidence' => $topic['confidence'],
                    'context' => $topic['context'] ?? null
                ];
            }
        }
        
        // Extract concepts (mid level)
        if (isset($entities_by_type['concept'])) {
            foreach ($entities_by_type['concept'] as $concept) {
                $hierarchy['concepts'][] = [
                    'name' => $concept['text'],
                    'confidence' => $concept['confidence'],
                    'context' => $concept['context'] ?? null
                ];
            }
        }
        
        // Group other entity types (lowest level)
        $entity_types = ['person', 'organization', 'location', 'product', 'technology', 'event', 'date'];
        foreach ($entity_types as $type) {
            if (isset($entities_by_type[$type])) {
                foreach ($entities_by_type[$type] as $entity) {
                    $hierarchy['entities'][] = [
                        'name' => $entity['text'],
                        'type' => $type,
                        'confidence' => $entity['confidence'],
                        'context' => $entity['context'] ?? null
                    ];
                }
            }
        }
        
        return $hierarchy;
    }
    
    /**
     * Get primary entities based on confidence and mentions
     *
     * @param array $extraction_result Result from extract() method
     * @param int $limit Maximum number of primary entities to return
     * @return array Primary entities
     */
    public function get_primary_entities($extraction_result, $limit = 5) {
        if (empty($extraction_result['entities'])) {
            return [];
        }
        
        // Sort entities by a combination of confidence and mentions
        $entities = $extraction_result['entities'];
        usort($entities, function($a, $b) {
            // Calculate a score based on confidence and mentions
            $score_a = $a['confidence'] * (1 + log($a['mentions']));
            $score_b = $b['confidence'] * (1 + log($b['mentions']));
            
            return $score_b <=> $score_a;
        });
        
        // Return top entities
        return array_slice($entities, 0, $limit);
    }
} 