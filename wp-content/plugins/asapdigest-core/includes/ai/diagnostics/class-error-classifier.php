<?php
/**
 * @file-marker ASAP_Digest_ErrorClassifier
 * @location /wp-content/plugins/asapdigest-core/includes/ai/diagnostics/class-error-classifier.php
 */

namespace ASAPDigest\AI\Diagnostics;

/**
 * Multi-tiered error classification system for AI provider errors.
 * Categorizes errors by type and provides standardized error codes and messages.
 *
 * @since 1.0.0
 */
class ErrorClassifier {
    /**
     * Error level constants
     */
    const LEVEL_NETWORK = 1;    // Network and connection errors
    const LEVEL_AUTH = 2;       // Authentication/authorization errors
    const LEVEL_REQUEST = 3;    // Request format/validation errors
    const LEVEL_PROVIDER = 4;   // Provider-specific errors
    const LEVEL_RESPONSE = 5;   // Response processing errors
    
    /**
     * Error codes by provider and level
     *
     * @var array
     */
    private $error_codes = [
        'openai' => [
            self::LEVEL_NETWORK => [
                'timeout' => 'Connection timed out',
                'dns_failure' => 'DNS resolution failed',
                'unreachable' => 'API endpoint unreachable',
                'ssl_error' => 'SSL certificate error',
            ],
            self::LEVEL_AUTH => [
                'invalid_api_key' => 'Invalid API key',
                'expired_api_key' => 'API key expired',
                'insufficient_permissions' => 'Insufficient permissions',
                'rate_limit_exceeded' => 'Rate limit exceeded',
            ],
            self::LEVEL_REQUEST => [
                'invalid_params' => 'Invalid parameters',
                'malformed_request' => 'Malformed request',
                'unsupported_model' => 'Unsupported model specified',
                'invalid_content_type' => 'Invalid content type',
            ],
            self::LEVEL_PROVIDER => [
                'content_policy_violation' => 'Content policy violation',
                'model_overloaded' => 'Model currently overloaded',
                'quota_exceeded' => 'Usage quota exceeded',
                'content_filtered' => 'Content filtered by safety system',
            ],
            self::LEVEL_RESPONSE => [
                'invalid_response_format' => 'Invalid response format',
                'parse_error' => 'Failed to parse response',
                'missing_fields' => 'Missing required fields in response',
                'unexpected_response' => 'Unexpected response from provider',
            ],
        ],
        'anthropic' => [
            // Similar structure to OpenAI but with Anthropic-specific codes
            // ...
        ],
        'huggingface' => [
            // Similar structure to OpenAI but with HuggingFace-specific codes
            // ...
        ],
        // Generic error codes used as fallback
        'generic' => [
            self::LEVEL_NETWORK => [
                'timeout' => 'Connection timed out',
                'dns_failure' => 'DNS resolution failed',
                'unreachable' => 'API endpoint unreachable',
                'connection_error' => 'Connection error',
                'http_error' => 'HTTP error',
            ],
            self::LEVEL_AUTH => [
                'auth_error' => 'Authentication error',
                'unauthorized' => 'Unauthorized access',
                'forbidden' => 'Access forbidden',
                'rate_limit' => 'Rate limit exceeded',
            ],
            self::LEVEL_REQUEST => [
                'bad_request' => 'Bad request',
                'invalid_input' => 'Invalid input',
                'validation_error' => 'Validation error',
                'unsupported_operation' => 'Unsupported operation',
            ],
            self::LEVEL_PROVIDER => [
                'service_error' => 'Service error',
                'quota_exceeded' => 'Quota exceeded',
                'content_policy' => 'Content policy violation',
                'service_unavailable' => 'Service temporarily unavailable',
            ],
            self::LEVEL_RESPONSE => [
                'parse_error' => 'Response parse error',
                'invalid_response' => 'Invalid response',
                'incomplete_response' => 'Incomplete response',
                'unexpected_response' => 'Unexpected response',
            ],
        ],
    ];
    
    /**
     * HTTP status code to error level mapping
     *
     * @var array
     */
    private $status_code_map = [
        // Network errors
        0 => self::LEVEL_NETWORK,        // No response
        408 => self::LEVEL_NETWORK,      // Request Timeout
        502 => self::LEVEL_NETWORK,      // Bad Gateway
        503 => self::LEVEL_NETWORK,      // Service Unavailable
        504 => self::LEVEL_NETWORK,      // Gateway Timeout
        
        // Authentication errors
        401 => self::LEVEL_AUTH,         // Unauthorized
        403 => self::LEVEL_AUTH,         // Forbidden
        429 => self::LEVEL_AUTH,         // Too Many Requests
        
        // Request errors
        400 => self::LEVEL_REQUEST,      // Bad Request
        405 => self::LEVEL_REQUEST,      // Method Not Allowed
        413 => self::LEVEL_REQUEST,      // Payload Too Large
        415 => self::LEVEL_REQUEST,      // Unsupported Media Type
        422 => self::LEVEL_REQUEST,      // Unprocessable Entity
        
        // Provider errors
        500 => self::LEVEL_PROVIDER,     // Internal Server Error
        501 => self::LEVEL_PROVIDER,     // Not Implemented
        507 => self::LEVEL_PROVIDER,     // Insufficient Storage
        
        // Response errors
        // (No HTTP status codes directly map to response processing errors,
        // these are typically determined by examining response content)
    ];
    
    /**
     * Error level names
     *
     * @var array
     */
    private $level_names = [
        self::LEVEL_NETWORK => 'Network/Connection',
        self::LEVEL_AUTH => 'Authentication/Authorization',
        self::LEVEL_REQUEST => 'Request Format/Validation',
        self::LEVEL_PROVIDER => 'Provider-Specific',
        self::LEVEL_RESPONSE => 'Response Processing',
    ];
    
    /**
     * Classify an error based on provider, status code, and error message
     *
     * @param string $provider_id The provider identifier ('openai', 'anthropic', etc.)
     * @param int    $status_code HTTP status code if available
     * @param string $error_message Error message
     * @return array Classified error information
     */
    public function classify_error($provider_id, $status_code = 0, $error_message = '') {
        $provider_id = strtolower($provider_id);
        $error_level = $this->get_error_level_from_status($status_code);
        $error_code = $this->determine_error_code($provider_id, $error_level, $error_message);
        
        return [
            'provider' => $provider_id,
            'status_code' => $status_code,
            'level' => $error_level,
            'level_name' => $this->level_names[$error_level] ?? 'Unknown',
            'code' => $error_code,
            'message' => $error_message,
            'description' => $this->get_error_description($provider_id, $error_level, $error_code),
            'retry_recommended' => $this->is_retry_recommended($error_level, $error_code),
            'recovery_strategy' => $this->get_recovery_strategy($error_level, $error_code),
            'timestamp' => current_time('mysql'),
        ];
    }
    
    /**
     * Get the error level based on HTTP status code
     *
     * @param int $status_code HTTP status code
     * @return int Error level
     */
    public function get_error_level_from_status($status_code) {
        return $this->status_code_map[$status_code] ?? self::LEVEL_PROVIDER;
    }
    
    /**
     * Determine the best error code based on provider, level, and message
     *
     * @param string $provider_id Provider identifier
     * @param int    $error_level Error level
     * @param string $error_message Error message
     * @return string Best matching error code
     */
    private function determine_error_code($provider_id, $error_level, $error_message) {
        // Default to generic provider if specific one not found
        $provider_codes = $this->error_codes[$provider_id] ?? $this->error_codes['generic'];
        
        // Get codes for the identified error level
        $level_codes = $provider_codes[$error_level] ?? [];
        
        if (empty($level_codes)) {
            return 'unknown_error';
        }
        
        // Try to match error message with known patterns
        foreach ($level_codes as $code => $description) {
            if (strpos(strtolower($error_message), strtolower($description)) !== false) {
                return $code;
            }
        }
        
        // If no match, return the first code for this level
        return array_key_first($level_codes);
    }
    
    /**
     * Get the error description for a specific code
     *
     * @param string $provider_id Provider identifier
     * @param int    $error_level Error level
     * @param string $error_code Error code
     * @return string Error description
     */
    private function get_error_description($provider_id, $error_level, $error_code) {
        // Try provider-specific description
        if (isset($this->error_codes[$provider_id][$error_level][$error_code])) {
            return $this->error_codes[$provider_id][$error_level][$error_code];
        }
        
        // Fall back to generic description
        if (isset($this->error_codes['generic'][$error_level][$error_code])) {
            return $this->error_codes['generic'][$error_level][$error_code];
        }
        
        return __('Unknown error', 'asapdigest-core');
    }
    
    /**
     * Determine if retry is recommended for this error
     *
     * @param int    $error_level Error level
     * @param string $error_code Error code
     * @return bool Whether retry is recommended
     */
    private function is_retry_recommended($error_level, $error_code) {
        // Usually retry network errors
        if ($error_level === self::LEVEL_NETWORK) {
            return true;
        }
        
        // Usually retry provider errors like overloading
        if ($error_level === self::LEVEL_PROVIDER) {
            $no_retry_codes = ['quota_exceeded', 'content_policy_violation', 'content_filtered'];
            return !in_array($error_code, $no_retry_codes);
        }
        
        // Sometimes retry auth errors (rate limits might be temporary)
        if ($error_level === self::LEVEL_AUTH) {
            return $error_code === 'rate_limit_exceeded' || $error_code === 'rate_limit';
        }
        
        // Rarely retry request or response errors as they usually need fixes
        return false;
    }
    
    /**
     * Get recommended recovery strategy for this error
     *
     * @param int    $error_level Error level
     * @param string $error_code Error code
     * @return string Recovery strategy recommendation
     */
    private function get_recovery_strategy($error_level, $error_code) {
        switch ($error_level) {
            case self::LEVEL_NETWORK:
                return __('Retry with exponential backoff. If persistent, check connection or try a different provider.', 'asapdigest-core');
                
            case self::LEVEL_AUTH:
                if (in_array($error_code, ['rate_limit_exceeded', 'rate_limit'])) {
                    return __('Wait and retry later. Consider implementing rate limiting or adjusting request frequency.', 'asapdigest-core');
                }
                return __('Verify API key is valid and has sufficient permissions.', 'asapdigest-core');
                
            case self::LEVEL_REQUEST:
                return __('Check request format and parameters. Consult provider documentation for correct usage.', 'asapdigest-core');
                
            case self::LEVEL_PROVIDER:
                if ($error_code === 'quota_exceeded') {
                    return __('Upgrade plan or wait for quota reset. Consider implementing usage limits.', 'asapdigest-core');
                } elseif (in_array($error_code, ['content_policy_violation', 'content_filtered'])) {
                    return __('Modify content to comply with provider policies. Consider content pre-filtering.', 'asapdigest-core');
                }
                return __('Try a different provider or retry later. Check provider status page for outages.', 'asapdigest-core');
                
            case self::LEVEL_RESPONSE:
                return __('Check response parsing logic. Ensure compatibility with provider API version.', 'asapdigest-core');
                
            default:
                return __('Investigate error details for appropriate recovery steps.', 'asapdigest-core');
        }
    }
    
    /**
     * Get all error levels with names
     *
     * @return array Error levels with names
     */
    public function get_error_levels() {
        return $this->level_names;
    }
    
    /**
     * Get all error codes for a specific provider and level
     *
     * @param string $provider_id Provider identifier
     * @param int    $error_level Error level
     * @return array Error codes with descriptions
     */
    public function get_error_codes_for_level($provider_id, $error_level) {
        $provider_id = strtolower($provider_id);
        
        if (isset($this->error_codes[$provider_id][$error_level])) {
            return $this->error_codes[$provider_id][$error_level];
        }
        
        return $this->error_codes['generic'][$error_level] ?? [];
    }
} 