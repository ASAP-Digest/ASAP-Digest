<?php
/**
 * Plugin Name:     ASAP Digest Core
 * Plugin URI:      https://asapdigest.com/
 * Description:     Core functionality for ASAP Digest app
 * Author:          ASAP Digest
 * Author URI:      https://philoveracity.com/
 * Text Domain:     adc
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         ASAPDigest_Core
 */

define('ASAP_DIGEST_SCHEMA_VERSION', '1.0.2');

// Include Better Auth configuration
require_once(plugin_dir_path(__FILE__) . 'better-auth-config.php');

load_plugin_textdomain('adc', false, dirname(plugin_basename(__FILE__)) . '/languages/');

global $wpdb;

// Create custom tables on plugin activation
function asap_create_tables() {
  global $wpdb;
  ob_start(); // Start output buffering
  $charset_collate = $wpdb->get_charset_collate();
  $wpdb->suppress_errors(true); // Disable error display

  // Schema version check
  $installed_ver = get_option('asap_digest_schema_version');
  if ($installed_ver != ASAP_DIGEST_SCHEMA_VERSION) {
    require_once(plugin_dir_path(__FILE__) . 'upgrade.php');
  }

  // Digests table
  $digests_table = $wpdb->prefix . 'asap_digests';
  $digests_sql = "CREATE TABLE $digests_table (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    sentiment_score VARCHAR(20) DEFAULT NULL,
    life_moment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    share_link VARCHAR(255) DEFAULT NULL,
    podcast_url VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_sentiment (sentiment_score),
    FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
    INDEX idx_created_at (created_at)
  ) ENGINE=InnoDB $charset_collate;";
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($digests_sql);

  // Migration: Move existing digests from options table
  $option_prefix = 'asap_digest_';
  $options = $wpdb->get_results($wpdb->prepare(
    "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
    $option_prefix.'%'
  ), ARRAY_A);
  foreach ($options as $option) {
    $digest_id = absint(str_replace($option_prefix, '', $option['option_name']));
    $wpdb->insert(
      $digests_table,
      ['id' => $digest_id, 'content' => $option['option_value'], 'created_at' => current_time('mysql')],
      ['%d', '%s', '%s']
    );
    delete_option($option['option_name']);
  }

  // Notifications table
  $notifications_table = $wpdb->prefix . 'asap_notifications';
  $notifications_sql = "CREATE TABLE $notifications_table (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX unique_endpoint (endpoint(255)),
    FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
  ) ENGINE=InnoDB $charset_collate;";
  dbDelta($notifications_sql);

  $wpdb->suppress_errors(false); // Re-enable errors
  ob_end_clean(); // Discard any buffered output
  update_option('asap_digest_schema_version', ASAP_DIGEST_SCHEMA_VERSION);
}
register_activation_hook(__FILE__, 'asap_create_tables');


// Cleanup on deactivation
register_deactivation_hook(__FILE__, 'asap_cleanup_on_deactivation');

function asap_cleanup_on_deactivation() {
  if (!defined('WP_UNINSTALL_PLUGIN')) {
    global $wpdb;
    
    // Remove scheduled cleanup
    wp_clear_scheduled_hook('asap_cleanup_data');
    
    // Remove debug options
    delete_option('sms_digest_time');
  }
}


// Schedule cleanup of old digests and notifications
function asap_schedule_cleanup() {
  if (!wp_next_scheduled('asap_cleanup_data')) {
    wp_schedule_event(time(), 'daily', 'asap_cleanup_data');
  }
}
add_action('wp', 'asap_schedule_cleanup');

function asap_cleanup_data() {
  global $wpdb;
  $digests_table = $wpdb->prefix . 'asap_digests';
  $notifications_table = $wpdb->prefix . 'asap_notifications';
  $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
  
  // Secure delete queries
  $wpdb->query($wpdb->prepare(
    "DELETE FROM $digests_table WHERE created_at < %s",
    $cutoff_date
  ));
  
  $wpdb->query("DELETE FROM $notifications_table WHERE created_at < '$cutoff_date'");
}
add_action('asap_cleanup_data', 'asap_cleanup_data');

function create_asap_cpts() {
  // Base arguments shared across all post types
  $base_args = [
    'public' => true,
    'show_in_graphql' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'has_archive' => true,
    'menu_icon' => 'dashicons-admin-post',
  ];

  // Register each post type with unique GraphQL names
  register_post_type('article', array_merge($base_args, [
    'label' => '⚡️ - Articles',
    'graphql_single_name' => 'Article',
    'graphql_plural_name' => 'Articles'
  ]));

  register_post_type('podcast', array_merge($base_args, [
    'label' => '⚡️ - Podcasts',
    'graphql_single_name' => 'Podcast',
    'graphql_plural_name' => 'Podcasts'
  ]));

  register_post_type('keyterm', array_merge($base_args, [
    'label' => '⚡️ - Key Terms',
    'graphql_single_name' => 'KeyTerm',
    'graphql_plural_name' => 'KeyTerms'
  ]));

  register_post_type('financial', array_merge($base_args, [
    'label' => '⚡️ - Financial Bites',
    'graphql_single_name' => 'Financial',
    'graphql_plural_name' => 'Financials'
  ]));

  register_post_type('xpost', array_merge($base_args, [
    'label' => '⚡️ - X Posts',
    'graphql_single_name' => 'XPost',
    'graphql_plural_name' => 'XPosts'
  ]));

  register_post_type('reddit', array_merge($base_args, [
    'label' => '⚡️ - Reddit Buzz',
    'graphql_single_name' => 'Reddit',
    'graphql_plural_name' => 'Reddits'
  ]));

  register_post_type('event', array_merge($base_args, [
    'label' => '⚡️ - Events',
    'graphql_single_name' => 'Event',
    'graphql_plural_name' => 'Events'
  ]));

  register_post_type('polymarket', array_merge($base_args, [
    'label' => '⚡️ - Polymarket',
    'graphql_single_name' => 'Polymarket',
    'graphql_plural_name' => 'Polymarkets'
  ]));
}
add_action('init', 'create_asap_cpts');

// Register custom REST API endpoint for digest generation
function asap_register_rest_routes() {
  register_rest_route('asap/v1', '/digest', [
    'methods' => 'GET',
    'callback' => 'asap_generate_digest',
    'permission_callback' => function () {
      check_ajax_referer('asap_digest_nonce', 'security');
      return current_user_can('read');
    },
  ]);

  // JWT Authentication endpoints
  register_rest_route('asap/v1', '/auth/token', [
    'methods' => 'POST',
    'callback' => 'asap_generate_jwt_token',
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('asap/v1', '/auth/validate', [
    'methods' => 'POST',
    'callback' => 'asap_validate_jwt_token',
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('asap/v1', '/auth/refresh', [
    'methods' => 'POST',
    'callback' => 'asap_refresh_jwt_token',
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('asap/v1', '/auth/register', [
    'methods' => 'POST',
    'callback' => 'asap_register_user',
    'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'asap_register_rest_routes');

/**
 * Register nonce endpoint
 */
add_action('rest_api_init', function() {
  register_rest_route('asap/v1', '/nonce', [
    'methods' => 'GET',
    'callback' => fn($req) => rest_ensure_response(wp_create_nonce($req->get_param('action') ?: 'wp_rest'))
  ]);
});

/**
 * Generates daily digest content
 * 
 * @since 0.1.0
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Formatted response or error
 */
function asap_generate_digest(WP_REST_Request $request) {
  global $wpdb;
  $digests_table = $wpdb->prefix . 'asap_digests';

  $args = [
    'post_type' => ['article', 'podcast', 'keyterm', 'financial', 'xpost', 'reddit', 'event', 'polymarket'],
    'post_status' => 'publish',
    'posts_per_page' => -1,
  ];
  $posts = get_posts($args);
  if (empty($posts)) {
    return new WP_Error('no_posts', 'No digest content available.', ['status' => 404]);
  }

  $digest = '### ASAP Digest - ' . date('Y-m-d') . "\n\n";
  foreach ($posts as $post) {
    $post_type = str_replace('Post', '', get_post_type($post));
    $digest .= "**{$post->post_title} ({$post_type})**\n";
    $fields = get_fields($post->ID);
    if ($post_type === 'article' && isset($fields['summary'])) $digest .= "- {$fields['summary']}\n";
    elseif ($post_type === 'podcast' && isset($fields['summary'])) $digest .= "- {$fields['summary']}\n";
    elseif ($post_type === 'keyterm' && isset($fields['mentions'])) $digest .= "- Mentions: " . implode(', ', $fields['mentions']) . "\n";
    elseif ($post_type === 'financial' && isset($fields['summary'])) $digest .= "- {$fields['summary']}\n";
    elseif ($post_type === 'xpost' && isset($fields['text'])) $digest .= "- {$fields['text']}\n";
    elseif ($post_type === 'reddit' && isset($fields['summary'])) $digest .= "- {$fields['summary']}\n";
    elseif ($post_type === 'event' && isset($fields['description'])) $digest .= "- {$fields['description']}\n";
    elseif ($post_type === 'polymarket' && isset($fields['changes'])) $digest .= "- Changes: " . implode(', ', $fields['changes']) . "\n";
    $digest .= "\n";
  }

  $digest_id = time();
  $digest_id = absint(time()); // Sanitized ID
  $wpdb->insert(
    $digests_table,
    ['content' => $digest, 'share_link' => get_rest_url(null, "asap/v1/digest/{$digest_id}")],
    ['%s', '%s', '%s']
  );

  if ($wpdb->last_error) {
    error_log('Digest insertion error: ' . $wpdb->last_error);
  }

  // Determine the correct API endpoint
  $api_endpoints = [
    'https://asapdigest.com/api/generate-podcast',
    'https://asapdigest.local/api/generate-podcast',
    'http://asapdigest.local/api/generate-podcast',
    'http://localhost:5173/api/generate-podcast'
  ];
  
  $api_endpoint = $api_endpoints[0]; // Default to first endpoint

  // Trigger podcast generation
  $response = wp_remote_post($api_endpoint, [
    'body' => json_encode(['digestId' => $digest_id, 'voiceSettings' => ['voice1' => 'en-US', 'voice2' => 'en-GB', 'rate' => 1.0]]),
    'headers' => ['Content-Type' => 'application/json'],
  ]);

  if (is_wp_error($response)) {
    error_log('Podcast generation error: ' . $response->get_error_message());
  }

  return rest_ensure_response([
    'content' => $digest,
    'share_link' => get_rest_url(null, "asap/v1/digest/{$digest_id}"),
  ]);
}

// Register endpoint to retrieve a specific digest
function asap_register_digest_retrieval() {
  register_rest_route('asap/v1', '/digest/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => 'asap_get_digest',
    'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'asap_register_digest_retrieval');

function asap_get_digest(WP_REST_Request $request) {
  global $wpdb;
  $digests_table = $wpdb->prefix . 'asap_digests';
  $digest_id = $request['id'];
  $digest = $wpdb->get_var($wpdb->prepare("SELECT content FROM $digests_table WHERE id = %d", $digest_id));
  if (!$digest) {
    return new WP_Error('not_found', 'Digest not found.', ['status' => 404]);
  }
  return rest_ensure_response(['content' => $digest]);
}

// Register endpoint to manage notifications
function asap_register_notification_routes() {
  register_rest_route('asap/v1', '/subscribe-push', [
    'methods' => 'POST',
    'callback' => 'asap_subscribe_push',
    'permission_callback' => function () {
      check_ajax_referer('asap_push_nonce', 'security');
      return current_user_can('read');
    },
  ]);
  register_rest_route('asap/v1', '/send-notification', [
    'methods' => 'POST',
    'callback' => 'asap_send_notification',
    'permission_callback' => function () {
      return current_user_can('manage_options');
    },
  ]);
}
add_action('rest_api_init', 'asap_register_notification_routes');

function asap_subscribe_push(WP_REST_Request $request) {
  global $wpdb;
  $notifications_table = $wpdb->prefix . 'asap_notifications';
  $data = $request->get_json_params();
  $subscription = $data['subscription'];
  $user_id = get_current_user_id();

  // Validate subscription data
  if (!isset($subscription['endpoint']) || !filter_var($subscription['endpoint'], FILTER_VALIDATE_URL)) {
    return new WP_Error('invalid_data', 'Invalid subscription data', ['status' => 400]);
  }

  $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $notifications_table WHERE endpoint = %s", $subscription['endpoint']));
  if ($existing) {
    $wpdb->update(
      $notifications_table,
      ['user_id' => $user_id, 'p256dh' => $subscription['keys']['p256dh'], 'auth' => $subscription['keys']['auth']],
      ['endpoint' => $subscription['endpoint']],
      ['%d', '%s', '%s'],
      ['%s']
    );
  } else {
    $wpdb->insert(
      $notifications_table,
      [
        'user_id' => $user_id,
        'endpoint' => $subscription['endpoint'],
        'p256dh' => $subscription['keys']['p256dh'],
        'auth' => $subscription['keys']['auth'],
      ],
      ['%d', '%s', '%s', '%s']
    );
  }

  return rest_ensure_response(['success' => true]);
}

function asap_send_notification(WP_REST_Request $request) {
  global $wpdb;
  $notifications_table = $wpdb->prefix . 'asap_notifications';
  $vapid = [
    'publicKey' => 'your-vapid-public-key',
    'privateKey' => 'your-vapid-private-key',
  ];
  $payload = $request->get_json_params();
  $subscriptions = $wpdb->get_results("SELECT endpoint, p256dh, auth FROM $notifications_table", ARRAY_A);

  // Determine the correct API endpoint
  $api_endpoints = [
    'https://asapdigest.com/api/send-push',
    'https://asapdigest.local/api/send-push',
    'http://asapdigest.local/api/send-push',
    'http://localhost:5173/api/send-push'
  ];
  
  $api_endpoint = $api_endpoints[0]; // Default to first endpoint

  foreach ($subscriptions as $sub) {
    $subscription = [
      'endpoint' => $sub['endpoint'],
      'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
    ];
    $response = wp_remote_post($api_endpoint, [
      'body' => json_encode(['subscription' => $subscription, 'payload' => $payload]),
      'headers' => ['Content-Type' => 'application/json'],
      'sslverify' => true // Force SSL verification
    ]);
    if (is_wp_error($response)) {
      error_log('Push notification error: ' . $response->get_error_message());
    }
  }

  return rest_ensure_response(['success' => true]);
}

// Add CORS headers for SvelteKit frontend
function asap_add_cors_headers() {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  $allowed_origins = apply_filters('asap_allowed_origins', [
    'https://asapdigest.com',
    'https://app.asapdigest.com',  // Add app subdomain
    'https://asapdigest.local',
    'http://asapdigest.local',
    'http://localhost:5173'
  ]);
  
  if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . esc_url_raw($origin));
  } else {
    header('Access-Control-Allow-Origin: https://asapdigest.com');
  }
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Authorization, Content-Type');
  
  // Handle preflight OPTIONS requests
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400'); // Cache preflight response for 24 hours
    exit(0);
  }
}
add_action('rest_api_init', 'asap_add_cors_headers');

// Register endpoint to update podcast URL
function asap_register_podcast_url_update() {
  register_rest_route('asap/v1', '/update-podcast-url', [
    'methods' => 'POST',
    'callback' => 'asap_update_podcast_url',
    'permission_callback' => function () {
      return current_user_can('manage_options');
    },
  ]);
}
add_action('rest_api_init', 'asap_register_podcast_url_update');

function asap_update_podcast_url(WP_REST_Request $request) {
  global $wpdb;
  $digests_table = $wpdb->prefix . 'asap_digests';
  $data = $request->get_json_params();
  $digest_id = $data['digestId'];
  $podcast_url = $data['podcastUrl'];
  $podcast_url = esc_url_raw($podcast_url);

  $wpdb->update(
    $digests_table,
    ['podcast_url' => $podcast_url],
    ['id' => $digest_id],
    ['%s'],
    ['%d']
  );

  return rest_ensure_response(['success' => true]);
}

// Register endpoint for podcast RSS feed
function asap_register_podcast_rss() {
  register_rest_route('asap/v1', '/podcast-rss', [
    'methods' => 'GET',
    'callback' => 'asap_generate_podcast_rss',
    'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'asap_register_podcast_rss');

function asap_generate_podcast_rss(WP_REST_Request $request) {
  global $wpdb;
  $digests_table = $wpdb->prefix . 'asap_digests';
  $digests = $wpdb->get_results("SELECT * FROM $digests_table WHERE podcast_url IS NOT NULL ORDER BY created_at DESC", ARRAY_A);

  $rss = '<?xml version="1.0" encoding="UTF-8"?>';
  $rss .= '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">';
  $rss .= '<channel>';
  $rss .= '<title>ASAP Digest Daily Podcast</title>';
  $rss .= '<link>https://asapdigest.com</link>';
  $rss .= '<description>Your daily digest of news, podcasts, and markets in audio form.</description>';
  $rss .= '<itunes:author>ASAP Digest Team</itunes:author>';
  $rss .= '<itunes:category text="News" />';

  foreach ($digests as $digest) {
    $rss .= '<item>';
    $rss .= '<title>ASAP Digest - ' . esc_html($digest['created_at']) . '</title>';
    $rss .= '<description><![CDATA[' . esc_html(substr($digest['content'], 0, 200)) . '...]]></description>';
    $rss .= '<pubDate>' . date('r', strtotime($digest['created_at'])) . '</pubDate>';
    $rss .= '<enclosure url="' . esc_url($digest['podcast_url']) . '" length="0" type="audio/wav" />';
    $rss .= '<guid>' . esc_url($digest['podcast_url']) . '</guid>';
    $rss .= '</item>';
  }

  $rss .= '</channel>';
  $rss .= '</rss>';

  return new WP_REST_Response($rss, 200, ['Content-Type' => 'application/rss+xml']);
}

// WordPress SMS Scheduling System ### SMS Integration Core
add_action('admin_init', function() {
  add_settings_field(
    'sms_digest_time', 
    'Default SMS Send Time',
    'sms_time_callback',
    'asapdigest-settings'
  );
});

// Add missing callback function
function sms_time_callback() {
  $time = get_option('sms_digest_time', '09:00');
  echo '<input type="time" name="sms_digest_time" value="' . esc_attr($time) . '" />';
  echo '<p class="description">' . esc_html__('Daily time to send SMS digests', 'adc') . '</p>';
}

// Cron System for Digest Delivery
add_filter('cron_schedules', function($schedules) {
  $schedules['five_min_sms'] = [
    'interval' => 300,
    'display' => __('Every 5 Minutes for SMS')
  ];
  return $schedules;
});

// Uninstall handler
register_uninstall_hook(__FILE__, 'asap_digest_uninstall');
function asap_digest_uninstall() {
  global $wpdb;
  
  if (!defined('WP_UNINSTALL_PLUGIN')) {
    return;
  }

  // Remove tables
  $tables = [
    $wpdb->prefix . 'asap_digests',
    $wpdb->prefix . 'asap_notifications'
  ];
  
  foreach ($tables as $table) {
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table));
  }
  delete_option('sms_digest_time');
}

/**
 * Generate JWT token for user authentication
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Response with token or error
 */
function asap_generate_jwt_token(WP_REST_Request $request) {
  $data = $request->get_json_params();
  $username = sanitize_user($data['username']);
  $password = $data['password'];

  // Authenticate user
  $user = wp_authenticate($username, $password);
  if (is_wp_error($user)) {
    return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
  }

  // Generate JWT token
  $issued_at = time();
  $expiration = $issued_at + (DAY_IN_SECONDS * 7); // Token valid for 7 days
  
  $token = [
    'iss' => get_site_url(),
    'iat' => $issued_at,
    'exp' => $expiration,
    'data' => [
      'user' => [
        'id' => $user->ID,
      ],
    ],
  ];
  
  // Use WordPress auth_key as JWT key
  $jwt_key = defined('AUTH_KEY') ? AUTH_KEY : 'asapdigest-jwt-secret';
  
  // Use JWT library if available, otherwise simple implementation
  $jwt = jwt_encode($token, $jwt_key);
  
  return rest_ensure_response([
    'token' => $jwt,
    'user_id' => $user->ID,
    'user_email' => $user->user_email,
    'user_display_name' => $user->display_name,
    'exp' => $expiration,
  ]);
}

/**
 * Validate JWT token
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Response with validation status or error
 */
function asap_validate_jwt_token(WP_REST_Request $request) {
  $data = $request->get_json_params();
  $token = $data['token'];
  
  // Use WordPress auth_key as JWT key
  $jwt_key = defined('AUTH_KEY') ? AUTH_KEY : 'asapdigest-jwt-secret';
  
  try {
    $decoded = jwt_decode($token, $jwt_key, true);
    return rest_ensure_response(['valid' => true, 'data' => $decoded->data]);
  } catch (Exception $e) {
    return new WP_Error('invalid_token', 'Invalid or expired token', ['status' => 401]);
  }
}

/**
 * Refresh JWT token
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Response with new token or error
 */
function asap_refresh_jwt_token(WP_REST_Request $request) {
  $data = $request->get_json_params();
  $token = $data['token'];
  
  // Use WordPress auth_key as JWT key
  $jwt_key = defined('AUTH_KEY') ? AUTH_KEY : 'asapdigest-jwt-secret';
  
  try {
    $decoded = jwt_decode($token, $jwt_key, true);
    $user_id = $decoded->data->user->id;
    
    // Get user
    $user = get_user_by('id', $user_id);
    if (!$user) {
      return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }
    
    // Generate new token
    $issued_at = time();
    $expiration = $issued_at + (DAY_IN_SECONDS * 7);
    
    $new_token = [
      'iss' => get_site_url(),
      'iat' => $issued_at,
      'exp' => $expiration,
      'data' => [
        'user' => [
          'id' => $user->ID,
        ],
      ],
    ];
    
    $jwt = jwt_encode($new_token, $jwt_key);
    
    return rest_ensure_response([
      'token' => $jwt,
      'user_id' => $user->ID,
      'exp' => $expiration,
    ]);
  } catch (Exception $e) {
    return new WP_Error('invalid_token', 'Invalid or expired token', ['status' => 401]);
  }
}

/**
 * Simple JWT encode function (should be replaced with a proper library)
 * 
 * @param array $payload JWT payload
 * @param string $key Secret key
 * @return string JWT token
 */
function jwt_encode($payload, $key) {
  $header = ['alg' => 'HS256', 'typ' => 'JWT'];
  
  $header_encoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
  $payload_encoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
  
  $signature = hash_hmac('SHA256', "$header_encoded.$payload_encoded", $key, true);
  $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
  
  return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Simple JWT decode function (should be replaced with a proper library)
 * 
 * @param string $jwt JWT token
 * @param string $key Secret key
 * @param bool $verify Whether to verify signature
 * @return object Decoded token
 * @throws Exception If token is invalid or expired
 */
function jwt_decode($jwt, $key, $verify = true) {
  $parts = explode('.', $jwt);
  if (count($parts) !== 3) {
    throw new Exception('Invalid token format');
  }
  
  [$header_encoded, $payload_encoded, $signature_encoded] = $parts;
  
  if ($verify) {
    $signature = base64_decode(strtr($signature_encoded, '-_', '+/'));
    $expected_signature = hash_hmac('SHA256', "$header_encoded.$payload_encoded", $key, true);
    
    if (!hash_equals($expected_signature, $signature)) {
      throw new Exception('Invalid signature');
    }
  }
  
  $payload = json_decode(base64_decode(strtr($payload_encoded, '-_', '+/')));
  
  // Check if token is expired
  if (isset($payload->exp) && $payload->exp < time()) {
    throw new Exception('Token expired');
  }
  
  return $payload;
}

/**
 * Register a new user
 * 
 * @param WP_REST_Request $request REST API request object
 * @return WP_REST_Response|WP_Error Response with user data or error
 */
function asap_register_user(WP_REST_Request $request) {
  $data = $request->get_json_params();
  
  // Validate required fields
  $required_fields = ['username', 'email', 'password'];
  foreach ($required_fields as $field) {
    if (empty($data[$field])) {
      return new WP_Error(
        'missing_field',
        sprintf(__('Missing required field: %s', 'adc'), $field),
        ['status' => 400]
      );
    }
  }
  
  // Sanitize input
  $username = sanitize_user($data['username']);
  $email = sanitize_email($data['email']);
  $password = $data['password'];
  
  // Validate email
  if (!is_email($email)) {
    return new WP_Error('invalid_email', __('Invalid email address', 'adc'), ['status' => 400]);
  }
  
  // Check if username or email already exists
  if (username_exists($username)) {
    return new WP_Error('username_exists', __('Username already exists', 'adc'), ['status' => 400]);
  }
  
  if (email_exists($email)) {
    return new WP_Error('email_exists', __('Email address already exists', 'adc'), ['status' => 400]);
  }
  
  // Create user
  $user_id = wp_create_user($username, $password, $email);
  
  if (is_wp_error($user_id)) {
    return new WP_Error(
      'registration_failed',
      $user_id->get_error_message(),
      ['status' => 500]
    );
  }
  
  // Set user role
  $user = new WP_User($user_id);
  $user->set_role('subscriber');
  
  // Add additional user meta if provided
  if (!empty($data['first_name'])) {
    update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
  }
  
  if (!empty($data['last_name'])) {
    update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
  }
  
  // Generate JWT token for the new user
  $issued_at = time();
  $expiration = $issued_at + (DAY_IN_SECONDS * 7);
  
  $token = [
    'iss' => get_site_url(),
    'iat' => $issued_at,
    'exp' => $expiration,
    'data' => [
      'user' => [
        'id' => $user_id,
      ],
    ],
  ];
  
  $jwt_key = defined('AUTH_KEY') ? AUTH_KEY : 'asapdigest-jwt-secret';
  $jwt = jwt_encode($token, $jwt_key);
  
  // Return user data and token
  return rest_ensure_response([
    'user_id' => $user_id,
    'username' => $username,
    'email' => $email,
    'token' => $jwt,
    'exp' => $expiration,
  ]);
}

// Register Better Auth settings page in admin
add_action('admin_menu', 'asap_add_better_auth_settings_page');

// Register Better Auth integration endpoints
function asap_register_better_auth_routes() {
  // Endpoint for creating WordPress user from Better Auth
  register_rest_route('asap/v1', '/auth/create-wp-user', [
    'methods' => 'POST',
    'callback' => 'asap_create_wp_user',
    'permission_callback' => 'asap_validate_better_auth_request',
    'args' => [
      'ba_user_id' => [
        'required' => true,
        'type' => 'string',
        'validate_callback' => function($param) {
          return !empty($param);
        }
      ],
      'email' => [
        'required' => true,
        'type' => 'string',
        'validate_callback' => function($param) {
          return is_email($param);
        }
      ],
      'username' => [
        'required' => true,
        'type' => 'string',
        'validate_callback' => function($param) {
          return !empty($param);
        }
      ],
      'name' => [
        'required' => false,
        'type' => 'string'
      ]
    ]
  ]);

  // Endpoint for creating WordPress session from Better Auth
  register_rest_route('asap/v1', '/auth/create-wp-session', [
    'methods' => 'POST',
    'callback' => 'asap_create_wp_session',
    'permission_callback' => 'asap_validate_better_auth_request',
    'args' => [
      'ba_user_id' => [
        'required' => true,
        'type' => 'string',
        'validate_callback' => function($param) {
          return !empty($param);
        }
      ]
    ]
  ]);
}
add_action('rest_api_init', 'asap_register_better_auth_routes');

/**
 * Validate request from Better Auth
 * Uses shared secret for security
 *
 * @param WP_REST_Request $request
 * @return bool
 */
function asap_validate_better_auth_request(WP_REST_Request $request) {
  // Get the shared secret from WordPress config
  $better_auth_shared_secret = '';
  
  // First, try to get from constant if defined
  if (defined('BETTER_AUTH_SHARED_SECRET')) {
    $better_auth_shared_secret = BETTER_AUTH_SHARED_SECRET;
  } 
  // Second, try to get from option if stored
  else if (get_option('better_auth_shared_secret')) {
    $better_auth_shared_secret = get_option('better_auth_shared_secret');
  } 
  // Finally, fallback to AUTH_KEY
  else {
    error_log('[ASAP Better Auth] No shared secret defined. Using AUTH_KEY as fallback.');
    $better_auth_shared_secret = defined('AUTH_KEY') ? AUTH_KEY : '';
  }

  if (empty($better_auth_shared_secret)) {
    error_log('[ASAP Better Auth] CRITICAL: No valid shared secret available for authentication!');
    return false;
  }

  // Get the authorization header
  $auth_header = $request->get_header('Authorization');
  if (empty($auth_header) || strpos($auth_header, 'Bearer ') !== 0) {
    return false;
  }

  // Extract the token
  $token = str_replace('Bearer ', '', $auth_header);
  
  // Validate the token (simple HMAC verification for now)
  $timestamp = $request->get_header('X-Better-Auth-Timestamp');
  $expected_token = hash_hmac('sha256', $timestamp . '|' . $request->get_param('ba_user_id'), $better_auth_shared_secret);
  
  // Check if token is valid and not expired (within 5 minutes)
  $is_valid_token = hash_equals($expected_token, $token);
  $is_recent = (time() - intval($timestamp)) < 300; // 5 minutes
  
  return $is_valid_token && $is_recent;
}

/**
 * Create WordPress user from Better Auth
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function asap_create_wp_user(WP_REST_Request $request) {
  global $wpdb;
  
  $ba_user_id = $request->get_param('ba_user_id');
  $email = $request->get_param('email');
  $username = $request->get_param('username');
  $name = $request->get_param('name') ?: $username;
  
  // Check if user exists by email
  $existing_user = get_user_by('email', $email);
  if ($existing_user) {
    // User exists, update the mapping
    $wpdb->replace(
      $wpdb->prefix . 'ba_wp_user_map',
      [
        'ba_user_id' => $ba_user_id,
        'wp_user_id' => $existing_user->ID
      ],
      ['%s', '%d']
    );
    
    return new WP_REST_Response([
      'success' => true,
      'wp_user_id' => $existing_user->ID,
      'message' => 'User mapping updated successfully',
      'existing' => true
    ], 200);
  }
  
  // Generate a random password
  $random_password = wp_generate_password(24, true, true);
  
  // Create new WordPress user
  $wp_user_id = wp_create_user($username, $random_password, $email);
  
  if (is_wp_error($wp_user_id)) {
    return new WP_REST_Response([
      'success' => false,
      'message' => $wp_user_id->get_error_message()
    ], 400);
  }
  
  // Update user meta
  wp_update_user([
    'ID' => $wp_user_id,
    'display_name' => $name,
    'nickname' => $username,
    'first_name' => $name,
    'show_admin_bar_front' => false
  ]);
  
  // Add role
  $user = new WP_User($wp_user_id);
  $user->set_role('subscriber');
  
  // Create user mapping in the database
  $wpdb->insert(
    $wpdb->prefix . 'ba_wp_user_map',
    [
      'ba_user_id' => $ba_user_id,
      'wp_user_id' => $wp_user_id
    ],
    ['%s', '%d']
  );
  
  return new WP_REST_Response([
    'success' => true,
    'wp_user_id' => $wp_user_id,
    'message' => 'User created successfully'
  ], 201);
}

/**
 * Create WordPress session from Better Auth
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function asap_create_wp_session(WP_REST_Request $request) {
  global $wpdb;
  
  $ba_user_id = $request->get_param('ba_user_id');
  
  // Get WordPress user ID from mapping
  $wp_user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT wp_user_id FROM {$wpdb->prefix}ba_wp_user_map WHERE ba_user_id = %s",
    $ba_user_id
  ));
  
  if (!$wp_user_id) {
    return new WP_REST_Response([
      'success' => false,
      'message' => 'No WordPress user found for this Better Auth user'
    ], 404);
  }
  
  // Get user object
  $user = get_user_by('ID', $wp_user_id);
  if (!$user) {
    return new WP_REST_Response([
      'success' => false,
      'message' => 'WordPress user not found'
    ], 404);
  }
  
  // Create session using core implementation
  $result = asap_create_wp_session_core($wp_user_id);
  
  if (is_wp_error($result)) {
    return new WP_REST_Response([
      'success' => false,
      'message' => $result->get_error_message()
    ], 400);
  }
  
  // Return session info
  return new WP_REST_Response([
    'success' => true,
    'wp_user_id' => $wp_user_id,
    'message' => 'WordPress session created successfully',
    'cookie_info' => [
      'name' => AUTH_COOKIE,
      'logged_in_cookie' => LOGGED_IN_COOKIE
    ]
  ], 200);
}