<?php
/**
 * Content Source Management Admin View
 *
 * @package ASAP_Digest
 * @subpackage Admin
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load content source manager class if not already loaded
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/crawler/class-content-source-manager.php';

// Initialize source manager
$source_manager = new AsapDigest\Crawler\ContentSourceManager();

// Get current sources
$sources = $source_manager->load_sources();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check nonce
    check_admin_referer('asap_digest_source_management');
    
    // Handle source actions (add, edit, delete)
    if (isset($_POST['action']) && $_POST['action'] === 'add_source') {
        // Validate and sanitize input
        $name = sanitize_text_field($_POST['name'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $fetch_interval = intval($_POST['fetch_interval'] ?? 3600);
        
        // Basic config
        $config = [
            'title_selector' => sanitize_text_field($_POST['title_selector'] ?? ''),
            'content_selector' => sanitize_text_field($_POST['content_selector'] ?? ''),
            'date_selector' => sanitize_text_field($_POST['date_selector'] ?? ''),
            'author_selector' => sanitize_text_field($_POST['author_selector'] ?? ''),
            'image_selector' => sanitize_text_field($_POST['image_selector'] ?? ''),
        ];
        
        // Content types
        $content_types = $_POST['content_types'] ?? [];
        $content_types = array_map('sanitize_text_field', $content_types);
        
        // Additional config fields based on source type
        if ($type === 'api') {
            $config['auth_type'] = sanitize_text_field($_POST['auth_type'] ?? 'none');
            $config['api_key'] = sanitize_text_field($_POST['api_key'] ?? '');
            $config['auth_header'] = sanitize_text_field($_POST['auth_header'] ?? '');
            $config['pagination'] = isset($_POST['pagination']) ? 1 : 0;
            $config['items_per_page'] = intval($_POST['items_per_page'] ?? 10);
        }
        
        // Add new source
        $source_data = [
            'name' => $name,
            'type' => $type,
            'url' => $url,
            'config' => $config,
            'content_types' => $content_types,
            'active' => $active,
            'fetch_interval' => $fetch_interval,
        ];
        
        $result = $source_manager->add_source($source_data);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>Content source added successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding content source.</p></div>';
        }
        
        // Refresh sources list
        $sources = $source_manager->load_sources();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_source' && isset($_POST['source_id'])) {
        $source_id = intval($_POST['source_id']);
        
        // Similar validation and sanitization as add_source
        $name = sanitize_text_field($_POST['name'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $fetch_interval = intval($_POST['fetch_interval'] ?? 3600);
        
        // Basic config
        $config = [
            'title_selector' => sanitize_text_field($_POST['title_selector'] ?? ''),
            'content_selector' => sanitize_text_field($_POST['content_selector'] ?? ''),
            'date_selector' => sanitize_text_field($_POST['date_selector'] ?? ''),
            'author_selector' => sanitize_text_field($_POST['author_selector'] ?? ''),
            'image_selector' => sanitize_text_field($_POST['image_selector'] ?? ''),
        ];
        
        // Content types
        $content_types = $_POST['content_types'] ?? [];
        $content_types = array_map('sanitize_text_field', $content_types);
        
        // Additional config fields based on source type
        if ($type === 'api') {
            $config['auth_type'] = sanitize_text_field($_POST['auth_type'] ?? 'none');
            $config['api_key'] = sanitize_text_field($_POST['api_key'] ?? '');
            $config['auth_header'] = sanitize_text_field($_POST['auth_header'] ?? '');
            $config['pagination'] = isset($_POST['pagination']) ? 1 : 0;
            $config['items_per_page'] = intval($_POST['items_per_page'] ?? 10);
        }
        
        // Update source
        $source_data = [
            'name' => $name,
            'type' => $type,
            'url' => $url,
            'config' => $config,
            'content_types' => $content_types,
            'active' => $active,
            'fetch_interval' => $fetch_interval,
        ];
        
        $result = $source_manager->update_source($source_id, $source_data);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>Content source updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating content source.</p></div>';
        }
        
        // Refresh sources list
        $sources = $source_manager->load_sources();
    }
}

// Handle run source action
if (isset($_GET['action']) && $_GET['action'] === 'run' && isset($_GET['id'])) {
    $source_id = intval($_GET['id']);
    $source = $source_manager->get_source($source_id);
    
    if ($source) {
        // Load content crawler
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/crawler/class-content-crawler.php';
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/class-content-processor.php';
        
        $processor = new ASAP_Digest_Content_Processor();
        $crawler = new AsapDigest\Crawler\ContentCrawler($source_manager, $processor);
        
        // Run crawler for this source
        $result = $crawler->crawl_source($source);
        
        if ($result && $result['items_processed'] > 0) {
            echo '<div class="notice notice-success"><p>Content source crawled successfully! Processed ' . $result['items_processed'] . ' items.</p></div>';
        } elseif ($result) {
            echo '<div class="notice notice-warning"><p>Content source crawled with no new items found.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error crawling content source.</p></div>';
        }
    }
}

// Handle delete source action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
    $source_id = intval($_GET['id']);
    
    // Verify nonce
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_source_' . $source_id)) {
        $result = $source_manager->delete_source($source_id);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>Content source deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error deleting content source.</p></div>';
        }
        
        // Refresh sources list
        $sources = $source_manager->load_sources();
    } else {
        echo '<div class="notice notice-error"><p>Invalid security token.</p></div>';
    }
}

// Handle reindex action (for content processing)
if (isset($_GET['page']) && $_GET['page'] === 'asap-content-sources' && isset($_GET['action']) && $_GET['action'] === 'reindex') {
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/class-content-processor.php';
    
    $processor = new ASAP_Digest_Content_Processor();
    $result = $processor->reindex_content(50); // Process 50 items
    
    if ($result && $result['processed'] > 0) {
        echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
    } else {
        echo '<div class="notice notice-info"><p>No content needed reindexing.</p></div>';
    }
}

// Output the admin page
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Content Sources</h1>
    <a href="#" class="page-title-action" onclick="document.getElementById('add-source-form').style.display='block'; return false;">Add New Source</a>
    
    <div class="notice notice-info">
        <p>Manage your content sources for the ASAP Digest Content Ingestion System (CIS). Add, edit, or delete sources and run the crawler manually to fetch content.</p>
    </div>
    
    <!-- Tab navigation -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="?page=asap-content-sources" class="nav-tab <?php echo !isset($_GET['tab']) ? 'nav-tab-active' : ''; ?>">Sources</a>
        <a href="?page=asap-content-sources&tab=metrics" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'metrics' ? 'nav-tab-active' : ''; ?>">Metrics</a>
        <a href="?page=asap-content-sources&tab=processing" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'processing' ? 'nav-tab-active' : ''; ?>">Processing Tools</a>
    </nav>
    
    <div class="tab-content">
        <?php
        // Display different content based on tab
        $current_tab = $_GET['tab'] ?? 'sources';
        
        if ($current_tab === 'metrics') {
            // Metrics tab
            ?>
            <h2>Content Source Metrics</h2>
            <div class="metrics-dashboard">
                <div class="metrics-card">
                    <h3>Total Sources</h3>
                    <div class="metric-value"><?php echo count($sources); ?></div>
                </div>
                
                <div class="metrics-card">
                    <h3>Active Sources</h3>
                    <div class="metric-value">
                        <?php 
                        $active_count = 0;
                        foreach ($sources as $source) {
                            if ($source->active) {
                                $active_count++;
                            }
                        }
                        echo $active_count; 
                        ?>
                    </div>
                </div>
                
                <div class="metrics-card">
                    <h3>Content Items</h3>
                    <div class="metric-value">
                        <?php 
                        global $wpdb;
                        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}asap_ingested_content");
                        echo $total_items ?: 0; 
                        ?>
                    </div>
                </div>
            </div>
            
            <h3>Source Performance</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Last Fetched</th>
                        <th>Fetch Count</th>
                        <th>Success Rate</th>
                        <th>Avg. Items per Fetch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source): ?>
                    <tr>
                        <td><?php echo esc_html($source->name); ?></td>
                        <td><?php echo esc_html($source->type); ?></td>
                        <td><?php echo $source->last_fetch ? date('Y-m-d H:i:s', $source->last_fetch) : 'Never'; ?></td>
                        <td><?php echo intval($source->fetch_count); ?></td>
                        <td>
                            <?php
                            // Calculate success rate
                            global $wpdb;
                            $total_errors = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}asap_crawler_errors WHERE source_id = %d",
                                $source->id
                            ));
                            
                            $success_rate = $source->fetch_count > 0 ? 
                                round((($source->fetch_count - $total_errors) / $source->fetch_count) * 100) : 0;
                            
                            echo "{$success_rate}%";
                            ?>
                        </td>
                        <td>
                            <?php
                            // Calculate average items per fetch
                            global $wpdb;
                            $total_items = $wpdb->get_var($wpdb->prepare(
                                "SELECT SUM(items_stored) FROM {$wpdb->prefix}asap_source_metrics WHERE source_id = %d",
                                $source->id
                            ));
                            
                            $avg_items = $source->fetch_count > 0 ? 
                                round($total_items / $source->fetch_count, 2) : 0;
                            
                            echo $avg_items;
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } elseif ($current_tab === 'processing') {
            // Processing tools tab
            ?>
            <h2>Content Processing Tools</h2>
            
            <div class="card" style="max-width: 600px;">
                <h3>Reindex Content</h3>
                <p>Reindex existing content to ensure all items have fingerprints and quality scores. This is useful after upgrading to a new version with enhanced fingerprinting or quality scoring.</p>
                <a href="?page=asap-content-sources&tab=processing&action=reindex" class="button button-primary">Run Reindexing (Batch)</a>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>Duplicate Detection Report</h3>
                <p>Generate a report of duplicate content in the system. This helps identify content that may need to be merged or removed.</p>
                
                <form method="get" action="">
                    <input type="hidden" name="page" value="asap-content-sources">
                    <input type="hidden" name="tab" value="processing">
                    <input type="hidden" name="action" value="duplicates">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="min_duplicates">Minimum Duplicates</label></th>
                            <td>
                                <input type="number" name="min_duplicates" id="min_duplicates" value="2" min="2" max="10">
                                <p class="description">Minimum number of duplicate instances to include in report</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="limit">Result Limit</label></th>
                            <td>
                                <input type="number" name="limit" id="limit" value="50" min="10" max="100">
                                <p class="description">Maximum number of duplicate sets to return</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p><input type="submit" class="button button-primary" value="Generate Report"></p>
                </form>
                
                <?php
                // Display duplicate report if requested
                if (isset($_GET['action']) && $_GET['action'] === 'duplicates') {
                    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/content-processing/class-content-processor.php';
                    
                    $processor = new ASAP_Digest_Content_Processor();
                    
                    $args = [
                        'min_duplicates' => intval($_GET['min_duplicates'] ?? 2),
                        'limit' => intval($_GET['limit'] ?? 50),
                    ];
                    
                    $report = $processor->generate_duplicate_report($args);
                    
                    if (empty($report)) {
                        echo '<div class="notice notice-info"><p>No duplicate content found.</p></div>';
                    } else {
                        echo '<h3>Duplicate Content Report</h3>';
                        echo '<p>Found ' . count($report) . ' sets of duplicate content.</p>';
                        
                        foreach ($report as $index => $duplicate_set) {
                            echo '<div class="duplicate-set">';
                            echo '<h4>Duplicate Set #' . ($index + 1) . ' (' . $duplicate_set['count'] . ' instances)</h4>';
                            echo '<p>First seen: ' . $duplicate_set['first_seen'] . '</p>';
                            echo '<p>Latest seen: ' . $duplicate_set['latest_seen'] . '</p>';
                            
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr><th>ID</th><th>Title</th><th>Source URL</th><th>Date</th><th>Quality</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($duplicate_set['instances'] as $instance) {
                                echo '<tr>';
                                echo '<td>' . esc_html($instance['id']) . '</td>';
                                echo '<td>' . esc_html($instance['title']) . '</td>';
                                echo '<td><a href="' . esc_url($instance['source_url']) . '" target="_blank">' . esc_url($instance['source_url']) . '</a></td>';
                                echo '<td>' . esc_html($instance['publish_date']) . '</td>';
                                echo '<td>' . esc_html($instance['quality_score']) . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <?php
        } else {
            // Default sources tab
            ?>
            <!-- Sources table -->
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Last Fetch</th>
                        <th>Interval</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sources)): ?>
                        <tr>
                            <td colspan="7">No content sources found. Add your first source to get started!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sources as $source): ?>
                            <tr>
                                <td><?php echo esc_html($source->name); ?></td>
                                <td><?php echo esc_html($source->type); ?></td>
                                <td><?php echo esc_url($source->url); ?></td>
                                <td><?php echo $source->active ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>'; ?></td>
                                <td><?php echo $source->last_fetch ? date('Y-m-d H:i:s', $source->last_fetch) : 'Never'; ?></td>
                                <td><?php echo human_time_diff(0, $source->fetch_interval); ?></td>
                                <td>
                                    <a href="?page=asap-content-sources&action=edit&id=<?php echo $source->id; ?>" class="button button-small">Edit</a>
                                    <a href="?page=asap-content-sources&action=run&id=<?php echo $source->id; ?>" class="button button-small button-primary">Run</a>
                                    <a href="?page=asap-content-sources&action=delete&id=<?php echo $source->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_source_' . $source->id); ?>" class="button button-small button-danger" onclick="return confirm('Are you sure you want to delete this source?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
        ?>
    </div>
    
    <!-- Add Source Form (hidden by default) -->
    <div id="add-source-form" style="display: none; margin-top: 20px; padding: 20px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,0.04); border: 1px solid #ccd0d4;">
        <h2>Add New Content Source</h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('asap_digest_source_management'); ?>
            <input type="hidden" name="action" value="add_source">
            
            <table class="form-table">
                <tr>
                    <th><label for="name">Source Name</label></th>
                    <td>
                        <input type="text" name="name" id="name" class="regular-text" required>
                        <p class="description">A descriptive name for this content source</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="type">Source Type</label></th>
                    <td>
                        <select name="type" id="type" required>
                            <option value="">Select Type</option>
                            <option value="rss">RSS Feed</option>
                            <option value="api">API Endpoint</option>
                            <option value="scraper">Web Scraper</option>
                        </select>
                        <p class="description">The type of content source determines how content is fetched</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="url">URL</label></th>
                    <td>
                        <input type="url" name="url" id="url" class="regular-text" required>
                        <p class="description">The URL of the content source (RSS feed URL, API endpoint, or website URL to scrape)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="active">Active</label></th>
                    <td>
                        <input type="checkbox" name="active" id="active" value="1" checked>
                        <label for="active">Include this source in scheduled crawls</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="fetch_interval">Fetch Interval</label></th>
                    <td>
                        <select name="fetch_interval" id="fetch_interval">
                            <option value="1800">30 Minutes</option>
                            <option value="3600" selected>1 Hour</option>
                            <option value="7200">2 Hours</option>
                            <option value="14400">4 Hours</option>
                            <option value="21600">6 Hours</option>
                            <option value="43200">12 Hours</option>
                            <option value="86400">24 Hours</option>
                        </select>
                        <p class="description">How often to check for new content</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Content Types</label></th>
                    <td>
                        <label><input type="checkbox" name="content_types[]" value="article" checked> Articles</label><br>
                        <label><input type="checkbox" name="content_types[]" value="news"> News</label><br>
                        <label><input type="checkbox" name="content_types[]" value="blog"> Blog Posts</label><br>
                        <label><input type="checkbox" name="content_types[]" value="podcast"> Podcasts</label><br>
                        <label><input type="checkbox" name="content_types[]" value="video"> Videos</label><br>
                        <p class="description">Types of content to fetch from this source</p>
                    </td>
                </tr>
                
                <!-- Selector fields for scraper sources -->
                <tr class="scraper-field" style="display: none;">
                    <th><label for="title_selector">Title Selector</label></th>
                    <td>
                        <input type="text" name="title_selector" id="title_selector" class="regular-text">
                        <p class="description">CSS selector for title (e.g., "h1.entry-title")</p>
                    </td>
                </tr>
                <tr class="scraper-field" style="display: none;">
                    <th><label for="content_selector">Content Selector</label></th>
                    <td>
                        <input type="text" name="content_selector" id="content_selector" class="regular-text">
                        <p class="description">CSS selector for content (e.g., "div.entry-content")</p>
                    </td>
                </tr>
                <tr class="scraper-field" style="display: none;">
                    <th><label for="date_selector">Date Selector</label></th>
                    <td>
                        <input type="text" name="date_selector" id="date_selector" class="regular-text">
                        <p class="description">CSS selector for publication date (e.g., "time.entry-date")</p>
                    </td>
                </tr>
                <tr class="scraper-field" style="display: none;">
                    <th><label for="author_selector">Author Selector</label></th>
                    <td>
                        <input type="text" name="author_selector" id="author_selector" class="regular-text">
                        <p class="description">CSS selector for author (e.g., "span.author")</p>
                    </td>
                </tr>
                <tr class="scraper-field" style="display: none;">
                    <th><label for="image_selector">Image Selector</label></th>
                    <td>
                        <input type="text" name="image_selector" id="image_selector" class="regular-text">
                        <p class="description">CSS selector for featured image (e.g., "img.featured")</p>
                    </td>
                </tr>
                
                <!-- API config fields -->
                <tr class="api-field" style="display: none;">
                    <th><label for="auth_type">Authentication Type</label></th>
                    <td>
                        <select name="auth_type" id="auth_type">
                            <option value="none">None</option>
                            <option value="api_key">API Key</option>
                            <option value="bearer">Bearer Token</option>
                        </select>
                    </td>
                </tr>
                <tr class="api-field" style="display: none;">
                    <th><label for="api_key">API Key / Token</label></th>
                    <td>
                        <input type="text" name="api_key" id="api_key" class="regular-text">
                    </td>
                </tr>
                <tr class="api-field" style="display: none;">
                    <th><label for="auth_header">Auth Header Name</label></th>
                    <td>
                        <input type="text" name="auth_header" id="auth_header" class="regular-text" placeholder="X-API-Key">
                        <p class="description">For API Key auth, specify the header name (default: X-API-Key)</p>
                    </td>
                </tr>
                <tr class="api-field" style="display: none;">
                    <th><label for="pagination">Supports Pagination</label></th>
                    <td>
                        <input type="checkbox" name="pagination" id="pagination" value="1">
                    </td>
                </tr>
                <tr class="api-field" style="display: none;">
                    <th><label for="items_per_page">Items Per Page</label></th>
                    <td>
                        <input type="number" name="items_per_page" id="items_per_page" min="1" max="100" value="10">
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Source">
                <button type="button" class="button" onclick="document.getElementById('add-source-form').style.display='none';">Cancel</button>
            </p>
        </form>
    </div>
    
    <!-- Edit Source Form (if editing) -->
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
        <?php
        $source_id = intval($_GET['id']);
        $source = $source_manager->get_source($source_id);
        
        if ($source):
            // Parse serialized config and content types
            $config = maybe_unserialize($source->config);
            $content_types = maybe_unserialize($source->content_types);
            
            if (!is_array($config)) {
                $config = [];
            }
            
            if (!is_array($content_types)) {
                $content_types = [];
            }
        ?>
        <div id="edit-source-form" style="margin-top: 20px; padding: 20px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,0.04); border: 1px solid #ccd0d4;">
            <h2>Edit Content Source</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('asap_digest_source_management'); ?>
                <input type="hidden" name="action" value="edit_source">
                <input type="hidden" name="source_id" value="<?php echo $source_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Source Name</label></th>
                        <td>
                            <input type="text" name="name" id="name" class="regular-text" value="<?php echo esc_attr($source->name); ?>" required>
                            <p class="description">A descriptive name for this content source</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Source Type</label></th>
                        <td>
                            <select name="type" id="type" required>
                                <option value="rss" <?php selected($source->type, 'rss'); ?>>RSS Feed</option>
                                <option value="api" <?php selected($source->type, 'api'); ?>>API Endpoint</option>
                                <option value="scraper" <?php selected($source->type, 'scraper'); ?>>Web Scraper</option>
                            </select>
                            <p class="description">The type of content source determines how content is fetched</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url">URL</label></th>
                        <td>
                            <input type="url" name="url" id="url" class="regular-text" value="<?php echo esc_url($source->url); ?>" required>
                            <p class="description">The URL of the content source (RSS feed URL, API endpoint, or website URL to scrape)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="active">Active</label></th>
                        <td>
                            <input type="checkbox" name="active" id="active" value="1" <?php checked($source->active, 1); ?>>
                            <label for="active">Include this source in scheduled crawls</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fetch_interval">Fetch Interval</label></th>
                        <td>
                            <select name="fetch_interval" id="fetch_interval">
                                <option value="1800" <?php selected($source->fetch_interval, 1800); ?>>30 Minutes</option>
                                <option value="3600" <?php selected($source->fetch_interval, 3600); ?>>1 Hour</option>
                                <option value="7200" <?php selected($source->fetch_interval, 7200); ?>>2 Hours</option>
                                <option value="14400" <?php selected($source->fetch_interval, 14400); ?>>4 Hours</option>
                                <option value="21600" <?php selected($source->fetch_interval, 21600); ?>>6 Hours</option>
                                <option value="43200" <?php selected($source->fetch_interval, 43200); ?>>12 Hours</option>
                                <option value="86400" <?php selected($source->fetch_interval, 86400); ?>>24 Hours</option>
                            </select>
                            <p class="description">How often to check for new content</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Content Types</label></th>
                        <td>
                            <label><input type="checkbox" name="content_types[]" value="article" <?php checked(in_array('article', $content_types), true); ?>> Articles</label><br>
                            <label><input type="checkbox" name="content_types[]" value="news" <?php checked(in_array('news', $content_types), true); ?>> News</label><br>
                            <label><input type="checkbox" name="content_types[]" value="blog" <?php checked(in_array('blog', $content_types), true); ?>> Blog Posts</label><br>
                            <label><input type="checkbox" name="content_types[]" value="podcast" <?php checked(in_array('podcast', $content_types), true); ?>> Podcasts</label><br>
                            <label><input type="checkbox" name="content_types[]" value="video" <?php checked(in_array('video', $content_types), true); ?>> Videos</label><br>
                            <p class="description">Types of content to fetch from this source</p>
                        </td>
                    </tr>
                    
                    <!-- Selector fields for scraper sources -->
                    <tr class="scraper-field" style="<?php echo $source->type !== 'scraper' ? 'display: none;' : ''; ?>">
                        <th><label for="title_selector">Title Selector</label></th>
                        <td>
                            <input type="text" name="title_selector" id="title_selector" class="regular-text" value="<?php echo esc_attr($config['title_selector'] ?? ''); ?>">
                            <p class="description">CSS selector for title (e.g., "h1.entry-title")</p>
                        </td>
                    </tr>
                    <tr class="scraper-field" style="<?php echo $source->type !== 'scraper' ? 'display: none;' : ''; ?>">
                        <th><label for="content_selector">Content Selector</label></th>
                        <td>
                            <input type="text" name="content_selector" id="content_selector" class="regular-text" value="<?php echo esc_attr($config['content_selector'] ?? ''); ?>">
                            <p class="description">CSS selector for content (e.g., "div.entry-content")</p>
                        </td>
                    </tr>
                    <tr class="scraper-field" style="<?php echo $source->type !== 'scraper' ? 'display: none;' : ''; ?>">
                        <th><label for="date_selector">Date Selector</label></th>
                        <td>
                            <input type="text" name="date_selector" id="date_selector" class="regular-text" value="<?php echo esc_attr($config['date_selector'] ?? ''); ?>">
                            <p class="description">CSS selector for publication date (e.g., "time.entry-date")</p>
                        </td>
                    </tr>
                    <tr class="scraper-field" style="<?php echo $source->type !== 'scraper' ? 'display: none;' : ''; ?>">
                        <th><label for="author_selector">Author Selector</label></th>
                        <td>
                            <input type="text" name="author_selector" id="author_selector" class="regular-text" value="<?php echo esc_attr($config['author_selector'] ?? ''); ?>">
                            <p class="description">CSS selector for author (e.g., "span.author")</p>
                        </td>
                    </tr>
                    <tr class="scraper-field" style="<?php echo $source->type !== 'scraper' ? 'display: none;' : ''; ?>">
                        <th><label for="image_selector">Image Selector</label></th>
                        <td>
                            <input type="text" name="image_selector" id="image_selector" class="regular-text" value="<?php echo esc_attr($config['image_selector'] ?? ''); ?>">
                            <p class="description">CSS selector for featured image (e.g., "img.featured")</p>
                        </td>
                    </tr>
                    
                    <!-- API config fields -->
                    <tr class="api-field" style="<?php echo $source->type !== 'api' ? 'display: none;' : ''; ?>">
                        <th><label for="auth_type">Authentication Type</label></th>
                        <td>
                            <select name="auth_type" id="auth_type">
                                <option value="none" <?php selected(($config['auth_type'] ?? 'none'), 'none'); ?>>None</option>
                                <option value="api_key" <?php selected(($config['auth_type'] ?? 'none'), 'api_key'); ?>>API Key</option>
                                <option value="bearer" <?php selected(($config['auth_type'] ?? 'none'), 'bearer'); ?>>Bearer Token</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="api-field" style="<?php echo $source->type !== 'api' ? 'display: none;' : ''; ?>">
                        <th><label for="api_key">API Key / Token</label></th>
                        <td>
                            <input type="text" name="api_key" id="api_key" class="regular-text" value="<?php echo esc_attr($config['api_key'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr class="api-field" style="<?php echo $source->type !== 'api' ? 'display: none;' : ''; ?>">
                        <th><label for="auth_header">Auth Header Name</label></th>
                        <td>
                            <input type="text" name="auth_header" id="auth_header" class="regular-text" placeholder="X-API-Key" value="<?php echo esc_attr($config['auth_header'] ?? ''); ?>">
                            <p class="description">For API Key auth, specify the header name (default: X-API-Key)</p>
                        </td>
                    </tr>
                    <tr class="api-field" style="<?php echo $source->type !== 'api' ? 'display: none;' : ''; ?>">
                        <th><label for="pagination">Supports Pagination</label></th>
                        <td>
                            <input type="checkbox" name="pagination" id="pagination" value="1" <?php checked(($config['pagination'] ?? 0), 1); ?>>
                        </td>
                    </tr>
                    <tr class="api-field" style="<?php echo $source->type !== 'api' ? 'display: none;' : ''; ?>">
                        <th><label for="items_per_page">Items Per Page</label></th>
                        <td>
                            <input type="number" name="items_per_page" id="items_per_page" min="1" max="100" value="<?php echo intval($config['items_per_page'] ?? 10); ?>">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Source">
                    <a href="?page=asap-content-sources" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Add styles for the admin page -->
    <style>
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: gray;
        }
        .button-danger {
            color: #a00 !important;
        }
        .button-danger:hover {
            color: #dc3232 !important;
        }
        
        /* Metrics dashboard */
        .metrics-dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .metrics-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            min-width: 180px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        .metrics-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #23282d;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
        }
        
        /* Duplicate report styling */
        .duplicate-set {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ccd0d4;
        }
        .duplicate-set h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
        }
    </style>
    
    <!-- Add JavaScript for source type field toggling -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelector = document.getElementById('type');
            
            if (typeSelector) {
                typeSelector.addEventListener('change', function() {
                    const scraperFields = document.querySelectorAll('.scraper-field');
                    const apiFields = document.querySelectorAll('.api-field');
                    
                    // Hide all first
                    scraperFields.forEach(field => field.style.display = 'none');
                    apiFields.forEach(field => field.style.display = 'none');
                    
                    // Show relevant fields based on selection
                    if (this.value === 'scraper') {
                        scraperFields.forEach(field => field.style.display = '');
                    } else if (this.value === 'api') {
                        apiFields.forEach(field => field.style.display = '');
                    }
                });
            }
        });
    </script>
</div> 