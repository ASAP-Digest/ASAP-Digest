<?php
/**
 * @file-marker ASAP_Digest_OpenAIAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/ai/adapters/class-openai-adapter.php
 */

namespace ASAPDigest\AI\Adapters;

use ASAPDigest\AI\Interfaces\AI_Provider_Interface;

/**
 * OpenAI API adapter
 * 
 * Interfaces with OpenAI's API for AI operations.
 */
class OpenAIAdapter implements AI_Provider_Interface {
    /**
     * @var string API key
     */
    private $api_key;
    
    /**
     * @var string Default model
     */
    private $default_model = 'gpt-3.5-turbo';
    
    /**
     * @var string API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1';
    
    /**
     * @var int Request timeout in seconds
     */
    private $timeout = 60;
    
    /**
     * Last API response data for debugging
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
            throw new \Exception('OpenAI API key is required');
        }
        
        $this->api_key = $options['api_key'];
        
        // Set model if provided
        if (!empty($options['model'])) {
            $this->default_model = $options['model'];
        }
        
        // Set timeout if provided
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
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $max_tokens = !empty($options['max_tokens']) ? (int)$options['max_tokens'] : 150;
        
        $messages = [
            ['role' => 'system', 'content' => 'You are a professional summarizer. Create a concise and accurate summary of the following text.'],
            ['role' => 'user', 'content' => $text]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => 0.5
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        throw new \Exception('Failed to generate summary');
    }
    
    /**
     * Extract entities from text
     * 
     * @param string $text Text to analyze
     * @param array $options Extraction options
     * @return array Entities
     */
    public function extract_entities($text, $options = []) {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $entity_types = !empty($options['entity_types']) ? $options['entity_types'] : ['person', 'organization', 'location', 'date', 'product'];
        
        $type_list = implode(', ', $entity_types);
        $system_prompt = "Extract entities from the following text. For each entity, provide the entity text, its type (limited to: {$type_list}), and a confidence score between 0 and 1. Return the result as a JSON array of objects with 'entity', 'type', and 'confidence' properties.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $text]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['entities']) && is_array($data['entities'])) {
                return $data['entities'];
            }
            
            // Fallback if the structure is different
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                if (isset($data[0]['entity'])) {
                    return $data;
                }
                return array_map(function($item) {
                    return [
                        'entity' => $item['text'] ?? $item['entity'] ?? '',
                        'type' => $item['type'] ?? $item['category'] ?? 'unknown',
                        'confidence' => $item['confidence'] ?? $item['score'] ?? 0.8
                    ];
                }, $data);
            }
        }
        
        throw new \Exception('Failed to extract entities');
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
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        
        if (empty($categories)) {
            // Zero-shot classification
            $system_prompt = "Classify the following text. Determine the most appropriate category and provide a confidence score between 0 and 1. Return the result as a JSON object with 'category' and 'confidence' properties.";
        } else {
            // Multi-class classification
            $category_list = implode(', ', $categories);
            $system_prompt = "Classify the following text into one or more of these categories: {$category_list}. For each matching category, provide a confidence score between 0 and 1. Return the result as a JSON array of objects with 'category' and 'confidence' properties, sorted by confidence score in descending order.";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $text]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($data['classifications'])) {
                    return $data['classifications'];
                } elseif (isset($data['category']) && isset($data['confidence'])) {
                    return [['category' => $data['category'], 'confidence' => $data['confidence']]];
                } elseif (is_array($data)) {
                    if (isset($data[0]['category']) && isset($data[0]['confidence'])) {
                        return $data;
                    }
                }
            }
        }
        
        throw new \Exception('Failed to classify content');
    }
    
    /**
     * Generate keywords from text
     * 
     * @param string $text Text to analyze
     * @param array $options Keyword generation options
     * @return array Keywords
     */
    public function generate_keywords($text, $options = []) {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $limit = !empty($options['limit']) ? (int)$options['limit'] : 10;
        
        $system_prompt = "Extract up to {$limit} keywords or key phrases from the following text. For each keyword, provide the keyword text and a relevance score between 0 and 1. Return the result as a JSON array of objects with 'keyword' and 'score' properties, sorted by score in descending order.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $text]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($data['keywords'])) {
                    return $data['keywords'];
                } elseif (is_array($data)) {
                    if (isset($data[0]['keyword']) && isset($data[0]['score'])) {
                        return $data;
                    }
                    return array_map(function($item) {
                        if (is_string($item)) {
                            return ['keyword' => $item, 'score' => 1.0];
                        }
                        return [
                            'keyword' => $item['text'] ?? $item['keyword'] ?? $item['term'] ?? '',
                            'score' => $item['score'] ?? $item['relevance'] ?? 0.8
                        ];
                    }, $data);
                }
            }
        }
        
        throw new \Exception('Failed to generate keywords');
    }
    
    /**
     * Process an image for description or analysis
     * 
     * @param string $image_url URL to image
     * @param array $options Image processing options
     * @return array Image analysis results
     */
    public function process_image($image_url, $options = []) {
        // Check if we have a vision-capable model
        $model = !empty($options['model']) ? $options['model'] : 'gpt-4-vision-preview';
        $max_tokens = !empty($options['max_tokens']) ? (int)$options['max_tokens'] : 300;
        $task = !empty($options['task']) ? $options['task'] : 'describe';
        
        switch ($task) {
            case 'describe':
                $prompt = "Describe this image in detail.";
                break;
            case 'analyze':
                $prompt = "Analyze this image and identify key elements, objects, people, text, and themes.";
                break;
            case 'extract_text':
                $prompt = "Extract all visible text from this image.";
                break;
            default:
                $prompt = $task; // Use custom prompt if provided
        }
        
        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $image_url]
                    ]
                ]
            ]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            $description = $response['choices'][0]['message']['content'];
            return [
                'description' => $description,
                'task' => $task,
                'model' => $model
            ];
        }
        
        throw new \Exception('Failed to process image');
    }
    
    /**
     * Make a request to the OpenAI API completions endpoint
     * 
     * @param array $data Request data
     * @return array Response data
     * @throws \Exception If the request fails
     */
    private function completions_request($data) {
        $endpoint = $this->api_endpoint . '/chat/completions';
        
        $args = [
            'method' => 'POST',
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
        ];
        
        $response = wp_remote_post($endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : "API returned error code: {$response_code}";
            throw new \Exception($error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        $this->update_usage_data($data);
        
        return $data;
    }
    
    /**
     * Test connection to OpenAI API
     * @return bool
     */
    public function test_connection() {
        error_log('[ASAP OpenAIAdapter] Attempting test connection.');
        $api_key = $this->api_key;
        if (!$api_key) {
            error_log('[ASAP OpenAIAdapter] Test connection failed: API key missing.');
            throw new \Exception('API key is missing.');
        }
        error_log('[ASAP OpenAIAdapter] API Key present (length: ' . strlen($api_key) . '). Making request to OpenAI.');
        $response = wp_remote_get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('[ASAP OpenAIAdapter] Test connection WP_Error: ' . $response->get_error_message());
            throw new \Exception('Connection failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('[ASAP OpenAIAdapter] Test connection response code: ' . $response_code);
        error_log('[ASAP OpenAIAdapter] Test connection response body: ' . $response_body);

        if ($response_code === 200) {
            error_log('[ASAP OpenAIAdapter] Test connection successful.');
            return true;
        }

        error_log('[ASAP OpenAIAdapter] Test connection failed with code: ' . $response_code);
        throw new \Exception('Connection failed with status code: ' . $response_code . ' - ' . $response_body);
    }
    
    /**
     * Calculate quality score for content
     * 
     * @param string $text Text to analyze
     * @param array $options Additional options for quality scoring
     * @return array Quality score results with breakdown
     */
    public function calculate_quality_score($text, $options = []) {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        
        $system_prompt = "Analyze the following content for quality. Score it on a scale of 1-10 for each of these dimensions: 
        1. Coherence (logical flow and structure)
        2. Clarity (ease of understanding)
        3. Accuracy (factual correctness)
        4. Relevance (to the apparent topic)
        5. Engagement (interesting, compelling)
        
        Return the results as a JSON object with properties for each dimension score, an overall score (average), and a brief explanation for each.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $text]
        ];
        
        $response = $this->completions_request([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        throw new \Exception('Failed to calculate quality score');
    }
    
    /**
     * Get capabilities of this provider
     * 
     * @return array Provider capabilities including supported operations
     */
    public function get_capabilities() {
        return [
            'name' => 'OpenAI',
            'features' => [
                'summarize' => true,
                'extract_entities' => true,
                'classify' => true,
                'generate_keywords' => true,
                'calculate_quality_score' => true,
                'process_image' => $this->default_model === 'gpt-4-vision-preview' || $this->default_model === 'gpt-4-turbo' || strpos($this->default_model, 'vision') !== false
            ],
            'models' => [
                'gpt-3.5-turbo' => [
                    'type' => 'chat',
                    'max_tokens' => 4096
                ],
                'gpt-4' => [
                    'type' => 'chat',
                    'max_tokens' => 8192
                ],
                'gpt-4-turbo' => [
                    'type' => 'chat',
                    'max_tokens' => 128000
                ],
                'gpt-4-vision-preview' => [
                    'type' => 'vision',
                    'max_tokens' => 128000
                ]
            ],
            'current_model' => $this->default_model
        ];
    }
    
    /**
     * Get available models from this provider
     * 
     * @return array Available models with details
     */
    public function get_models() {
        try {
            $endpoint = $this->api_endpoint . '/models';
            
            $args = [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ],
            ];
            
            $response = wp_remote_get($endpoint, $args);
            
            if (is_wp_error($response)) {
                throw new \Exception('API request failed: ' . $response->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                throw new \Exception("API returned error code: {$response_code}");
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response');
            }
            
            // Filter for chat models
            $chat_models = array_filter($data['data'], function($model) {
                return strpos($model['id'], 'gpt') === 0;
            });
            
            // Format results
            $result = [];
            foreach ($chat_models as $model) {
                $result[] = [
                    'id' => $model['id'],
                    'name' => $model['id'],
                    'created' => $model['created'],
                    'owned_by' => $model['owned_by']
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            // Return default models on error
            return array_keys($this->get_capabilities()['models']);
        }
    }
    
    /**
     * Get details about the last API response
     * 
     * @return array Response details including status and timing information
     */
    public function get_last_response() {
        return $this->last_response ?? [
            'status' => 'no_requests_made',
            'time' => 0,
            'tokens' => 0
        ];
    }
    
    /**
     * Get usage information for billing/monitoring
     * 
     * @return array Usage data including tokens used and estimated cost
     */
    public function get_usage_info() {
        return $this->usage_data;
    }
    
    /**
     * Update usage data based on response
     * 
     * @param array $response API response data
     */
    private function update_usage_data($response) {
        if (isset($response['usage'])) {
            $usage = $response['usage'];
            
            // Update token counts
            $this->usage_data['prompt_tokens'] = isset($usage['prompt_tokens']) ? $usage['prompt_tokens'] : 0;
            $this->usage_data['completion_tokens'] = isset($usage['completion_tokens']) ? $usage['completion_tokens'] : 0;
            $this->usage_data['total_tokens'] = isset($usage['total_tokens']) ? $usage['total_tokens'] : 0;
            
            // Calculate approximate cost
            // Rates may change, these are estimates
            $model = $this->default_model;
            $prompt_rate = 0.0;
            $completion_rate = 0.0;
            
            if (strpos($model, 'gpt-4') === 0) {
                $prompt_rate = 0.03 / 1000; // $0.03 per 1K tokens
                $completion_rate = 0.06 / 1000; // $0.06 per 1K tokens
            } else {
                // gpt-3.5-turbo
                $prompt_rate = 0.0015 / 1000; // $0.0015 per 1K tokens
                $completion_rate = 0.002 / 1000; // $0.002 per 1K tokens
            }
            
            $this->usage_data['cost'] = 
                ($this->usage_data['prompt_tokens'] * $prompt_rate) + 
                ($this->usage_data['completion_tokens'] * $completion_rate);
        }
    }
} 