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
        'extract_entities' => 'dslim/bert-base-NER',
        'classify' => 'facebook/bart-large-mnli',
        'generate_keywords' => 'yanekyuk/bert-uncased-keyword-extractor',
        'text_generation' => 'google/flan-t5-base',
        'embedding' => 'sentence-transformers/all-MiniLM-L6-v2',
        'translation' => 'Helsinki-NLP/opus-mt-en-fr',
        'test' => 'distilbert-base-uncased-finetuned-sst-2-english' // Updated to a reliable test model
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
        // Define fallback models for summarization
        $fallback_models = [
            'facebook/bart-large-cnn',           // Main summarization model
            'sshleifer/distilbart-cnn-12-6',     // Lighter/faster alternative
            'philschmid/bart-large-cnn-samsum',  // Good for dialogue summarization
            'google/pegasus-xsum'                // Alternative architecture
        ];
        
        try {
            // Get model to use with fallback options
            $primary_model = !empty($options['model']) ? $options['model'] : $this->default_models['summarize'];
            
            // Only use fallbacks if a specific model wasn't requested
            $use_fallbacks = empty($options['model']);
            $model = $use_fallbacks 
                ? $this->get_model_with_fallback($primary_model, $fallback_models, 'summarize')
                : $primary_model;
            
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
            
            // Make request with retry
            $response = $this->call_api_with_retry($model, $payload);
            
            // Extract and return the summary
            if (isset($response[0]['summary_text'])) {
                return $response[0]['summary_text'];
            } elseif (isset($response[0]['generated_text'])) {
                return $response[0]['generated_text'];
            } else {
                return $this->extract_text_from_response($response);
            }
        } catch (\Exception $e) {
            error_log('[ASAP HuggingFaceAdapter] Summarization error: ' . $e->getMessage());
            
            // If a specific model was requested and failed, try our fallbacks as a last resort
            if (!empty($options['model']) && strpos($e->getMessage(), 'Not Found') !== false) {
                error_log('[ASAP HuggingFaceAdapter] Requested model failed, trying fallbacks');
                $options_without_model = $options;
                unset($options_without_model['model']); // Remove the specific model to use defaults + fallbacks
                return $this->summarize($text, $options_without_model);
            }
            
            throw new \Exception('Failed to generate summary: ' . $e->getMessage());
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
     * @param string $model Optional model name to test with. If not provided, uses a default test model.
     * @return bool
     */
    public function test_connection($model = null) {
        error_log('[ASAP HuggingFaceAdapter] Attempting test connection.');
        $api_key = $this->get_api_key();
        if (!$api_key) {
            error_log('[ASAP HuggingFaceAdapter] Test connection failed: API key missing.');
            throw new \Exception('API key is missing.');
        }
        error_log('[ASAP HuggingFaceAdapter] API Key present (length: ' . strlen($api_key) . '). Making request to Hugging Face.');
        
        // Use provided model or fall back to a simple model for testing
        $test_model = $model ?: (isset($this->default_models['test']) ? $this->default_models['test'] : 'distilbert-base-uncased-finetuned-sst-2-english');
        
        // Log the model being used
        error_log('[ASAP HuggingFaceAdapter] Testing with model: ' . $test_model);
        
        // Ensure we have a valid model name
        if (empty($test_model)) {
            error_log('[ASAP HuggingFaceAdapter] Test connection failed: Model name missing.');
            throw new \Exception('A model name is required for Hugging Face Inference API.');
        }
        
        $url = $this->api_endpoint . $test_model;
        error_log('[ASAP HuggingFaceAdapter] Test connection URL: ' . $url);
        
        // Use a simple test input based on model type
        $test_input = 'Hello, testing!';
        if (strpos($test_model, 'bart') !== false && strpos($test_model, 'cnn') !== false) {
            // For summarization models
            $test_input = 'The quick brown fox jumps over the lazy dog. This is a test sentence to verify the API connection.';
        } elseif (strpos($test_model, 'bert') !== false) {
            // For BERT-based models
            $test_input = 'John Smith works at Microsoft in Seattle.';
        } elseif (strpos($test_model, 't5') !== false) {
            // For T5 models
            $test_input = 'translate English to French: Hello, world!';
        }
        
        error_log('[ASAP HuggingFaceAdapter] Using test input: ' . $test_input);
        
        try {
            $response = wp_remote_post($url, [
                'method'  => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ],
                'body'    => json_encode(['inputs' => $test_input]),
                'timeout' => 15,
            ]);

            $response_body = wp_remote_retrieve_body($response);
            error_log('[ASAP HuggingFaceAdapter] Raw test connection response: ' . $response_body);

            if (is_wp_error($response)) {
                $msg = $response->get_error_message();
                if (empty($msg)) {
                    $msg = 'Unknown error from wp_remote_post.';
                }
                error_log('[ASAP HuggingFaceAdapter] Test connection WP_Error: ' . $msg);
                throw new \Exception('Connection failed: ' . $msg);
            }

            $response_code = wp_remote_retrieve_response_code($response);
            error_log('[ASAP HuggingFaceAdapter] Test connection response code: ' . $response_code);

            if ($response_code === 200) {
                error_log('[ASAP HuggingFaceAdapter] Test connection successful.');
                return true;
            }

            // Try to extract error message from response body
            $error_message = '';
            if (!empty($response_body)) {
                $data = json_decode($response_body, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['error'])) {
                    $error_message = $data['error'];
                } else {
                    $error_message = $response_body;
                }
            }
            if (empty($error_message)) {
                $error_message = 'Connection failed with status code: ' . $response_code . '. No error message returned from Hugging Face.';
            }
            
            // For "Not Found" errors, provide more helpful context
            if ($response_code === 404 || strpos($error_message, 'Not Found') !== false) {
                $error_message = 'Model "' . $test_model . '": Not Found. This model may have been renamed, moved, or is no longer available via the Inference API.';
            }
            
            // Include model information in error message for clarity
            if (strpos($error_message, $test_model) === false) {
                $error_message = 'Model "' . $test_model . '": ' . $error_message;
            }
            
            error_log('[ASAP HuggingFaceAdapter] Test connection failed with code: ' . $response_code . '. Message: ' . $error_message);
            throw new \Exception($error_message);
        } catch (\Exception $e) {
            // Additional error handling for specific error types
            $message = $e->getMessage();
            
            // Handle common connection issues
            if (strpos($message, 'cURL error 28') !== false) {
                throw new \Exception('Connection timed out. The Hugging Face servers might be under heavy load or the model is too large for the current timeout setting.');
            }
            
            // Handle file consistency issues
            if (strpos($message, 'Consistency check failed') !== false) {
                throw new \Exception('Model file consistency check failed. The model might be corrupted or incompletely downloaded. Please try again later or contact support if the issue persists.');
            }
            
            throw $e;
        }
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

    /**
     * Get recommended models grouped by task category
     * 
     * @return array Recommended models by category
     */
    public function get_recommended_models() {
        $recommended_models = [
            'Summarization' => [
                'facebook/bart-large-cnn' => 'BART model for news summarization',
                'sshleifer/distilbart-cnn-12-6' => 'Lightweight BART model for summarization',
                'google/pegasus-xsum' => 'Pegasus model for extreme summarization',
                'philschmid/bart-large-cnn-samsum' => 'BART optimized for dialogue summarization'
            ],
            'Named Entity Recognition' => [
                'dslim/bert-base-NER' => 'BERT model fine-tuned for named entity recognition',
                'Davlan/bert-base-multilingual-cased-ner-hrl' => 'Multilingual NER model',
                'elastic/distilbert-base-cased-finetuned-conll03-english' => 'Lightweight model for NER'
            ],
            'Text Classification' => [
                'facebook/bart-large-mnli' => 'BART model for zero-shot classification',
                'distilbert-base-uncased-finetuned-sst-2-english' => 'DistilBERT for sentiment analysis',
                'cardiffnlp/twitter-roberta-base-sentiment' => 'RoBERTa model for sentiment analysis',
                'cross-encoder/nli-distilroberta-base' => 'Efficient model for natural language inference'
            ],
            'Keyword Extraction' => [
                'yanekyuk/bert-uncased-keyword-extractor' => 'BERT model for keyword extraction',
                'ml6team/keyphrase-extraction-kbir-inspec' => 'Modern keyword extraction model',
                'mse30/keybert-trained' => 'KeyBERT model specifically for keyword extraction'
            ],
            'Text Generation' => [
                'google/flan-t5-base' => 'Flan-T5 instruction-tuned model',
                'succinctly/text2text-generation-webgpt-t5-base' => 'T5 model fine-tuned for high-quality generation',
                'google/mt5-small' => 'Multilingual T5 model for generation tasks',
                'bigscience/T0pp' => 'Zero-shot text generation model'
            ],
            'Embeddings' => [
                'sentence-transformers/all-MiniLM-L6-v2' => 'Efficient sentence embedding model',
                'sentence-transformers/all-mpnet-base-v2' => 'High-quality sentence embeddings',
                'microsoft/mpnet-base' => 'Microsoft MPNet for embeddings',
                'thenlper/gte-small' => 'Compact but powerful embedding model'
            ],
            'Translation' => [
                'Helsinki-NLP/opus-mt-en-fr' => 'English to French translation',
                'Helsinki-NLP/opus-mt-en-es' => 'English to Spanish translation',
                'Helsinki-NLP/opus-mt-en-de' => 'English to German translation',
                'Helsinki-NLP/opus-mt-en-ru' => 'English to Russian translation'
            ]
        ];
        
        return $recommended_models;
    }
    
    /**
     * Get model details from Hugging Face
     * 
     * @param string $model_id The model ID
     * @return array|WP_Error Model details or error
     */
    public function get_model_details($model_id) {
        if (empty($model_id)) {
            return new \WP_Error('missing_model_id', 'Model ID is required');
        }
        
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', 'HuggingFace API key is not configured');
        }
        
        // Build API URL
        $url = "https://huggingface.co/api/models/{$model_id}";
        
        // Make API request
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => "Bearer {$api_key}",
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Check if request was successful
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            $body = wp_remote_retrieve_body($response);
            
            if (!empty($body)) {
                $data = json_decode($body, true);
                if (isset($data['error'])) {
                    $error_message = $data['error'];
                }
            }
            
            return new \WP_Error(
                'huggingface_api_error',
                sprintf('Error fetching model details: %s', $error_message),
                array('status' => $response_code)
            );
        }
        
        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return new \WP_Error('invalid_response', 'Invalid response from HuggingFace API');
        }
        
        // Extract relevant information
        $model_details = array(
            'id' => $data['id'] ?? $model_id,
            'name' => $data['id'] ?? $model_id,
            'description' => $data['description'] ?? '',
            'author' => $data['author'] ?? '',
            'lastModified' => $data['lastModified'] ?? '',
            'tags' => $data['tags'] ?? array(),
            'pipeline_tag' => $data['pipeline_tag'] ?? '',
            'task' => $this->determine_task_from_model_data($data),
            'library' => $data['library_name'] ?? '',
            'downloads' => $data['downloads'] ?? 0,
            'likes' => $data['likes'] ?? 0,
            'model_card' => isset($data['model_card']) ? true : false,
            'spaces' => isset($data['spaces']) ? count($data['spaces']) : 0,
        );
        
        return $model_details;
    }
    
    /**
     * Determine the task from model data
     * 
     * @param array $model_data Model data from HuggingFace API
     * @return string|null Task name or null
     */
    private function determine_task_from_model_data($model_data) {
        // Check pipeline_tag first
        if (!empty($model_data['pipeline_tag'])) {
            $pipeline_tag = $model_data['pipeline_tag'];
            
            // Map pipeline tags to our task names
            $task_map = array(
                'text-generation' => 'text_generation',
                'text2text-generation' => 'text_generation',
                'summarization' => 'summarize',
                'translation' => 'translation',
                'feature-extraction' => 'embedding',
                'fill-mask' => 'text_generation',
                'token-classification' => 'extract_entities',
                'text-classification' => 'classify',
                'question-answering' => 'text_generation',
                'zero-shot-classification' => 'classify',
                'sentence-similarity' => 'embedding',
                'table-question-answering' => 'text_generation',
                'conversational' => 'text_generation',
            );
            
            if (isset($task_map[$pipeline_tag])) {
                return $task_map[$pipeline_tag];
            }
        }
        
        // Check tags as fallback
        if (!empty($model_data['tags'])) {
            $tags = $model_data['tags'];
            
            $task_keywords = array(
                'summarize' => array('summarization', 'summarize', 'summary'),
                'extract_entities' => array('ner', 'named-entity-recognition', 'token-classification', 'entity-extraction'),
                'classify' => array('classification', 'categorization', 'sentiment', 'emotions'),
                'generate_keywords' => array('keyword', 'keywords', 'keyphrase', 'key-phrase'),
                'text_generation' => array('generation', 'text-generation', 'gpt', 't5', 'bart', 'llm'),
                'embedding' => array('embedding', 'embeddings', 'sentence-transformers', 'sentence-similarity'),
                'translation' => array('translation', 'translate', 'mt', 'opus'),
            );
            
            foreach ($task_keywords as $task => $keywords) {
                foreach ($keywords as $keyword) {
                    if (in_array($keyword, $tags, true)) {
                        return $task;
                    }
                }
            }
        }
        
        // Fallback based on model name
        $model_id = $model_data['id'] ?? '';
        
        if (strpos($model_id, 'bart') !== false && strpos($model_id, 'cnn') !== false) {
            return 'summarize';
        }
        
        if (strpos($model_id, 'pegasus') !== false) {
            return 'summarize';
        }
        
        if (strpos($model_id, 'ner') !== false || strpos($model_id, 'NER') !== false) {
            return 'extract_entities';
        }
        
        if (strpos($model_id, 'sentiment') !== false || strpos($model_id, 'classify') !== false) {
            return 'classify';
        }
        
        if (strpos($model_id, 'keyword') !== false) {
            return 'generate_keywords';
        }
        
        if (strpos($model_id, 't5') !== false || strpos($model_id, 'T5') !== false) {
            return 'text_generation';
        }
        
        if (strpos($model_id, 'sentence-transformers') !== false || strpos($model_id, 'embedding') !== false) {
            return 'embedding';
        }
        
        if (strpos($model_id, 'opus-mt') !== false) {
            return 'translation';
        }
        
        // Default to null if we couldn't determine the task
        return null;
    }

    /**
     * Get API key from config or options
     * 
     * @return string API key or empty string
     */
    private function get_api_key() {
        // Check if API key is in the instance property
        if (!empty($this->api_key)) {
            return $this->api_key;
        }
        
        // Fall back to option
        return get_option('asap_ai_huggingface_key', '');
    }

    /**
     * Get a model with fallback options
     * 
     * Attempts to use the primary model, but falls back to alternatives if the primary model is unavailable.
     * 
     * @param string $primary_model_id The primary model ID to try first
     * @param array $fallback_models Array of fallback model IDs to try if the primary model fails
     * @param string $task_type Type of task (for logging and reporting)
     * @return string The model ID that was successfully validated
     * @throws \Exception if none of the models are available
     */
    private function get_model_with_fallback($primary_model_id, $fallback_models = [], $task_type = 'unknown') {
        $models_to_try = array_merge([$primary_model_id], $fallback_models);
        $errors = [];
        
        foreach ($models_to_try as $model_id) {
            try {
                $url = $this->api_endpoint . $model_id;
                
                // Make a simple HEAD request to check if the model exists
                $response = wp_remote_head($url, [
                    'method'  => 'HEAD',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->get_api_key(),
                    ],
                    'timeout' => 5, // Short timeout for HEAD request
                ]);
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    error_log("[ASAP HuggingFaceAdapter] Successfully validated model for {$task_type}: {$model_id}");
                    return $model_id;
                }
                
                // If we're here, the model is not available
                $status_code = is_wp_error($response) ? 'WP_Error' : wp_remote_retrieve_response_code($response);
                $error_msg = is_wp_error($response) ? $response->get_error_message() : "HTTP {$status_code}";
                $errors[$model_id] = $error_msg;
                
                error_log("[ASAP HuggingFaceAdapter] Model unavailable ({$error_msg}): {$model_id}");
            } catch (\Exception $e) {
                $errors[$model_id] = $e->getMessage();
                error_log("[ASAP HuggingFaceAdapter] Error checking model {$model_id}: " . $e->getMessage());
            }
        }
        
        // If we get here, all models failed
        $error_details = '';
        foreach ($errors as $model => $error) {
            $error_details .= "\n - {$model}: {$error}";
        }
        
        throw new \Exception("No available models found for {$task_type} task. Tried:" . $error_details);
    }
    
    /**
     * Call the Hugging Face API with retries and error handling
     * 
     * @param string $model Model to use
     * @param array $payload Request payload
     * @param int $max_retries Maximum number of retry attempts
     * @return mixed API response
     * @throws \Exception If API request fails after all retries
     */
    private function call_api_with_retry($model, $payload, $max_retries = 2) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt <= $max_retries) {
            try {
                return $this->call_api($model, $payload);
            } catch (\Exception $e) {
                $last_error = $e;
                $attempt++;
                
                // If this is a "model not found" error, don't retry
                if (strpos($e->getMessage(), 'Not Found') !== false || 
                    strpos($e->getMessage(), '404') !== false) {
                    break;
                }
                
                if ($attempt <= $max_retries) {
                    // Wait before retrying (exponential backoff)
                    $wait_time = pow(2, $attempt) * 0.5;
                    error_log("[ASAP HuggingFaceAdapter] Retry {$attempt}/{$max_retries} for model {$model} after {$wait_time}s");
                    sleep($wait_time);
                }
            }
        }
        
        // If we get here, all attempts failed
        throw $last_error ?: new \Exception("API request failed after {$max_retries} retries");
    }
} 