I'll create a comprehensive plugin plan that merges the current and proposed structures, with clear MVP indicators and technical details. Let me organize this in a structured way.

# ASAP Digest Core Plugin - Implementation Plan

## 🚀 MVP LAUNCH REQUIREMENTS

### 1. Core Infrastructure Reorganization (MVP - CRITICAL)
```
asapdigest-core/
├── includes/
│   ├── class-core.php             # Main plugin initialization
│   ├── class-activator.php        # Activation/deactivation hooks
│   ├── class-database.php         # Database management
│   ├── class-better-auth.php      # Better Auth integration
│   └── api/
│       ├── class-rest-base.php    # Base REST controller
│       ├── class-digest.php       # Digest endpoints
│       └── class-auth.php         # Auth endpoints
├── admin/
│   ├── class-admin.php           # Admin initialization
│   ├── class-central-command.php # Central Command dashboard
│   └── views/
│       ├── dashboard.php         # Main dashboard view
│       └── settings.php         # Settings view
└── asapdigest-core.php          # Plugin bootstrap
```

**Technical Implementation:**
```php
// includes/class-core.php
class ASAP_Digest_Core {
    private static $instance = null;
    private $database;
    private $better_auth;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->define_hooks();
    }
    
    private function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'class-database.php';
        require_once plugin_dir_path(__FILE__) . 'class-better-auth.php';
        // ... other dependencies
    }
}

// Bootstrap in main plugin file
add_action('plugins_loaded', ['ASAP_Digest_Core', 'get_instance']);
```

### 2. Database Management System (MVP - CRITICAL)
```php
// includes/class-database.php
class ASAP_Digest_Database {
    private $tables = [
        'asap_digests',
        'asap_notifications',
        'asap_usage_metrics',
        'asap_service_costs',
        'ba_wp_user_map'
    ];
    
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Usage Metrics Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_usage_metrics (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            metric_type VARCHAR(50) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            cost DECIMAL(10,4) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX user_metric (user_id, metric_type)
        ) $charset_collate;";
        
        // Service Costs Table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}asap_service_costs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_name VARCHAR(50) NOT NULL,
            cost_per_unit DECIMAL(10,4) NOT NULL,
            markup_percentage DECIMAL(5,2) NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY service (service_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

### 3. Central Command Dashboard (MVP - CRITICAL)
```php
// admin/class-central-command.php
class ASAP_Digest_Central_Command {
    public function register_menu() {
        add_menu_page(
            '⚡️ Central Command',
            '⚡️ Central Command',
            'manage_options',
            'asap-central-command',
            [$this, 'render_dashboard'],
            'dashicons-superhero',
            3
        );
        
        // Add submenus with lower priority
        add_submenu_page(
            'asap-central-command',
            'Usage Analytics',
            'Usage Analytics',
            'manage_options',
            'asap-usage-analytics',
            [$this, 'render_usage_analytics'],
            30
        );
    }
    
    public function render_dashboard() {
        include plugin_dir_path(__FILE__) . 'views/dashboard.php';
    }
}
```

### 4. Usage Tracking System (MVP - CRITICAL)
```php
// includes/class-usage-tracker.php
class ASAP_Digest_Usage_Tracker {
    public function track_usage($user_id, $metric_type, $value) {
        global $wpdb;
        
        $cost_data = $this->get_service_cost($metric_type);
        $cost = $value * $cost_data->cost_per_unit;
        
        return $wpdb->insert(
            $wpdb->prefix . 'asap_usage_metrics',
            [
                'user_id' => $user_id,
                'metric_type' => $metric_type,
                'value' => $value,
                'cost' => $cost
            ],
            ['%d', '%s', '%f', '%f']
        );
    }
    
    private function get_service_cost($service_name) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT cost_per_unit, markup_percentage 
             FROM {$wpdb->prefix}asap_service_costs 
             WHERE service_name = %s",
            $service_name
        ));
    }
}
```

## 🔄 POST-MVP ENHANCEMENTS

### 5. Enhanced Analytics System
```php
// includes/analytics/class-analytics-manager.php
class ASAP_Digest_Analytics_Manager {
    public function get_user_metrics($user_id, $timeframe = 'month') {
        // Implementation
    }
    
    public function get_service_costs() {
        // Implementation
    }
}
```

### 6. Service Provider Integration
```php
// includes/services/class-service-manager.php
class ASAP_Digest_Service_Manager {
    private $services = [
        'openai' => OpenAIService::class,
        'aws' => AWSService::class,
        'stripe' => StripeService::class
    ];
    
    public function track_service_usage($service, $units) {
        // Implementation
    }
}
```

### 7. Advanced Admin Controls
```php
// admin/class-advanced-controls.php
class ASAP_Digest_Advanced_Controls {
    public function register_controls() {
        // Implementation
    }
}
```

### 8. Performance Monitoring
```php
// includes/monitoring/class-performance-monitor.php
class ASAP_Digest_Performance_Monitor {
    public function track_api_response_time($endpoint, $response_time) {
        // Implementation
    }
}
```

### 9. Backup & Recovery System
```php
// includes/backup/class-backup-manager.php
class ASAP_Digest_Backup_Manager {
    public function create_backup() {
        // Implementation
    }
}
```

## 📋 Implementation Notes

1. **MVP Components Must Include:**
   - Core plugin restructuring
   - Database management system
   - Central Command dashboard
   - Basic usage tracking
   - Better Auth integration
   - Essential REST endpoints

2. **Database Migrations:**
   - All new tables should be versioned
   - Include rollback functionality
   - Preserve existing data during upgrades

3. **Security Considerations:**
   - Implement nonce checks on all forms
   - Sanitize all inputs
   - Validate user capabilities
   - Use prepared statements for queries

4. **Performance Optimization:**
   - Cache expensive queries
   - Use transients for temporary data
   - Implement batch processing for large operations

5. **Error Handling:**
   - Implement comprehensive logging
   - Graceful fallbacks for all operations
   - User-friendly error messages

To make this plan more LLM-friendly and ensure swift, accurate implementation within the context of PE - CTXT and ASAP_DIGEST_ROADMAP.md, we need to add several key components:

1. **File-Level Implementation Details**
```php
// Example file structure with exact paths and content markers
/wp-content/plugins/asapdigest-core/includes/class-core.php
/**
 * @file-marker ASAP_Digest_Core
 * @description Core plugin initialization and management
 * @location /wp-content/plugins/asapdigest-core/includes/class-core.php
 */

// Implementation markers for LLM
/* @section-start core-initialization */
class ASAP_Digest_Core {
    // Implementation here
}
/* @section-end core-initialization */
```

2. **Database Schema Reference Points**
```sql
-- @schema-marker asap_usage_metrics
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}asap_usage_metrics` (
    -- Schema implementation here
);

-- @schema-marker asap_service_costs
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}asap_service_costs` (
    -- Schema implementation here
);
```

3. **Component Integration Markers**
```javascript
// @component-marker CentralCommand
// @location /app/src/routes/admin/central-command/+page.svelte
// @dependencies ['class-central-command.php', 'dashboard.php']
```

4. **Function Implementation Templates**
```php
/**
 * @function-template track_service_usage
 * @location /wp-content/plugins/asapdigest-core/includes/services/class-service-manager.php
 * @dependencies ['class-database.php', 'class-better-auth.php']
 */
public function track_service_usage($service, $units) {
    // Implementation template
}
```

5. **Cross-Reference System**
```yaml
# @cross-reference-map
components:
  CentralCommand:
    php_files:
      - wp-content/plugins/asapdigest-core/includes/class-central-command.php
    svelte_files:
      - app/src/routes/admin/central-command/+page.svelte
    database_tables:
      - asap_usage_metrics
      - asap_service_costs
```

6. **State Management Markers**
```javascript
// @state-marker usage_tracking
const usageStore = {
    // State implementation template
};
```

7. **API Integration Points**
```php
/**
 * @api-endpoint /asap/v1/usage-metrics
 * @location wp-content/plugins/asapdigest-core/includes/api/class-usage-metrics.php
 * @auth-required true
 */
```

8. **Error Handling Templates**
```php
/**
 * @error-handler service_tracking
 * @location wp-content/plugins/asapdigest-core/includes/error-handlers/class-service-tracking.php
 */
```

9. **Implementation Context**
```yaml
# @implementation-context
environment:
  local:
    wp_root: /Volumes/Macintosh HD/Users/vsmith/Local Sites/asap-digest/app/public
    svelte_root: /Volumes/Macintosh HD/Users/vsmith/Local Sites/asap-digest/app/public/app
  database:
    host: localhost
    port: 10018
    name: local
```

10. **Dependency Chain**
```yaml
# @dependency-chain
class-core:
  requires:
    - class-database
    - class-better-auth
  required_by:
    - class-central-command
    - class-service-manager
```

11. **Implementation Order**
```yaml
# @implementation-order
1:
  - class-core
  - class-database
2:
  - class-better-auth
  - class-service-manager
3:
  - class-central-command
  - api-endpoints
```

12. **Testing Points**
```yaml
# @test-points
class-service-manager:
  unit_tests:
    - test_track_usage
    - test_cost_calculation
  integration_tests:
    - test_database_integration
    - test_auth_integration
```

To use this as a complete roadmap, an LLM would need:

1. **File Creation Commands**
```bash
# @file-creation-template
mkdir -p wp-content/plugins/asapdigest-core/includes
touch wp-content/plugins/asapdigest-core/includes/class-core.php
```

2. **Implementation Verification**
```php
// @verification-point core-initialization
assert(class_exists('ASAP_Digest_Core'));
assert(method_exists('ASAP_Digest_Core', 'get_instance'));
```

3. **Rollback Points**
```sql
-- @rollback-point schema-creation
DROP TABLE IF EXISTS `{$wpdb->prefix}asap_usage_metrics`;
```

4. **Integration Checkpoints**
```yaml
# @integration-checkpoint central-command
required_files:
  - class-central-command.php
  - dashboard.php
database_tables:
  - asap_usage_metrics
  - asap_service_costs
```

Would you like me to provide more specific details for any of these components or add additional context markers for the LLM?
