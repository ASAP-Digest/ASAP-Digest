<?php
/**
 * ASAP Digest Core Crawler Scheduler Class
 * 
 * Handles scheduling and execution of content crawling tasks via WordPress Cron.
 * 
 * @package ASAPDigest_Core
 * @created 04.17.25 | 11:15 AM PDT
 * @file-marker ASAP_Digest_Core_Crawler_Scheduler
 * @location /wp-content/plugins/asapdigest-core/includes/crawler/class-scheduler.php
 */

namespace ASAPDigest\Crawler;

/**
 * Class Scheduler
 * 
 * Manages the scheduling of crawler tasks via WP Cron with different
 * frequencies for different content source types.
 * 
 * @since 1.0.0
 */
class Scheduler {
    /**
     * The callable that will execute the crawler
     * 
     * @var callable
     */
    private $crawler_runner_callable;
    
    /**
     * The scheduled events
     * 
     * @var array
     */
    private $events = [
        'asap_crawler_hourly_event' => 'hourly',
        'asap_crawler_daily_event' => 'daily',
        'asap_crawler_twicedaily_event' => 'twicedaily',
        'asap_crawler_weekly_event' => 'weekly',
    ];
    
    /**
     * Mapping of source types to event frequencies
     * 
     * @var array
     */
    private $source_type_frequencies = [
        'rss' => 'hourly', // RSS feeds typically updated frequently
        'api' => 'hourly', // API endpoints may have fresh data regularly
        'scraper' => 'daily', // Web scraping less frequently to avoid rate limits
        'default' => 'daily' // Default frequency for any unspecified type
    ];
    
    /**
     * Constructor
     * 
     * @param callable $crawler_runner The function that runs the crawler
     */
    public function __construct(callable $crawler_runner) {
        $this->crawler_runner_callable = $crawler_runner;
        
        // Register our custom cron schedules
        add_filter('cron_schedules', [$this, 'add_custom_cron_intervals']);
        
        // Register the initialization hook (for WordPress actions)
        add_action('init', [$this, 'register_cron_events']);
        
        // Register event handlers
        foreach (array_keys($this->events) as $event) {
            add_action($event, [$this, 'handle_cron_event']);
        }
    }
    
    /**
     * Add custom cron intervals if needed
     * 
     * @param array $schedules The existing cron schedules
     * @return array Modified cron schedules
     */
    public function add_custom_cron_intervals($schedules) {
        // Add a weekly schedule if not already defined
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 604800, // 60 * 60 * 24 * 7
                'display' => __('Once Weekly', 'adc')
            ];
        }
        
        // You can add more custom intervals here as needed
        
        return $schedules;
    }
    
    /**
     * Register cron events
     * 
     * Ensures all our crawler events are scheduled in WP Cron
     */
    public function register_cron_events() {
        foreach ($this->events as $event => $frequency) {
            if (!wp_next_scheduled($event)) {
                // Schedule the event if it's not already scheduled
                // Add a small offset to each to avoid all running at exactly the same time
                $offset = array_search($event, array_keys($this->events)) * 300; // 5 minutes between each
                wp_schedule_event(time() + $offset, $frequency, $event);
                error_log("ASAP Digest Scheduler: Registered $event with $frequency frequency");
            }
        }
    }
    
    /**
     * Handle a cron event triggering
     * 
     * @param string $event The cron event being triggered
     */
    public function handle_cron_event($event = null) {
        // If event wasn't passed, get the current action
        if (null === $event) {
            $event = current_action();
        }
        
        // Map the event to the appropriate source types
        $frequency = isset($this->events[$event]) ? $this->events[$event] : 'daily';
        $source_types = $this->get_source_types_for_frequency($frequency);
        
        if (!empty($source_types)) {
            // Log the event
            error_log("ASAP Digest Scheduler: Running $event for source types: " . implode(', ', $source_types));
            
            // Run the crawler with the appropriate source types
            call_user_func($this->crawler_runner_callable, [
                'source_types' => $source_types,
                'frequency' => $frequency
            ]);
        }
    }
    
    /**
     * Get source types that should be crawled at a specific frequency
     * 
     * @param string $frequency The cron frequency
     * @return array Array of source type strings
     */
    private function get_source_types_for_frequency($frequency) {
        $types = [];
        
        foreach ($this->source_type_frequencies as $type => $type_frequency) {
            if ($type_frequency === $frequency) {
                $types[] = $type;
            }
        }
        
        return $types;
    }
    
    /**
     * Manually run the crawler for specific source types
     * 
     * @param array $source_types Array of source type strings
     * @param array|int $source_ids Optional specific source IDs to crawl
     * @return bool Success status
     */
    public function run_manual_crawl($source_types = null, $source_ids = null) {
        try {
            // If no source types specified, use all
            if (empty($source_types)) {
                $source_types = array_keys($this->source_type_frequencies);
            }
            
            // Log the manual run
            $ids_log = $source_ids ? ' (IDs: ' . (is_array($source_ids) ? implode(',', $source_ids) : $source_ids) . ')' : '';
            error_log("ASAP Digest Scheduler: Manual crawl for types: " . implode(', ', $source_types) . $ids_log);
            
            // Run the crawler with the specified parameters
            call_user_func($this->crawler_runner_callable, [
                'source_types' => $source_types,
                'source_ids' => $source_ids,
                'manual' => true
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("ASAP Digest Scheduler: Error in manual crawl: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Force-clear all scheduled events
     * 
     * @return void
     */
    public function clear_scheduled_events() {
        foreach (array_keys($this->events) as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
                error_log("ASAP Digest Scheduler: Cleared scheduled event $event");
            }
        }
    }
    
    /**
     * Update the frequency for a specific source type
     * 
     * @param string $source_type The source type
     * @param string $frequency The new frequency ('hourly', 'daily', 'twicedaily', 'weekly')
     * @return bool Success status
     */
    public function update_source_type_frequency($source_type, $frequency) {
        // Validate frequency
        if (!in_array($frequency, array_values($this->events))) {
            return false;
        }
        
        // Update the frequency
        $this->source_type_frequencies[$source_type] = $frequency;
        
        // Save to options
        update_option('asap_digest_source_frequencies', $this->source_type_frequencies);
        
        return true;
    }
    
    /**
     * Load source type frequencies from options
     * 
     * @return void
     */
    private function load_source_type_frequencies() {
        $saved_frequencies = get_option('asap_digest_source_frequencies', []);
        
        if (!empty($saved_frequencies)) {
            $this->source_type_frequencies = array_merge(
                $this->source_type_frequencies,
                $saved_frequencies
            );
        }
    }
} 