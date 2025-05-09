<?php
/**
 * @file-marker ASAP_Digest_ContentCrawler
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/class-content-crawler.php
 */

namespace AsapDigest\Crawler;

use AsapDigest\Crawler\Adapters\APIAdapter;
use AsapDigest\Crawler\Adapters\RSSAdapter;
use AsapDigest\Crawler\Adapters\ScraperAdapter;

/**
 * Main orchestrator for the content crawling process.
 * Manages source selection, adapter routing, and crawl scheduling.
 */
class ContentCrawler {
    /**
     * @var ContentSourceManager Source manager instance
     */
    private $source_manager;
    
    /**
     * @var ContentProcessor Content processor instance
     */
    private $content_processor;
    
    /**
     * @var array Registered source adapters
     */
    private $adapters = [];
    
    /**
     * @var bool Whether a crawl is currently running
     */
    private $is_running = false;
    
    /**
     * @var array Log of current crawl operations
     */
    private $run_log = [];
    
    /**
     * Constructor
     * 
     * @param ContentSourceManager $source_manager Source manager instance
     * @param ContentProcessor $content_processor Content processor instance
     */
    public function __construct($source_manager, $content_processor) {
        $this->source_manager = $source_manager;
        $this->content_processor = $content_processor;
        
        // Register default hooks
        add_action('asap_run_crawler', [$this, 'run_scheduled_crawl']);
        add_action('init', [$this, 'register_default_adapters']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }
    
    /**
     * Register default source adapters
     */
    public function register_default_adapters() {
        // Register RSS adapter if SimplePie is available
        if (class_exists('SimplePie')) {
            $this->register_adapter('rss', new RSSAdapter());
        }
        
        // Register API adapter if available
        if (class_exists('AsapDigest\Crawler\Adapters\APIAdapter')) {
            $this->register_adapter('api', new APIAdapter());
        }
        
        // Register scraper adapter if available
        if (class_exists('AsapDigest\Crawler\Adapters\ScraperAdapter')) {
            $this->register_adapter('scraper', new ScraperAdapter());
        }
        
        // Allow third-party adapters to be registered
        do_action('asap_register_crawler_adapters', $this);
    }
    
    /**
     * Register a source adapter
     * 
     * @param string $type Adapter type identifier
     * @param object $adapter Adapter instance
     * @return bool Success
     */
    public function register_adapter($type, $adapter) {
        if (!method_exists($adapter, 'fetch_content')) {
            return false;
        }
        
        $this->adapters[$type] = $adapter;
        return true;
    }
    
    /**
     * Register REST API endpoints for manual crawler control
     */
    public function register_api_endpoints() {
        register_rest_route('asap/v1', '/crawler/run', [
            'methods' => 'POST',
            'callback' => [$this, 'api_run_crawler'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
        
        register_rest_route('asap/v1', '/crawler/run/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'api_run_single_source'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
        
        register_rest_route('asap/v1', '/crawler/status', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_status'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * API endpoint to run crawler for all due sources
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_run_crawler($request) {
        $result = $this->run_crawl();
        return rest_ensure_response($result);
    }
    
    /**
     * API endpoint to run crawler for a single source
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_run_single_source($request) {
        $source_id = $request['id'];
        $source = $this->source_manager->get_source($source_id);
        
        if (!$source) {
            return new \WP_Error('source_not_found', 'Source not found', ['status' => 404]);
        }
        
        $result = $this->crawl_source($source);
        return rest_ensure_response($result);
    }
    
    /**
     * API endpoint to get crawler status
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function api_get_status($request) {
        return rest_ensure_response([
            'is_running' => $this->is_running,
            'run_log' => $this->run_log,
            'last_run' => get_option('asap_crawler_last_run', ''),
            'next_run' => wp_next_scheduled('asap_run_crawler'),
            'registered_adapters' => array_keys($this->adapters)
        ]);
    }
    
    /**
     * Run scheduled crawl (triggered by cron)
     */
    public function run_scheduled_crawl() {
        $this->run_crawl();
        
        // Schedule the next run with dynamic adjustment
        $this->schedule_next_run();
    }
    
    /**
     * Schedule the next crawler run with dynamic timing
     */
    private function schedule_next_run() {
        // Get the base recurrence (hourly, twicedaily, daily)
        $base_recurrence = get_option('asap_crawler_recurrence', 'hourly');
        
        // Calculate when the next run should be
        $next_run_timestamp = $this->calculate_next_run_time($base_recurrence);
        
        // Clear existing schedule
        $timestamp = wp_next_scheduled('asap_run_crawler');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'asap_run_crawler');
        }
        
        // Schedule new event with calculated timestamp
        wp_schedule_single_event($next_run_timestamp, 'asap_run_crawler');
        
        // Update next run info
        update_option('asap_crawler_next_run', $next_run_timestamp);
        
        $this->log("Next crawler run scheduled for " . date('Y-m-d H:i:s', $next_run_timestamp));
    }
    
    /**
     * Calculate the next run time based on source performance
     * 
     * @param string $base_recurrence Base recurrence interval
     * @return int Timestamp for next run
     */
    private function calculate_next_run_time($base_recurrence) {
        // Default intervals in seconds
        $intervals = [
            'hourly' => HOUR_IN_SECONDS,
            'twicedaily' => 12 * HOUR_IN_SECONDS,
            'daily' => DAY_IN_SECONDS
        ];
        
        $base_interval = $intervals[$base_recurrence] ?? HOUR_IN_SECONDS;
        
        // Get source performance metrics
        $source_metrics = $this->get_source_performance_metrics();
        
        // Calculate adjustment factor based on metrics
        $adjustment_factor = $this->calculate_adjustment_factor($source_metrics);
        
        // Apply the adjustment factor to the base interval
        $adjusted_interval = max(
            HOUR_IN_SECONDS / 2,  // Minimum: 30 minutes
            min(
                DAY_IN_SECONDS * 2,  // Maximum: 2 days
                $base_interval * $adjustment_factor
            )
        );
        
        // Calculate next run time
        return time() + $adjusted_interval;
    }
    
    /**
     * Get performance metrics for sources
     * 
     * @return array Performance metrics
     */
    private function get_source_performance_metrics() {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_source_metrics';
        
        // Get metrics from the last 7 days
        $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $metrics = $wpdb->get_results($wpdb->prepare(
            "SELECT source_id, AVG(items_found) as avg_items, AVG(errors) as avg_errors, 
            COUNT(*) as crawl_count
            FROM {$table} 
            WHERE created_at >= %s
            GROUP BY source_id",
            $start_date
        ));
        
        return $metrics;
    }
    
    /**
     * Calculate adjustment factor based on source metrics
     * 
     * @param array $metrics Source performance metrics
     * @return float Adjustment factor (0.5 - 2.0)
     */
    private function calculate_adjustment_factor($metrics) {
        if (empty($metrics)) {
            return 1.0; // No adjustment for no metrics
        }
        
        // Initialize factors
        $total_items = 0;
        $total_errors = 0;
        $total_crawls = 0;
        
        // Aggregate metrics
        foreach ($metrics as $metric) {
            $total_items += $metric->avg_items * $metric->crawl_count;
            $total_errors += $metric->avg_errors * $metric->crawl_count;
            $total_crawls += $metric->crawl_count;
        }
        
        // Calculate averages
        $avg_items = $total_crawls > 0 ? $total_items / $total_crawls : 0;
        $avg_errors = $total_crawls > 0 ? $total_errors / $total_crawls : 0;
        
        // Error rate as a percentage of items
        $error_rate = $avg_items > 0 ? $avg_errors / $avg_items : 0;
        
        // Calculate adjustment factor:
        // - High error rate -> run less frequently (increase interval)
        // - Low error rate & high item count -> run more frequently (decrease interval)
        // - Default -> no change
        
        if ($error_rate > 0.2) {
            // More than 20% errors, slow down (increase interval)
            return 1.5;
        } elseif ($error_rate < 0.05 && $avg_items > 10) {
            // Less than 5% errors and good item count, speed up (decrease interval)
            return 0.75;
        } else {
            // Default: no adjustment
            return 1.0;
        }
    }
    
    /**
     * Run crawler for all due sources with retry mechanism
     * 
     * @param int $retry_attempts Number of retry attempts for failed sources
     * @return array Results
     */
    public function run_crawl($retry_attempts = 1) {
        if ($this->is_running) {
            return [
                'success' => false,
                'message' => 'Crawler is already running'
            ];
        }
        
        $this->is_running = true;
        $this->run_log = [];
        
        // Get sources that are due for crawling
        $sources = $this->source_manager->get_due_sources();
        
        $results = [
            'success' => true,
            'sources_processed' => 0,
            'items_found' => 0,
            'items_processed' => 0,
            'errors' => 0,
            'sources' => []
        ];
        
        $failed_sources = [];
        
        foreach ($sources as $source) {
            try {
                $source_result = $this->crawl_source($source);
                $results['sources'][] = $source_result;
                $results['sources_processed']++;
                $results['items_found'] += $source_result['items_found'];
                $results['items_processed'] += $source_result['items_processed'];
                $results['errors'] += count($source_result['errors']);
                
                if (count($source_result['errors']) > 0) {
                    $failed_sources[] = $source;
                }
            } catch (\Exception $e) {
                $this->log("Fatal error processing source #{$source->id}: " . $e->getMessage(), 'error');
                $results['errors']++;
                $failed_sources[] = $source;
            }
        }
        
        // Retry failed sources if specified
        if ($retry_attempts > 0 && !empty($failed_sources)) {
            $this->log("Retrying " . count($failed_sources) . " failed sources...");
            
            foreach ($failed_sources as $source) {
                try {
                    $this->log("Retry attempt for source #{$source->id}");
                    $source_result = $this->crawl_source($source);
                    
                    // Update the existing result or add a new one
                    $found = false;
                    foreach ($results['sources'] as &$existing_result) {
                        if ($existing_result['source_id'] == $source->id) {
                            $existing_result = $source_result;
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $results['sources'][] = $source_result;
                        $results['sources_processed']++;
                    }
                    
                    // Update counts
                    $results['items_found'] += $source_result['items_found'];
                    $results['items_processed'] += $source_result['items_processed'];
                    $results['errors'] -= count($source_result['errors']) > 0 ? 0 : 1; // Reduce error count only if retry had no errors
                } catch (\Exception $e) {
                    $this->log("Retry failed for source #{$source->id}: " . $e->getMessage(), 'error');
                }
            }
        }
        
        update_option('asap_crawler_last_run', current_time('mysql'));
        $this->is_running = false;
        
        // Store crawler metrics
        $this->store_crawler_metrics($results);
        
        return $results;
    }
    
    /**
     * Store crawler run metrics for analysis
     * 
     * @param array $results Crawler run results
     */
    private function store_crawler_metrics($results) {
        global $wpdb;
        $table = $wpdb->prefix . 'asap_crawler_metrics';
        
        // Store overall metrics
        $wpdb->insert(
            $table,
            [
                'run_date' => current_time('mysql'),
                'sources_processed' => $results['sources_processed'],
                'items_found' => $results['items_found'],
                'items_processed' => $results['items_processed'],
                'errors' => $results['errors'],
                'duration_seconds' => time() - strtotime(get_option('asap_crawler_last_run')),
                'created_at' => current_time('mysql')
            ]
        );
        
        // Store per-source metrics
        $source_metrics_table = $wpdb->prefix . 'asap_source_metrics';
        
        foreach ($results['sources'] as $source_result) {
            $wpdb->insert(
                $source_metrics_table,
                [
                    'source_id' => $source_result['source_id'],
                    'items_found' => $source_result['items_found'],
                    'items_processed' => $source_result['items_processed'],
                    'errors' => count($source_result['errors']),
                    'created_at' => current_time('mysql')
                ]
            );
        }
    }
    
    /**
     * Crawl a single source
     * 
     * @param object $source Source object
     * @return array Result
     */
    public function crawl_source($source) {
        $result = [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'source_type' => $source->type,
            'started_at' => current_time('mysql'),
            'items_found' => 0,
            'items_processed' => 0,
            'errors' => []
        ];
        
        try {
            // Log start
            $this->log("Starting crawl for source #{$source->id}: {$source->name} ({$source->type})");
            
            // Check if we have an adapter for this source type
            if (!isset($this->adapters[$source->type])) {
                throw new \Exception("No adapter registered for source type: {$source->type}");
            }
            
            $adapter = $this->adapters[$source->type];
            
            // Fetch content using the appropriate adapter
            $items = $adapter->fetch_content($source);
            $result['items_found'] = count($items);
            
            $this->log("Found {$result['items_found']} items from source #{$source->id}");
            
            // Process each item
            foreach ($items as $item) {
                try {
                    // Add source information to item
                    $item['source_id'] = $source->id;
                    $item['source_name'] = $source->name;
                    $item['source_url'] = $source->url;
                    
                    // Process the item
                    $processed = $this->content_processor->process_item($item);
                    
                    if ($processed) {
                        $result['items_processed']++;
                    }
                } catch (\Exception $e) {
                    $error = "Error processing item: " . $e->getMessage();
                    $this->log($error, 'error');
                    $result['errors'][] = $error;
                    
                    // Log to error table
                    $this->log_error($source->id, 'item_processing', $e->getMessage(), $item);
                }
            }
            
            // Update source status with results
            $this->source_manager->update_source_status($source->id, true, [
                'items_found' => $result['items_found'],
                'new_items' => $result['items_processed']
            ]);
            
            $this->log("Completed crawl for source #{$source->id}. Processed {$result['items_processed']} of {$result['items_found']} items.");
            
        } catch (\Exception $e) {
            $error = "Error crawling source #{$source->id}: " . $e->getMessage();
            $this->log($error, 'error');
            $result['errors'][] = $error;
            
            // Log to error table
            $this->log_error($source->id, 'source_crawl', $e->getMessage());
            
            // Update source status as failed
            $this->source_manager->update_source_status($source->id, false);
        }
        
        $result['completed_at'] = current_time('mysql');
        return $result;
    }
    
    /**
     * Log a crawler message
     * 
     * @param string $message Log message
     * @param string $type Log type (info, error)
     */
    private function log($message, $type = 'info') {
        $this->run_log[] = [
            'time' => current_time('mysql'),
            'type' => $type,
            'message' => $message
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ASAP Crawler: {$message}");
        }
    }
    
    /**
     * Log an error to the database
     * 
     * @param int $source_id Source ID
     * @param string $error_type Error type
     * @param string $message Error message
     * @param array $context Optional error context
     */
    private function log_error($source_id, $error_type, $message, $context = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'asap_crawler_errors';
        
        $wpdb->insert(
            $table,
            [
                'source_id' => $source_id,
                'error_type' => $error_type,
                'message' => $message,
                'context' => is_array($context) ? json_encode($context) : $context,
                'severity' => 'error',
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    /**
     * Set up the crawler schedule
     * 
     * @param string $recurrence Recurrence (hourly, twicedaily, daily)
     * @return bool Success
     */
    public function setup_schedule($recurrence = 'hourly') {
        // Save the base recurrence
        update_option('asap_crawler_recurrence', $recurrence);
        
        // Clear existing schedule
        $timestamp = wp_next_scheduled('asap_run_crawler');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'asap_run_crawler');
        }
        
        // Calculate when the next run should be based on recurrence
        $intervals = [
            'hourly' => HOUR_IN_SECONDS,
            'twicedaily' => 12 * HOUR_IN_SECONDS,
            'daily' => DAY_IN_SECONDS
        ];
        
        $interval = $intervals[$recurrence] ?? HOUR_IN_SECONDS;
        $next_run = time() + $interval;
        
        // Schedule new event
        $scheduled = wp_schedule_single_event($next_run, 'asap_run_crawler');
        
        if ($scheduled) {
            update_option('asap_crawler_next_run', $next_run);
            $this->log("Crawler scheduled to run at " . date('Y-m-d H:i:s', $next_run));
        }
        
        return $scheduled;
    }
} 