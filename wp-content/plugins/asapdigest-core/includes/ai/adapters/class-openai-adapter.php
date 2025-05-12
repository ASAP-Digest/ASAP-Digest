<?php
/**
 * @file-marker ASAP_Digest_OpenAIAdapter
 * @location /wp-content/plugins/asapdigest-core/includes/ai/adapters/class-openai-adapter.php
 */

namespace AsapDigest\AI\Adapters;

use AsapDigest\AI\Interfaces\AIProviderAdapter;

/**
 * OpenAI API adapter
 * 
 * Interfaces with OpenAI's API for AI operations.
 */
class OpenAIAdapter implements AIProviderAdapter {
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
} 