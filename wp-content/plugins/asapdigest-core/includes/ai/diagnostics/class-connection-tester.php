<?php
/**
 * @file-marker ASAP_Digest_ConnectionTester
 * @location /wp-content/plugins/asapdigest-core/includes/ai/diagnostics/class-connection-tester.php
 */

namespace AsapDigest\AI\Diagnostics;

use AsapDigest\AI\Interfaces\AIProviderAdapter;

/**
 * Standardized connection testing for AI provider adapters.
 * Provides consistent testing methods and result formatting across providers.
 *
 * @since 1.0.0
 */
class ConnectionTester {
    /**
     * Default timeout in seconds
     *
     * @var int
     */
    private $default_timeout = 10;
    
    /**
     * Default number of retry attempts
     *
     * @var int
     */
    private $default_retry_attempts = 2;
    
    /**
     * AI provider adapter instance
     *
     * @var AIProviderAdapter
     */
    private $provider;
    
    /**
     * Configuration options
     *
     * @var array
     */
    private $options;
    
    /**
     * AIDebugger instance for logging
     *
     * @var AIDebugger|null
     */
    private $debugger;
    
    /**
     * Constructor
     *
     * @param AIProviderAdapter $provider The provider adapter to test
     * @param array $options Configuration options
     * @param AIDebugger|null $debugger Optional debugger instance
     */
    public function __construct(AIProviderAdapter $provider, $options = [], $debugger = null) {
        $this->provider = $provider;
        $this->options = wp_parse_args($options, [
            'timeout' => $this->default_timeout,
            'retry_attempts' => $this->default_retry_attempts,
        ]);
        $this->debugger = $debugger;
    }
    
    /**
     * Run the connection test
     *
     * @return array Test results
     */
    public function run_test() {
        $start_time = microtime(true);
        $success = false;
        $message = '';
        $latency = 0;
        $provider_status = [];
        $test_timestamp = current_time('mysql');
        $error = null;
        
        // Attempt to run the test with retries
        for ($attempt = 0; $attempt <= $this->options['retry_attempts']; $attempt++) {
            if ($attempt > 0) {
                // Log retry attempt
                if ($this->debugger) {
                    $this->debugger->log_request(
                        'connection_test_retry',
                        [],
                        ['attempt' => $attempt],
                        ['timeout' => $this->options['timeout']]
                    );
                }
                
                // Sleep with exponential backoff
                $sleep_time = pow(2, $attempt - 1);
                usleep($sleep_time * 500000); // Convert to microseconds (0.5s, 1s, 2s, etc.)
            }
            
            try {
                // First, check if the provider implements testConnection method
                if (method_exists($this->provider, 'test_connection')) {
                    $provider_response = $this->provider->test_connection();
                    $end_time = microtime(true);
                    $latency = round(($end_time - $start_time) * 1000); // in milliseconds
                    
                    // Check response format and extract results
                    if (is_array($provider_response) && isset($provider_response['success'])) {
                        $success = (bool) $provider_response['success'];
                        $message = isset($provider_response['message']) ? $provider_response['message'] : '';
                        $provider_status = isset($provider_response['provider_status']) ? $provider_response['provider_status'] : [];
                        
                        // Break out of retry loop if successful
                        if ($success) {
                            break;
                        }
                    } else {
                        // Handle unexpected response format
                        $success = false;
                        $message = __('Invalid response format from provider', 'asapdigest-core');
                    }
                } else {
                    // Provider doesn't implement test_connection
                    $success = false;
                    $message = __('Provider does not support connection testing', 'asapdigest-core');
                    break;
                }
            } catch (\Exception $e) {
                $error = $e;
                $end_time = microtime(true);
                $latency = round(($end_time - $start_time) * 1000);
                $success = false;
                $message = $e->getMessage();
                
                // Log exception
                if ($this->debugger) {
                    $this->debugger->log_error(
                        $message,
                        0,
                        $latency
                    );
                }
            }
        }
        
        // Prepare test results
        $results = [
            'success' => $success,
            'message' => $message,
            'latency' => $latency,
            'provider_status' => $provider_status,
            'timestamp' => $test_timestamp,
            'attempts' => $attempt,
        ];
        
        // Log test completion
        if ($this->debugger) {
            $this->debugger->log_response(
                $success ? 200 : 500,
                [],
                $results,
                $latency / 1000, // Convert ms to seconds
                $success ? '' : $message
            );
        }
        
        return $results;
    }
    
    /**
     * Test provider capabilities
     *
     * @return array Capability test results
     */
    public function test_capabilities() {
        $capabilities = [];
        $supported_methods = [
            'summarize' => __('Content Summarization', 'asapdigest-core'),
            'extract_entities' => __('Entity Extraction', 'asapdigest-core'),
            'classify' => __('Content Classification', 'asapdigest-core'),
            'generate_keywords' => __('Keyword Generation', 'asapdigest-core'),
            'calculate_quality_score' => __('Quality Scoring', 'asapdigest-core'),
        ];
        
        foreach ($supported_methods as $method => $label) {
            $capabilities[$method] = [
                'supported' => method_exists($this->provider, $method),
                'label' => $label,
                'tested' => false,
                'success' => false,
                'message' => '',
            ];
        }
        
        // Get provider's self-reported capabilities
        if (method_exists($this->provider, 'get_capabilities')) {
            try {
                $provider_capabilities = $this->provider->get_capabilities();
                if (is_array($provider_capabilities) && isset($provider_capabilities['supported_operations'])) {
                    foreach ($provider_capabilities['supported_operations'] as $operation) {
                        if (isset($capabilities[$operation])) {
                            $capabilities[$operation]['supported'] = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log capability retrieval error
                if ($this->debugger) {
                    $this->debugger->log_error(
                        sprintf(__('Error retrieving capabilities: %s', 'asapdigest-core'), $e->getMessage())
                    );
                }
            }
        }
        
        return $capabilities;
    }
    
    /**
     * Get the available provider models
     *
     * @return array Available models
     */
    public function get_available_models() {
        $models = [];
        
        if (method_exists($this->provider, 'get_models')) {
            try {
                $models = $this->provider->get_models();
            } catch (\Exception $e) {
                // Log model retrieval error
                if ($this->debugger) {
                    $this->debugger->log_error(
                        sprintf(__('Error retrieving models: %s', 'asapdigest-core'), $e->getMessage())
                    );
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Get the provider usage information
     *
     * @return array Usage information
     */
    public function get_usage_info() {
        $usage = [];
        
        if (method_exists($this->provider, 'get_usage_info')) {
            try {
                $usage = $this->provider->get_usage_info();
            } catch (\Exception $e) {
                // Log usage retrieval error
                if ($this->debugger) {
                    $this->debugger->log_error(
                        sprintf(__('Error retrieving usage info: %s', 'asapdigest-core'), $e->getMessage())
                    );
                }
            }
        }
        
        return $usage;
    }
} 