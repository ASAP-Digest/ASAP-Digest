<?php
/**
 * @file-marker ASAP_Digest_AnthropicAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/ai/adapters/class-anthropic-adapter.php
 */

namespace ASAPDigest\AI\Adapters;

use ASAPDigest\AI\Interfaces\AI_Provider_Interface;

/**
 * Anthropic API adapter
 * 
 * Interfaces with Anthropic's Claude API for AI operations.
 */
class AnthropicAdapter implements AI_Provider_Interface {
    /**
     * @var string API key
     */
    private $api_key;
    
    /**
     * @var string Default model
     */
    private $default_model = 'claude-3-haiku-20240307';
    
    /**
     * @var string API endpoint
     */
    private $api_endpoint = 'https://api.anthropic.com/v1';
    
    /**
     * @var string Anthropic API version
     */
    private $api_version = '2023-06-01';
    
    /**
     * @var int Request timeout in seconds
     */
    private $timeout = 60;
    
    /**
     * @var string|null Last raw response for debugging
     */
    private $last_response = null;
    
    /**
     * @var array Usage data
     */
    private $usage_data = [];
    
    /**
     * Constructor
     * 
     * @param array $options Configuration options
     */
    public function __construct($options = []) {
        if (empty($options['api_key'])) {
            throw new \Exception('Anthropic API key is required');
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
        
        // Set API version if provided
        if (!empty($options['api_version'])) {
            $this->api_version = $options['api_version'];
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
        try {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $max_tokens = !empty($options['max_tokens']) ? (int)$options['max_tokens'] : 150;
        
        $system = "You are a professional summarizer. Create a concise and accurate summary of the following text.";
        $prompt = $text;
        
        $response = $this->messages_request([
            'model' => $model,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => 0.5
        ]);
            $this->update_usage_data($response);
        if (isset($response['content'][0]['text'])) {
            return trim($response['content'][0]['text']);
        }
        
            error_log('[ASAP AnthropicAdapter][summarize] No summary returned for input: ' . substr($text, 0, 100));
            throw new \Exception('Failed to generate summary: No summary returned.');
        } catch (\Exception $e) {
            error_log('[ASAP AnthropicAdapter][summarize] Exception: ' . $e->getMessage() . ' | Input: ' . substr($text, 0, 100));
            throw new \Exception('Anthropic summarize error: ' . $e->getMessage());
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
        try {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $entity_types = !empty($options['entity_types']) ? $options['entity_types'] : ['person', 'organization', 'location', 'date', 'product'];
        
        $type_list = implode(', ', $entity_types);
        $system = "You are an expert at named entity recognition.";
        $prompt = "Extract entities from the following text. For each entity, provide the entity text, its type (limited to: {$type_list}), and a confidence score between 0 and 1.\n\nRespond with ONLY a JSON array of objects with 'entity', 'type', and 'confidence' properties. Do not include any explanation or text outside the JSON array.\n\nText to analyze:\n\n{$text}";
        
        $response = $this->messages_request([
            'model' => $model,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3
        ]);
            $this->update_usage_data($response);
        if (isset($response['content'][0]['text'])) {
            $content = $response['content'][0]['text'];
            
            // Extract JSON from response
            preg_match('/\[.*\]/s', $content, $matches);
            if (!empty($matches[0])) {
                $json_str = $matches[0];
            } else {
                $json_str = $content;
            }
            
            $data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Normalize the response format
                return array_map(function($item) {
                    return [
                        'entity' => $item['entity'] ?? $item['text'] ?? '',
                        'type' => $item['type'] ?? $item['category'] ?? 'unknown',
                        'confidence' => $item['confidence'] ?? $item['score'] ?? 0.8
                    ];
                }, $data);
            }
        }
        
            error_log('[ASAP AnthropicAdapter][extract_entities] No entities returned for input: ' . substr($text, 0, 100));
            throw new \Exception('Failed to extract entities: No valid JSON array returned.');
        } catch (\Exception $e) {
            error_log('[ASAP AnthropicAdapter][extract_entities] Exception: ' . $e->getMessage() . ' | Input: ' . substr($text, 0, 100));
            throw new \Exception('Anthropic extract_entities error: ' . $e->getMessage());
        }
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
        try {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $system = "You are a content classifier with expertise in categorizing text.";
        
        if (empty($categories)) {
            // Zero-shot classification
            $prompt = "Classify the following text. Determine the most appropriate category and provide a confidence score between 0 and 1.\n\nRespond with ONLY a JSON object with 'category' and 'confidence' properties. Do not include any explanation or text outside the JSON object.\n\nText to classify:\n\n{$text}";
        } else {
            // Multi-class classification
            $category_list = implode(', ', $categories);
            $prompt = "Classify the following text into one or more of these categories: {$category_list}. For each matching category, provide a confidence score between 0 and 1.\n\nRespond with ONLY a JSON array of objects with 'category' and 'confidence' properties, sorted by confidence score in descending order. Do not include any explanation or text outside the JSON array.\n\nText to classify:\n\n{$text}";
        }
        
        $response = $this->messages_request([
            'model' => $model,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3
        ]);
            $this->update_usage_data($response);
        if (isset($response['content'][0]['text'])) {
            $content = $response['content'][0]['text'];
            
            // Extract JSON from response
            preg_match('/(\{.*\}|\[.*\])/s', $content, $matches);
            if (!empty($matches[0])) {
                $json_str = $matches[0];
            } else {
                $json_str = $content;
            }
            
            $data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($data['category']) && isset($data['confidence'])) {
                    // Single category response
                    return [['category' => $data['category'], 'confidence' => (float)$data['confidence']]];
                } elseif (is_array($data)) {
                    // Multiple categories response
                    if (isset($data[0]['category']) && isset($data[0]['confidence'])) {
                        return $data;
                    }
                }
            }
        }
        
            error_log('[ASAP AnthropicAdapter][classify] No valid classification returned for input: ' . substr($text, 0, 100));
            throw new \Exception('Failed to classify content: No valid JSON returned.');
        } catch (\Exception $e) {
            error_log('[ASAP AnthropicAdapter][classify] Exception: ' . $e->getMessage() . ' | Input: ' . substr($text, 0, 100));
            throw new \Exception('Anthropic classify error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate keywords from text
     * 
     * @param string $text Text to analyze
     * @param array $options Keyword generation options
     * @return array Keywords
     */
    public function generate_keywords($text, $options = []) {
        try {
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $limit = !empty($options['limit']) ? (int)$options['limit'] : 10;
        
        $system = "You are a keyword extraction specialist.";
        $prompt = "Extract up to {$limit} keywords or key phrases from the following text. For each keyword, provide the keyword text and a relevance score between 0 and 1.\n\nRespond with ONLY a JSON array of objects with 'keyword' and 'score' properties, sorted by score in descending order. Do not include any explanation or text outside the JSON array.\n\nText to analyze:\n\n{$text}";
        
        $response = $this->messages_request([
            'model' => $model,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3
        ]);
            $this->update_usage_data($response);
        if (isset($response['content'][0]['text'])) {
            $content = $response['content'][0]['text'];
            
            // Extract JSON from response
            preg_match('/\[.*\]/s', $content, $matches);
            if (!empty($matches[0])) {
                $json_str = $matches[0];
            } else {
                $json_str = $content;
            }
            
            $data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Normalize the response format
                return array_map(function($item) {
                    return [
                        'keyword' => $item['keyword'] ?? $item['text'] ?? $item['term'] ?? '',
                        'score' => $item['score'] ?? $item['relevance'] ?? 0.8
                    ];
                }, $data);
            }
        }
        
            error_log('[ASAP AnthropicAdapter][generate_keywords] No keywords returned for input: ' . substr($text, 0, 100));
            throw new \Exception('Failed to generate keywords: No valid JSON array returned.');
        } catch (\Exception $e) {
            error_log('[ASAP AnthropicAdapter][generate_keywords] Exception: ' . $e->getMessage() . ' | Input: ' . substr($text, 0, 100));
            throw new \Exception('Anthropic generate_keywords error: ' . $e->getMessage());
        }
    }
    
    /**
     * Make a request to the Anthropic API messages endpoint
     * 
     * @param array $data Request data
     * @return array Response data
     * @throws \Exception If the request fails
     */
    private function messages_request($data) {
        $endpoint = $this->api_endpoint . '/messages';
        $args = [
            'method' => 'POST',
            'timeout' => $this->timeout,
            'headers' => [
                'x-api-key' => $this->api_key,
                'anthropic-version' => $this->api_version,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
        ];
        $response = wp_remote_post($endpoint, $args);
        $this->last_response = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
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
        $this->last_response = $body;
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        return $data;
    }
    
    /**
     * Test connection to Anthropic API
     * @return bool
     */
    public function test_connection() {
        error_log('[ASAP AnthropicAdapter] Attempting test connection.');
        $api_key = $this->api_key;
        if (!$api_key) {
            error_log('[ASAP AnthropicAdapter] Test connection failed: API key missing.');
            throw new \Exception('API key is missing.');
        }
        error_log('[ASAP AnthropicAdapter] API Key present (length: ' . strlen($api_key) . '). Making request to Anthropic.');
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'method'  => 'POST',
            'headers' => [
                'x-api-key'        => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'     => 'application/json'
            ],
            'body'    => json_encode([
                'model' => 'claude-3-haiku-20240307', // Smallest model for a light test
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'Hello']]
            ]),
            'timeout' => 15, // Added timeout
        ]);

        $this->last_response = wp_remote_retrieve_body($response); // Store for debugging
        error_log('[ASAP AnthropicAdapter] Raw test connection response: ' . $this->last_response);

        if (is_wp_error($response)) {
            error_log('[ASAP AnthropicAdapter] Test connection WP_Error: ' . $response->get_error_message());
            throw new \Exception('Connection failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        error_log('[ASAP AnthropicAdapter] Test connection response code: ' . $response_code);

        if ($response_code === 200) {
            error_log('[ASAP AnthropicAdapter] Test connection successful.');
            return true;
        }

        error_log('[ASAP AnthropicAdapter] Test connection failed with code: ' . $response_code);
        throw new \Exception('Connection failed with status code: ' . $response_code . ' - ' . $this->last_response);
    }
    
    /**
     * Get the last raw response (for debugging)
     * @return string|null
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
        $model = !empty($options['model']) ? $options['model'] : $this->default_model;
        $max_tokens = !empty($options['max_tokens']) ? $options['max_tokens'] : 100;
        
        $prompt = "Please analyze the following content and provide a quality score between 0 and 100. Include scores for readability, engagement, coherence, and relevance. Also provide 2-3 specific improvement suggestions:\n\n" . $text;
        
        try {
            $response = $this->send_api_request([
                'model' => $model,
                'prompt' => $prompt,
                'max_tokens' => $max_tokens
            ]);
            
            $this->last_response = $response;
            $this->update_usage_data($response);
            
            // In a real implementation, parse the response to extract scores
            // For now, return a placeholder response
            return [
                'overall' => 75,
                'components' => [
                    'readability' => 80,
                    'engagement' => 70,
                    'coherence' => 75,
                    'relevance' => 75,
                ],
                'suggestions' => [
                    'Consider breaking up long paragraphs for better readability.',
                    'Add more specific examples to support key points.'
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
            'max_input_tokens' => 200000, // Claude supports very long contexts
            'supports_streaming' => true,
            'supports_functions' => true,
            'supports_vision' => false,
        ];
    }
    
    /**
     * Get available models
     * 
     * @return array List of models with capabilities
     */
    public function get_models() {
        return [
            'claude-3-opus' => [
                'max_tokens' => 200000,
                'description' => 'Most powerful Claude model for complex tasks',
                'cost_per_1k_tokens' => [
                    'input' => 0.015,
                    'output' => 0.075,
                ],
            ],
            'claude-3-sonnet' => [
                'max_tokens' => 200000,
                'description' => 'Balanced model for most tasks',
                'cost_per_1k_tokens' => [
                    'input' => 0.003,
                    'output' => 0.015,
                ],
            ],
            'claude-3-haiku' => [
                'max_tokens' => 200000,
                'description' => 'Fast and cost-effective model',
                'cost_per_1k_tokens' => [
                    'input' => 0.00025,
                    'output' => 0.00125,
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
     * Send request to Anthropic API
     * 
     * @param array $params Request parameters
     * @return array API response
     * @throws \Exception on error
     */
    private function send_api_request($params) {
        // In real implementation, this would send request to Anthropic API
        // This is a placeholder implementation
        
        if (empty($this->api_key)) {
            throw new \Exception('API key is not set');
        }
        
        // Simulate API response
        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Sample response content'
                    ]
                ]
            ],
            'usage' => [
                'prompt_tokens' => 50,
                'completion_tokens' => 100,
                'total_tokens' => 150
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
        if (!empty($response['usage'])) {
            $this->usage_data['prompt_tokens'] = ($this->usage_data['prompt_tokens'] ?? 0) + $response['usage']['prompt_tokens'];
            $this->usage_data['completion_tokens'] = ($this->usage_data['completion_tokens'] ?? 0) + $response['usage']['completion_tokens'];
            $this->usage_data['total_tokens'] = ($this->usage_data['total_tokens'] ?? 0) + $response['usage']['total_tokens'];
            
            // Calculate approximate cost
            $model = $this->default_model;
            if (!empty($response['model'])) {
                $model = $response['model'];
            }
            $models = $this->get_models();
            if (isset($models[$model])) {
                $input_cost = $models[$model]['cost_per_1k_tokens']['input'] * $response['usage']['prompt_tokens'] / 1000;
                $output_cost = $models[$model]['cost_per_1k_tokens']['output'] * $response['usage']['completion_tokens'] / 1000;
                $this->usage_data['cost'] = ($this->usage_data['cost'] ?? 0) + $input_cost + $output_cost;
            }
        }
    }
} 