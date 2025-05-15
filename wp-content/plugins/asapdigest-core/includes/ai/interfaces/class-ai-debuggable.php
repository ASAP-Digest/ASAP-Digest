<?php
/**
 * @file-marker ASAP_Digest_AIDebuggable
 * @location /wp-content/plugins/asapdigest-core/includes/ai/interfaces/class-ai-debuggable.php
 */

namespace ASAPDigest\AI\Interfaces;

/**
 * Interface for debugging AI provider adapters.
 * Ensures consistent debugging capabilities across all AI providers.
 * 
 * @since 1.0.0
 */
interface AIDebuggable {
    /**
     * Enable debug mode for detailed logging
     * 
     * @return bool Whether debug mode was successfully enabled
     */
    public function enable_debug_mode();
    
    /**
     * Disable debug mode
     * 
     * @return bool Whether debug mode was successfully disabled
     */
    public function disable_debug_mode();
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool Whether debug mode is currently enabled
     */
    public function is_debug_enabled();
    
    /**
     * Get details about the last API request
     * 
     * @return array Request details including endpoint, headers, and payload
     * with sensitive information (like API keys) redacted
     */
    public function get_last_request_details();
    
    /**
     * Get details about the last API response
     * 
     * @return array Response details including status, headers, and body
     */
    public function get_last_response_details();
    
    /**
     * Clear the debug log
     * 
     * @return bool Whether the debug log was successfully cleared
     */
    public function clear_debug_log();
} 