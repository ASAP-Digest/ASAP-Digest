<?php
/**
 * @file-marker ASAP_Digest_AIDebugger
 * @location /wp-content/plugins/asapdigest-core/includes/ai/diagnostics/class-ai-debugger.php
 */

namespace AsapDigest\AI\Diagnostics;

use AsapDigest\Core\ErrorLogger;

/**
 * Centralized debugging system for AI provider interactions.
 * Captures, logs, and manages diagnostic information for all AI provider calls.
 *
 * @since 1.0.0
 */
class AIDebugger {
    /**
     * Debug mode status
     *
     * @var bool
     */
    private $debug_enabled = false;
    
    /**
     * The provider name (for logging context)
     *
     * @var string
     */
    private $provider_name;
    
    /**
     * Log of API requests and responses
     *
     * @var array
     */
    private $log = [];
    
    /**
     * Maximum number of entries to keep in the log
     *
     * @var int
     */
    private $max_log_entries = 50;
    
    /**
     * Last request details
     *
     * @var array|null
     */
    private $last_request = null;
    
    /**
     * Last response details
     *
     * @var array|null
     */
    private $last_response = null;

    /**
     * ErrorLogger instance (if available)
     *
     * @var ErrorLogger|null
     */
    private $error_logger = null;
    
    /**
     * Constructor
     *
     * @param string $provider_name The name of the AI provider
     * @param ErrorLogger|null $error_logger Optional instance of ErrorLogger
     */
    public function __construct($provider_name, $error_logger = null) {
        $this->provider_name = sanitize_text_field($provider_name);
        $this->error_logger = $error_logger;
        
        // Check if debug mode is enabled via constant or option
        // Using fully qualified constant name in the global namespace
        if (defined('ASAP_AI_DEBUG')) {
            $this->debug_enabled = (bool) constant('ASAP_AI_DEBUG');
        }
        
        if (!$this->debug_enabled) {
            $options = get_option('asap_ai_settings', []);
            $this->debug_enabled = isset($options['debug_mode']) && $options['debug_mode'];
        }
    }
    
    /**
     * Enable debug mode
     *
     * @return bool Always returns true
     */
    public function enable_debug() {
        $this->debug_enabled = true;
        return true;
    }
    
    /**
     * Disable debug mode
     *
     * @return bool Always returns true
     */
    public function disable_debug() {
        $this->debug_enabled = false;
        return true;
    }
    
    /**
     * Check if debug mode is enabled
     *
     * @return bool Debug status
     */
    public function is_debug_enabled() {
        return $this->debug_enabled;
    }
    
    /**
     * Log an API request
     *
     * @param string $endpoint The API endpoint
     * @param array  $headers  Request headers
     * @param mixed  $payload  Request payload
     * @param array  $options  Additional options
     * @return void
     */
    public function log_request($endpoint, $headers, $payload, $options = []) {
        $sanitized_headers = $this->sanitize_headers($headers);
        $sanitized_payload = $this->sanitize_payload($payload);
        
        $request = [
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint,
            'headers' => $sanitized_headers,
            'payload' => $sanitized_payload,
            'options' => $options,
        ];
        
        $this->last_request = $request;
        
        if ($this->debug_enabled) {
            $this->add_to_log('request', $request);
            $this->maybe_log_to_error_logger('request', $request);
        }
    }
    
    /**
     * Log an API response
     *
     * @param int    $status_code HTTP status code
     * @param array  $headers     Response headers
     * @param mixed  $body        Response body
     * @param float  $duration    Request duration in seconds
     * @param string $error       Error message if applicable
     * @return void
     */
    public function log_response($status_code, $headers, $body, $duration, $error = '') {
        $response = [
            'timestamp' => current_time('mysql'),
            'status_code' => $status_code,
            'headers' => $headers,
            'body' => $body,
            'duration' => $duration,
            'error' => $error,
        ];
        
        $this->last_response = $response;
        
        if ($this->debug_enabled) {
            $this->add_to_log('response', $response);
            $this->maybe_log_to_error_logger('response', $response, !empty($error));
        }
        
        // Always log errors, even if debug mode is off
        if (!empty($error)) {
            $this->log_error($error, $status_code, $duration);
        }
    }
    
    /**
     * Log an error
     *
     * @param string $message     Error message
     * @param int    $status_code HTTP status code if applicable
     * @param float  $duration    Request duration if applicable
     * @return void
     */
    public function log_error($message, $status_code = 0, $duration = 0) {
        $error = [
            'timestamp' => current_time('mysql'),
            'provider' => $this->provider_name,
            'message' => $message,
            'status_code' => $status_code,
            'duration' => $duration,
            'request' => $this->last_request,
        ];
        
        // Always add errors to the log regardless of debug mode
        $this->add_to_log('error', $error);
        
        // Log to ErrorLogger if available
        if ($this->error_logger) {
            $this->error_logger->log(
                'error',
                sprintf('AI Provider Error (%s): %s', $this->provider_name, $message),
                $error
            );
        } else {
            // Fallback to standard error logging
            error_log(sprintf(
                'ASAP AI Provider Error (%s): %s (Status: %d)',
                $this->provider_name,
                $message,
                $status_code
            ));
        }
    }
    
    /**
     * Get details about the last API request
     *
     * @return array|null Request details or null if no request has been logged
     */
    public function get_last_request_details() {
        return $this->last_request;
    }
    
    /**
     * Get details about the last API response
     *
     * @return array|null Response details or null if no response has been logged
     */
    public function get_last_response_details() {
        return $this->last_response;
    }
    
    /**
     * Get the full debug log
     *
     * @return array Debug log
     */
    public function get_log() {
        return $this->log;
    }
    
    /**
     * Clear the debug log
     *
     * @return bool Always returns true
     */
    public function clear_log() {
        $this->log = [];
        return true;
    }
    
    /**
     * Add an entry to the debug log
     *
     * @param string $type The log entry type (request, response, error)
     * @param array  $data The log entry data
     * @return void
     */
    private function add_to_log($type, $data) {
        $entry = [
            'type' => $type,
            'data' => $data,
        ];
        
        array_unshift($this->log, $entry);
        
        // Trim log if it exceeds max entries
        if (count($this->log) > $this->max_log_entries) {
            $this->log = array_slice($this->log, 0, $this->max_log_entries);
        }
    }
    
    /**
     * Sanitize request headers to remove sensitive information
     *
     * @param array $headers The headers to sanitize
     * @return array Sanitized headers
     */
    private function sanitize_headers($headers) {
        $sanitized = [];
        $sensitive_keys = ['authorization', 'api-key', 'x-api-key'];
        
        foreach ($headers as $key => $value) {
            $lower_key = strtolower($key);
            
            if (in_array($lower_key, $sensitive_keys)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize request payload to remove sensitive information
     *
     * @param mixed $payload The payload to sanitize
     * @return mixed Sanitized payload
     */
    private function sanitize_payload($payload) {
        // If payload is an array or object, process it
        if (is_array($payload) || is_object($payload)) {
            $payload_array = (array) $payload;
            $sanitized = [];
            
            foreach ($payload_array as $key => $value) {
                // Redact sensitive keys
                if (in_array(strtolower($key), ['api_key', 'apikey', 'key', 'token', 'secret'])) {
                    $sanitized[$key] = '[REDACTED]';
                } 
                // Recursively sanitize nested structures
                elseif (is_array($value) || is_object($value)) {
                    $sanitized[$key] = $this->sanitize_payload($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            
            return $sanitized;
        }
        
        // Return non-array/object payloads as-is
        return $payload;
    }
    
    /**
     * Log to the ErrorLogger if available
     *
     * @param string $type     Log entry type
     * @param array  $data     Log entry data
     * @param bool   $is_error Whether this is an error entry
     * @return void
     */
    private function maybe_log_to_error_logger($type, $data, $is_error = false) {
        if (!$this->error_logger) {
            return;
        }
        
        $level = $is_error ? 'error' : 'debug';
        $message = sprintf(
            'AI Provider %s (%s): %s',
            $type,
            $this->provider_name,
            $is_error ? 'Error' : 'Info'
        );
        
        $this->error_logger->log($level, $message, $data);
    }
} 