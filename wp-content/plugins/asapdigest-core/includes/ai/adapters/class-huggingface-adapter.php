<?php
/**
 * @file-marker ASAP_Digest_HuggingFaceAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/ai/adapters/class-huggingface-adapter.php
 */

namespace ASAPDigest\AI\Adapters;

use ASAPDigest\AI\Interfaces\AI_Provider_Interface;

/**
 * Hugging Face API adapter
 * 
 * Interfaces with Hugging Face's Inference API for AI operations.
 */
class HuggingFaceAdapter implements AI_Provider_Interface {
    /**
     * @var string API key
     */
    private $api_key;
    
    /**
     * @var array Default models for different tasks
     */
    private $default_models = [
        'summarize' => 'facebook/bart-large-cnn',
        'extract_entities' => 'dbmdz/bert-large-cased-finetuned-conll03-english',
        'classify' => 'facebook/bart-large-mnli',
        'generate_keywords' => 'yanekyuk/bert-uncased-keyword-extractor',
    ];
    
    /**
     * @var string Inference API endpoint
     */
    private $api_endpoint = 'https://api-inference.huggingface.co/models/';
    
    /**
     * @var int Request timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * Last API response for debugging
     *
     * @var array|null
     */
    private $last_response = null;
    
    /**
     * Usage data from the last request
     *
     * @var array
     */
    private $usage_data = [
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'total_tokens' => 0,
        'cost' => 0.0
    ];
    
    /**
     * Constructor
     * 
     * @param array $options Configuration options
     */
    public function __construct($options = []) {
        if (empty($options['api_key'])) {
            throw new \Exception('Hugging Face API key is required');
        }
        
        $this->api_key = $options['api_key'];
        
        // Override default models if specified
        if (!empty($options['models']) && is_array($options['models'])) {
            $this->default_models = array_merge($this->default_models, $options['models']);
        }
        
        // Set timeout if specified
        if (!empty($options['timeout'])) {
            $this->timeout = (int)$options['timeout'];
        }
    }
    
    /**
     * Generate a text summary
     * 
     * @param string $text Text to summarize
     * @param array $options Summarization options
     * @return string Summary
     */
    public function summarize($text, $options = []) {
        // Get model to use
        $model = !empty($options['model']) ? $options['model'] : $this->default_models['summarize'];
        
        // Prepare payload
        $payload = [
            'inputs' => $text,
            'parameters' => [
                'max_length' => !empty($options['max_length']) ? (int)$options['max_length'] : 150,
                'min_length' => !empty($options['min_length']) ? (int)$options['min_length'] : 30,
                'do_sample' => !empty($options['do_sample']),
                'early_stopping' => true,
            ],
        ];
        
        // Make request
        $response = $this->call_api($model, $payload);
        
        // Extract and return the summary
        if (isset($response[0]['summary_text'])) {
            return $response[0]['summary_text'];
        } elseif (isset($response[0]['generated_text'])) {
            return $response[0]['generated_text'];
        } else {
            return $this->extract_text_from_response($response);
        }
    }
    
    /**
     * Extract entities from text
     * 
     * @param string $text Text to analyze
     * @param array $options Extraction options
     * @return array Entities
     */
    public function extract_entities($text, $options = []) {
        // Get model to use
        $model = !empty($options['model']) ? $options['model'] : $this->default_models['extract_entities'];
        
        // Prepare payload
        $payload = [
            'inputs' => $text,
        ];
        
        // Make request
        $response = $this->call_api($model, $payload);
        
        // Process and normalize the response
        $entities = $this->process_entity_response($response);
        
        // Filter by confidence if specified
        if (isset($options['min_confidence'])) {
            $min_confidence = (float)$options['min_confidence'];
            $entities = array_filter($entities, function($entity) use ($min_confidence) {
                return $entity['confidence'] >= $min_confidence;
            });
        }
        
        return array_values($entities); // Reset array keys
    }
    
    /**
     * Process and normalize entity recognition response
     * 
     * @param array $response API response
     * @return array Normalized entities
     */
    private function process_entity_response($response) {
        $entities = [];
        
        // Response format varies by model
        if (isset($response[0]['entity_group'])) {
            // Single entity format
            foreach ($response as $entity) {
                $entities[] = [
                    'entity' => $entity['word'],
                    'type' => $entity['entity_group'],
                    'confidence' => $entity['score'],
                ];
            }
        } elseif (isset($response[0]['entities'])) {
            // Nested entities format
            foreach ($response as $item) {
                foreach ($item['entities'] as $entity) {
                    $entities[] = [
                        'entity' => $entity['word'],
                        'type' => $entity['entity'],
                        'confidence' => $entity['score'],
                    ];
                }
            }
        }
        
        return $entities;
    }
    
    /**
     * Classify content into categories
     * 
     * @param string $text Text to classify
     * @param array $categories Categories to choose from
     * @param array $options Classification options
     * @return array Classifications with confidence scores
     */
    public function classify($text, $categories = [], $options = []) {
        // Get model to use
        $model = !empty($options['model']) ? $options['model'] : $this->default_models['classify'];
        
        $results = [];
        
        if (empty($categories)) {
            // If no categories provided, use zero-shot classification
            $payload = [
                'inputs' => $text,
            ];
            
            $response = $this->call_api($model, $payload);
            
            // Extract classification results
            if (isset($response[0]['label'])) {
                $results[] = [
                    'category' => $response[0]['label'],
                    'confidence' => $response[0]['score'],
                ];
            }
        } else {
            // For each category, check if the text belongs to it
            foreach ($categories as $category) {
                $hypothesis = "This text is about " . $category . ".";
                
                $payload = [
                    'inputs' => [
                        'premise' => $text,
                        'hypothesis' => $hypothesis,
                    ],
                ];
                
                try {
                    $response = $this->call_api($model, $payload);
                    
                    // Extract classification results
                    if (isset($response['labels']) && isset($response['scores'])) {
                        $index = array_search('ENTAILMENT', $response['labels']);
                        if ($index !== false) {
                            $results[] = [
                                'category' => $category,
                                'confidence' => $response['scores'][$index],
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Continue with other categories
                    continue;
                }
            }
        }
        
        // Sort by confidence (descending)
        usort($results, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $results;
    }
    
    /**
     * Generate keywords from text
     * 
     * @param string $text Text to analyze
     * @param array $options Keyword generation options
     * @return array Keywords
     */
    public function generate_keywords($text, $options = []) {
        // For Hugging Face, we'll use a transformer model or fall back to language parsing
        $model = !empty($options['model']) ? $options['model'] : $this->default_models['generate_keywords'];
        
        try {
            // Try using the specified model first
            $payload = [
                'inputs' => $text,
            ];
            
            $response = $this->call_api($model, $payload);
            
            // Process based on response format
            if (isset($response[0]['word']) && isset($response[0]['score'])) {
                // Keyword extractor format
                $keywords = [];
                foreach ($response as $item) {
                    $keywords[] = [
                        'keyword' => $item['word'],
                        'score' => $item['score'],
                    ];
                }
                
                // Sort by score (descending)
                usort($keywords, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                // Take top N keywords
                $limit = !empty($options['limit']) ? (int)$options['limit'] : 10;
                return array_slice($keywords, 0, $limit);
            }
        } catch (\Exception $e) {
            // If model-based extraction fails, fall back to rule-based extraction
        }
        
        // Fallback: Extract potential keywords using NLP rules
        return $this->extract_keywords_from_text($text, $options);
    }
    
    /**
     * Extract keywords using rule-based NLP
     * 
     * @param string $text Text to analyze
     * @param array $options Extraction options
     * @return array Keywords
     */
    private function extract_keywords_from_text($text, $options = []) {
        // Remove common stop words
        $stop_words = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'were', 'will', 'with',
        ];
        
        // Clean text
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Tokenize and filter
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        // Count word frequency
        $counts = array_count_values($words);
        
        // Convert to keyword format
        $keywords = [];
        foreach ($counts as $word => $count) {
            $keywords[] = [
                'keyword' => $word,
                'score' => $count / count($words), // Normalize score
            ];
        }
        
        // Sort by score (descending)
        usort($keywords, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Take top N keywords
        $limit = !empty($options['limit']) ? (int)$options['limit'] : 10;
        return array_slice($keywords, 0, $limit);
    }
    
    /**
     * Call the Hugging Face API
     * 
     * @param string $model Model to use
     * @param array $payload Request payload
     * @return mixed API response
     * @throws \Exception If API request fails
     */
    private function call_api($model, $payload) {
        $url = $this->api_endpoint . $model;
        
        $args = [
            'method' => 'POST',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ];
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new \Exception('API returned error code: ' . $response_code);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Extract text from various response formats
     * 
     * @param mixed $response API response
     * @return string Extracted text
     */
    private function extract_text_from_response($response) {
        if (is_string($response)) {
            return $response;
        }
        
        if (is_array($response)) {
            if (isset($response[0])) {
                if (is_string($response[0])) {
                    return $response[0];
                } elseif (isset($response[0]['generated_text'])) {
                    return $response[0]['generated_text'];
                }
            }
        }
        
        // Fallback: serialize the response
        return json_encode($response);
    }
    
    /**
     * Test connection to Hugging Face API
     * @return bool
     */
    public function test_connection() {
        error_log('[ASAP HuggingFaceAdapter] Attempting test connection.');
        $api_key = $this->api_key;
        if (!$api_key) {
            error_log('[ASAP HuggingFaceAdapter] Test connection failed: API key missing.');
            throw new \Exception('API key is missing.');
        }
        error_log('[ASAP HuggingFaceAdapter] API Key present (length: ' . strlen($api_key) . '). Making request to Hugging Face.');
        
        // Use a simple model for testing
        $model = 'distilbert-base-uncased';
        $url = $this->api_endpoint . $model;
        $response = wp_remote_post($url, [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body'    => json_encode(['inputs' => 'Hello, testing!']),
            'timeout' => 15,
        ]);

        $response_body = wp_remote_retrieve_body($response);
        error_log('[ASAP HuggingFaceAdapter] Raw test connection response: ' . $response_body);

        if (is_wp_error($response)) {
            error_log('[ASAP HuggingFaceAdapter] Test connection WP_Error: ' . $response->get_error_message());
            throw new \Exception('Connection failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        error_log('[ASAP HuggingFaceAdapter] Test connection response code: ' . $response_code);

        if ($response_code === 200) {
            error_log('[ASAP HuggingFaceAdapter] Test connection successful.');
            return true;
        }

        error_log('[ASAP HuggingFaceAdapter] Test connection failed with code: ' . $response_code);
        throw new \Exception('Connection failed with status code: ' . $response_code . ' - ' . $response_body);
    }
    
    /**
     * Get the last API response for debugging
     *
     * @return array|null Last response data
     */
    public function get_last_response() {
        return $this->last_response;
    }
    
    /**
     * Calculate quality score for content
     * 
     * @param string $text Text to analyze
     * @param array $options Additional options for quality scoring
     * @return array Quality score results with breakdown
     */
    public function calculate_quality_score($text, $options = []) {
        $model = !empty($options['model']) ? $options['model'] : $this->default_models['summarize'];
        $task = !empty($options['task']) ? $options['task'] : 'text-classification';
        
        $inputs = "Analyze the following text and rate its quality from 0-100. Include scores for readability, engagement, coherence, and relevance:\n\n" . $text;
        
        try {
            $response = $this->send_api_request([
                'model' => $model,
                'inputs' => $inputs,
                'task' => $task
            ]);
            
            $this->last_response = $response;
            $this->update_usage_data($response);
            
            // In a real implementation, parse the response to extract scores
            // For now, return a placeholder response
            return [
                'overall' => 70,
                'components' => [
                    'readability' => 75,
                    'engagement' => 65,
                    'coherence' => 70,
                    'relevance' => 70,
                ],
                'suggestions' => [
                    'Consider using more engaging language.',
                    'Improve transitions between paragraphs.'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'overall' => 0,
                'components' => [],
                'suggestions' => []
            ];
        }
    }
    
    /**
     * Get provider capabilities
     * 
     * @return array List of supported features and limitations
     */
    public function get_capabilities() {
        return [
            'summarize' => true,
            'classify' => true,
            'extract_entities' => true,
            'calculate_quality_score' => true,
            'max_input_tokens' => 4096, // Typical for many HF models
            'supports_streaming' => false,
            'supports_functions' => false,
            'supports_vision' => true,
        ];
    }
    
    /**
     * Get available models
     * 
     * @return array List of models with capabilities
     */
    public function get_models() {
        return [
            'gpt2' => [
                'max_tokens' => 1024,
                'description' => 'Basic language model for text generation',
                'cost_per_1k_tokens' => [
                    'input' => 0.0,    // HF Inference API is free for many models
                    'output' => 0.0,
                ],
            ],
            'distilbert-base-uncased' => [
                'max_tokens' => 512,
                'description' => 'Fast model for text classification',
                'cost_per_1k_tokens' => [
                    'input' => 0.0,
                    'output' => 0.0,
                ],
            ],
            't5-base' => [
                'max_tokens' => 512,
                'description' => 'Versatile model for text-to-text tasks',
                'cost_per_1k_tokens' => [
                    'input' => 0.0,
                    'output' => 0.0,
                ],
            ],
        ];
    }
    
    /**
     * Get usage information
     * 
     * @return array Usage metrics
     */
    public function get_usage_info() {
        return $this->usage_data;
    }
    
    /**
     * Send request to Hugging Face API
     * 
     * @param array $params Request parameters
     * @return array API response
     * @throws \Exception on error
     */
    private function send_api_request($params) {
        // In real implementation, this would send request to Hugging Face Inference API
        // This is a placeholder implementation
        
        if (empty($this->api_key)) {
            throw new \Exception('API key is not set');
        }
        
        // Simulate API response
        $response = [
            'generated_text' => 'Sample response from Hugging Face',
            // HF doesn't provide token usage in the same way as OpenAI
            // We would need to estimate it
            'estimated_usage' => [
                'prompt_tokens' => 40,
                'completion_tokens' => 20,
                'total_tokens' => 60
            ]
        ];
        
        return $response;
    }
    
    /**
     * Update usage tracking data
     * 
     * @param array $response API response with usage data
     */
    private function update_usage_data($response) {
        if (!empty($response['estimated_usage'])) {
            $this->usage_data['prompt_tokens'] += $response['estimated_usage']['prompt_tokens'];
            $this->usage_data['completion_tokens'] += $response['estimated_usage']['completion_tokens'];
            $this->usage_data['total_tokens'] += $response['estimated_usage']['total_tokens'];
            
            // Most Hugging Face models are free in the Inference API
            $this->usage_data['cost'] = 0.0;
        }
    }
} 