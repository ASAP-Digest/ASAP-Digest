# ASAP Digest AJAX Handlers System

## Overview

ASAP Digest implements a standardized AJAX handler system that follows the WordPress AJAX Handler Standardization Protocol. The system is designed to provide consistent, secure, and maintainable AJAX endpoints throughout the plugin.

## File Structure

```
includes/ajax/
├── README.md               # This documentation file
├── bootstrap.php           # Main entry point for initializing AJAX system
├── class-ajax-manager.php  # Central manager that registers all handlers
├── class-base-ajax.php     # Abstract base class for all handlers
├── admin/                  # Admin-specific AJAX handlers
│   ├── class-admin-ajax.php       # General admin AJAX operations
│   ├── class-ai-ajax.php          # AI-related AJAX operations
│   ├── class-content-ajax.php     # Content management AJAX operations
│   ├── class-quality-ajax.php     # Content quality AJAX operations
│   └── class-source-ajax.php      # Content source AJAX operations
└── user/                   # User-specific AJAX handlers
    └── class-user-actions-ajax.php   # User management AJAX operations
```

## Key Components

### AJAX_Manager (class-ajax-manager.php)

Central manager that registers all AJAX handler classes and coordinates their initialization.

```php
$ajax_manager = new \AsapDigest\Core\Ajax\AJAX_Manager($core);
$ajax_manager->register_handler(new \AsapDigest\Core\Ajax\Admin\Admin_Ajax($core));
$ajax_manager->init();
```

### Base_AJAX (class-base-ajax.php)

Abstract base class that all AJAX handlers extend. Provides common functionality:

- Security verification (nonce, capability)
- Standardized response methods (success/error)
- Parameter validation
- Error handling with logging

```php
class My_Handler extends Base_AJAX {
    protected $capability = 'manage_options';
    protected $nonce_action = 'my_nonce_action';
    
    protected function register_actions() {
        add_action('wp_ajax_my_action', [$this, 'handle_my_action']);
    }
    
    public function handle_my_action() {
        $this->verify_request();
        // Handle the AJAX request
        $this->send_success(['message' => 'Success!']);
    }
}
```

## Security Features

1. **Nonce Verification**: All requests are verified against a registered nonce.
2. **Capability Checks**: Access is restricted based on user capabilities.
3. **Input Validation**: All input parameters are validated and sanitized.
4. **Error Logging**: Errors are logged for debugging and security monitoring.

## Response Format

All AJAX responses follow a consistent format:

### Success Response

```json
{
    "success": true,
    "data": {
        "message": "Operation successful",
        "additional_data": "..."
    }
}
```

### Error Response

```json
{
    "success": false,
    "data": {
        "message": "Error message",
        "code": "error_code",
        "details": "Additional error details (debug mode only)"
    }
}
```

## Adding New AJAX Handlers

1. Create a new class extending `Base_AJAX`
2. Define protected properties (`$capability`, `$nonce_action`)
3. Implement the `register_actions()` method to add wp_ajax_* hooks
4. Create handler methods that call `verify_request()` and use `send_success()`/`send_error()`
5. Register the handler in `bootstrap.php`

## Example Usage

### Client-side (JavaScript)

```javascript
jQuery.ajax({
    url: ajax_object.ajax_url,
    type: 'POST',
    data: {
        action: 'asap_get_content_details',
        content_id: 123,
        nonce: ajax_object.content_nonce
    },
    success: function(response) {
        if (response.success) {
            // Handle success
            console.log(response.data);
        } else {
            // Handle error
            console.error(response.data.message);
        }
    }
});
```

### Server-side (PHP)

```php
// In your handler class:
public function handle_get_content_details() {
    // Verify request (nonce and capability)
    $this->verify_request();
    
    // Validate required parameters
    $this->validate_params(['content_id']);
    
    try {
        // Get content ID
        $content_id = intval($_POST['content_id']);
        
        // Process the request...
        $content = $this->get_content($content_id);
        
        // Send success response
        $this->send_success(['content' => $content]);
    } catch (\Exception $e) {
        // Log error
        ErrorLogger::log('ajax', 'content_details_error', $e->getMessage());
        
        // Send error response
        $this->send_error([
            'message' => __('An error occurred', 'asapdigest-core'),
            'code' => 'processing_error'
        ]);
    }
}
```

## Troubleshooting

If an AJAX handler is not working as expected:

1. Check browser console for JavaScript errors
2. Verify the nonce is correctly generated and passed
3. Ensure the user has the required capability
4. Review PHP error logs for backend issues
5. Validate that the handler is properly registered in the bootstrap file 