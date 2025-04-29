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
 * @created         03.31.25 | 03:34 PM PDT
 * @file-marker     ASAP_Digest_Core_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASAP_DIGEST_SCHEMA_VERSION', '1.0.2');

// Include Better Auth configuration
require_once(plugin_dir_path(__FILE__) . 'better-auth-config.php');
// Include the API Base Controller FIRST
require_once(plugin_dir_path(__FILE__) . 'includes/api/class-rest-base.php');
// Include the new Session Check Controller
require_once(plugin_dir_path(__FILE__) . 'includes/api/class-session-check-controller.php');
// Include the new Sync Token Controller
require_once(plugin_dir_path(__FILE__) . 'includes/api/class-sync-token-controller.php');
// Include the REST Auth Controller
require_once(plugin_dir_path(__FILE__) . 'includes/api/class-rest-auth.php');
// Include the new SK User Sync Controller
require_once(plugin_dir_path(__FILE__) . 'includes/api/class-sk-user-sync.php');

load_plugin_textdomain('adc', false, dirname(plugin_basename(__FILE__)) . '/languages/');

global $wpdb;

// Register activation hook to create tables
register_activation_hook(__FILE__, 'asap_ensure_database_tables');

// Register deactivation hook for cleanup
register_deactivation_hook(__FILE__, 'asap_cleanup_on_deactivation');

// Schedule cleanup of old digests and notifications
function asap_schedule_cleanup() {
  if (!wp_next_scheduled('asap_cleanup_data')) {
    wp_schedule_event(time(), 'daily', 'asap_cleanup_data');
  }
}
add_action('wp', 'asap_schedule_cleanup');

/**
 * @description Clean up plugin data on deactivation
 * @return void
 * @example
 * // Called during plugin deactivation
 * asap_cleanup_on_deactivation();
 * @created 03.29.25 | 03:45 PM PDT
 */
function asap_cleanup_on_deactivation() {
  if (!defined('WP_UNINSTALL_PLUGIN')) {
    global $wpdb;
    
    // Remove scheduled cleanup
    wp_clear_scheduled_hook('asap_cleanup_data');
    
    // Remove debug options
    delete_option('sms_digest_time');
  }
}

/**
 * @description Clean up old digests and notifications data
 * @return void
 * @example
 * // Perform cleanup of old data
 * asap_cleanup_data();
 * @created 03.29.25 | 03:45 PM PDT
 */
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

/**
 * @description Initialize ASAP Digest Core plugin hooks and features
 * @hook add_action('plugins_loaded', 'asap_init_core', 5)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 04:25 PM PDT
 */
function asap_init_core() {
    // Core functionality (priority 10)
    add_action('init', 'create_asap_cpts', 10);
    add_action('wp', 'asap_schedule_cleanup', 10);
    add_action('rest_api_init', 'asap_register_rest_routes', 10);
    add_action('rest_api_init', 'asap_register_digest_retrieval', 10);
    add_action('rest_api_init', 'asap_register_notification_routes', 10);
    add_action('rest_api_init', 'asap_register_podcast_url_update', 10);
    add_action('rest_api_init', 'asap_register_podcast_rss', 10);
    // Register the new session check route
    add_action('rest_api_init', function() {
        $controller = new \ASAPDigest\Core\API\Session_Check_Controller();
        $controller->register_routes();
    }, 10);
    
    // Register the new sync token validation route (NEW)
    add_action('rest_api_init', function() {
        $controller = new \ASAPDigest\Core\API\Sync_Token_Controller();
        $controller->register_routes();
    }, 10);
    
    // Auth Controller (NEW)
    add_action('rest_api_init', function() {
        $controller = new \ASAPDigest\Core\API\ASAP_Digest_REST_Auth();
        $controller->register_routes();
    }, 10);
    
    // Feature-specific hooks (priority 20-29)
    add_action('asap_cleanup_data', 'asap_cleanup_data', 20);
    
    // Admin interface hooks (priority 30+)
    add_action('admin_menu', 'asap_add_central_command_menu', 30);
    add_action('admin_enqueue_scripts', 'asap_enqueue_admin_styles', 30);
    
    // CORS and headers (priority 100+)
    add_action('rest_api_init', 'asap_add_cors_headers', 15);

    // Register SK User Sync endpoint
    add_action('rest_api_init', function() {
        $sk_user_sync = new \ASAPDigest\Core\API\SK_User_Sync();
        $sk_user_sync->register_routes();
    });
}

// Initialize core functionality early
add_action('plugins_loaded', 'asap_init_core', 5);

/**
 * @description Enqueue admin styles for ASAP Digest Core
 * @hook add_action('admin_enqueue_scripts', 'asap_enqueue_admin_styles', 30)
 * @param string $hook The current admin page hook
 * @return void
 * @created 03.30.25 | 04:48 PM PDT
 */
function asap_enqueue_admin_styles($hook) {
    // Only load on our plugin's admin pages
    if (strpos($hook, 'asap') === false) {
        return;
    }

    wp_enqueue_style(
        'asap-admin-styles',
        plugins_url('admin/css/asap-admin.css', __FILE__),
        [],
        ASAP_DIGEST_SCHEMA_VERSION
    );
}

/**
 * @description Register custom post types for ASAP Digest
 * @hook add_action('init', 'create_asap_cpts', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
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

/**
 * @description Register custom REST API endpoint for digest generation
 * @hook add_action('rest_api_init', 'asap_register_rest_routes', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
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

/**
 * @description Register nonce endpoint
 * @hook add_action('rest_api_init', function() {
 *   register_rest_route('asap/v1', '/nonce', [
 *     'methods' => 'GET',
 *     'callback' => fn($req) => rest_ensure_response(wp_create_nonce($req->get_param('action') ?: 'wp_rest'))
 *   ]);
 * });
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
add_action('rest_api_init', function() {
  register_rest_route('asap/v1', '/nonce', [
    'methods' => 'GET',
    'callback' => fn($req) => rest_ensure_response(wp_create_nonce($req->get_param('action') ?: 'wp_rest'))
  ]);
});

/**
 * @description Generate daily digest content from various post types
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response|WP_Error} Formatted response or error
 * @example
 * // Generate a digest via REST API
 * $response = asap_generate_digest($request);
 * @created 03.29.25 | 03:45 PM PDT
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
    'https://localhost:5173/api/generate-podcast'
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

/**
 * @description Register endpoint to retrieve a specific digest
 * @hook add_action('rest_api_init', 'asap_register_digest_retrieval', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_register_digest_retrieval() {
  register_rest_route('asap/v1', '/digest/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => 'asap_get_digest',
    'permission_callback' => '__return_true',
  ]);
}

/**
 * @description Retrieve a specific digest by ID
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response|WP_Error} Digest content or error
 * @example
 * // Get a digest by ID via REST API
 * $response = asap_get_digest($request);
 * @created 03.29.25 | 03:45 PM PDT
 */
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

/**
 * @description Register endpoint to manage notifications
 * @hook add_action('rest_api_init', 'asap_register_notification_routes', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
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

/**
 * @description Subscribe user to push notifications
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response|WP_Error} Success response or error
 * @example
 * // Subscribe a user to push notifications
 * $response = asap_subscribe_push($request);
 * @created 03.29.25 | 03:45 PM PDT
 */
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

/**
 * @description Send push notification to subscribed users
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response|WP_Error} Success response or error
 * @example
 * // Send a push notification
 * $response = asap_send_notification($request);
 * @created 03.29.25 | 03:45 PM PDT
 */
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
    'https://localhost:5173/api/send-push'
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

/**
 * @description Add CORS headers for SvelteKit frontend
 * @hook add_action('rest_api_init', 'asap_add_cors_headers', 15)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_add_cors_headers() {
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers'); // Remove default WP CORS headers

  add_filter('rest_pre_serve_request', function($value) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $current_site_url = site_url(); // Get the current WP site URL
    $allowed_origin = '';

    // Determine the allowed cross-origin partner based on the current WP environment
    if (strpos($current_site_url, 'asapdigest.local') !== false) {
      // Local environment: WP is asapdigest.local, partner is localhost:5173
      $allowed_origin = 'https://localhost:5173';
    } elseif (strpos($current_site_url, 'asapdigest.com') !== false) {
      // Production environment: WP is asapdigest.com, partner is app.asapdigest.com
      $allowed_origin = 'https://app.asapdigest.com';
    }
    // Add other environments (e.g., staging) if needed

    // Check if the request origin matches the *single* allowed partner for this environment
    if ($origin === $allowed_origin) {
      header("Access-Control-Allow-Origin: " . esc_url_raw($origin)); // Allow the specific partner origin
      header("Access-Control-Allow-Credentials: true"); 
      header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); 
      header("Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce"); 
    }
    // If $origin does not match $allowed_origin, no CORS headers are sent, blocking the request.

    // Handle preflight OPTIONS requests specifically for REST API
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
       // Check if the origin is the allowed partner for this environment
       if ($origin === $allowed_origin) {
            // Send required CORS headers for OPTIONS preflight
            // Headers already set above if origin matched, just add Max-Age and exit
            header('Access-Control-Max-Age: 86400'); // Cache preflight response for 24 hours
            status_header(204); // No Content for OPTIONS
            exit(0); // Exit early for OPTIONS requests
       } else {
            // If origin is not the allowed partner for OPTIONS, send 403 Forbidden
            status_header(403);
            exit(0);
       }
    }

    return $value; // Continue with the request processing
  });
}
add_action('rest_api_init', 'asap_add_cors_headers', 15); // Hook into rest_api_init with priority 15

/**
 * @description Register endpoint to update podcast URL
 * @hook add_action('rest_api_init', 'asap_register_podcast_url_update', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_register_podcast_url_update() {
  register_rest_route('asap/v1', '/update-podcast-url', [
    'methods' => 'POST',
    'callback' => 'asap_update_podcast_url',
    'permission_callback' => function () {
      return current_user_can('manage_options');
    },
  ]);
}

/**
 * @description Update podcast URL for a digest
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response|WP_Error} Success response or error
 * @example
 * // Update podcast URL for a digest
 * $response = asap_update_podcast_url($request);
 * @created 03.29.25 | 03:45 PM PDT
 */
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

/**
 * @description Register endpoint for podcast RSS feed
 * @hook add_action('rest_api_init', 'asap_register_podcast_rss', 10)
 * @since 1.0.0
 * @return void
 * @created 03.30.25 | 03:45 PM PDT
 */
function asap_register_podcast_rss() {
  register_rest_route('asap/v1', '/podcast-rss', [
    'methods' => 'GET',
    'callback' => 'asap_generate_podcast_rss',
    'permission_callback' => '__return_true',
  ]);
}

/**
 * @description Generate podcast RSS feed
 * @param {WP_REST_Request} request REST API request object
 * @return {WP_REST_Response} RSS feed content
 * @example
 * // Generate podcast RSS feed
 * $response = asap_generate_podcast_rss($request);
 * @created 03.29.25 | 03:45 PM PDT
 */
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

/**
 * @description Render the Central Command dashboard
 * @return void
 * @example
 * // Called when viewing the Central Command dashboard
 * asap_render_central_command_dashboard();
 * @created 03.30.25 | 04:35 PM PDT
 */
function asap_render_central_command_dashboard() {
    global $wpdb;

    // Ensure required tables exist
    asap_ensure_database_tables();
    
    ?>
    <div class="wrap asap-central-command">
        <h1>⚡️ ASAP Digest Central Command</h1>
        
        <div class="asap-card">
            <h2>Quick Stats</h2>
            <p>Welcome to ASAP Digest Central Command. This dashboard provides an overview of your system.</p>
            
            <?php
            // Get some basic stats
            $total_users = count_users();
            
            // Check if table exists before counting
            $table_name = $wpdb->prefix . 'ba_wp_user_map';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            $synced_users = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") : 0;
            
            // Check if digest post type exists before counting
            $digest_counts = post_type_exists('digest') ? wp_count_posts('digest') : null;
            $total_digests = $digest_counts ? ($digest_counts->publish ?? 0) : 0;
            ?>
            
            <div class="asap-status-grid">
                <div class="asap-status-item">
                    <strong>Total Users:</strong>
                    <span class="asap-stat"><?php echo $total_users['total_users']; ?></span>
                </div>
                
                <div class="asap-status-item">
                    <strong>Synced Users:</strong>
                    <span class="asap-stat"><?php echo $synced_users; ?></span>
                    <?php if (!$table_exists): ?>
                        <p class="asap-warning"><span class="dashicons dashicons-warning"></span> Sync table not found. Please deactivate and reactivate the plugin.</p>
                    <?php endif; ?>
                </div>
                
                <div class="asap-status-item">
                    <strong>Total Digests:</strong>
                    <span class="asap-stat"><?php echo $total_digests; ?></span>
                    <?php if (!post_type_exists('digest')): ?>
                        <p class="asap-warning"><span class="dashicons dashicons-warning"></span> Digest post type not registered.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="asap-card">
            <h2>Quick Actions</h2>
            <p>Access common tasks and management tools:</p>
            
            <div class="asap-action-grid">
                <a href="<?php echo admin_url('admin.php?page=asap-auth-settings'); ?>" class="asap-action-button">
                    <span class="dashicons dashicons-shield"></span>
                    Auth Settings
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-digest-management'); ?>" class="asap-action-button">
                    <span class="dashicons dashicons-media-text"></span>
                    Manage Digests
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-user-stats'); ?>" class="asap-action-button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    View User Stats
                </a>
                <a href="<?php echo admin_url('admin.php?page=asap-settings'); ?>" class="asap-action-button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Configure Settings
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @description Render the digest management page
 * @return void
 * @example
 * // Called when viewing the digest management page
 * asap_render_digest_management();
 * @created 03.30.25 | 04:35 PM PDT
 */
function asap_render_digest_management() {
    ?>
    <div class="wrap asap-central-command">
        <h1>Digest Management</h1>
        <div class="asap-card">
            <h2>Recent Digests</h2>
            <p>Manage your ASAP Digests here. This feature is coming soon.</p>
            <div class="asap-coming-soon">
                <span class="dashicons dashicons-clock"></span>
                <p>We're working on bringing you powerful digest management tools.</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @description Render the user stats page
 * @return void
 * @example
 * // Called when viewing the user stats page
 * asap_render_user_stats();
 * @created 03.30.25 | 04:35 PM PDT
 */
function asap_render_user_stats() {
    ?>
    <div class="wrap asap-central-command">
        <h1>User Statistics</h1>
        <div class="asap-card">
            <h2>User Analytics</h2>
            <p>View detailed user statistics here. This feature is coming soon.</p>
            <div class="asap-coming-soon">
                <span class="dashicons dashicons-chart-area"></span>
                <p>Advanced analytics and user insights are on the way.</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @description Render the settings page
 * @return void
 * @example
 * // Called when viewing the settings page
 * asap_render_settings();
 * @created 03.30.25 | 04:35 PM PDT
 */
function asap_render_settings() {
    ?>
    <div class="wrap asap-central-command">
        <h1>ASAP Settings</h1>
        <div class="asap-card">
            <h2>Global Configuration</h2>
            <p>Configure ASAP Digest settings here. This feature is coming soon.</p>
            <div class="asap-coming-soon">
                <span class="dashicons dashicons-admin-generic"></span>
                <p>Advanced configuration options will be available soon.</p>
            </div>
        </div>
    </div>
    <?php
}

// Add the user actions class
require_once plugin_dir_path(__FILE__) . 'includes/class-user-actions.php';

/**
 * @description Create necessary Better Auth & Sync database tables
 * @return void
 * @example
 * // Called during plugin activation or Central Command initialization
 * asap_ensure_database_tables();
 * @created 04.02.25 | 10:45 PM PDT
 * @updated 04.16.25 | 12:00 PM PDT // Added sync token table
 */
function asap_ensure_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // Ensure dbDelta is loaded

    // User Map Table
    $table_name_map = $wpdb->prefix . 'ba_wp_user_map';
    $sql_map = "CREATE TABLE IF NOT EXISTS $table_name_map (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT(20) UNSIGNED NOT NULL,
        ba_user_id VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY wp_user_id (wp_user_id),
        UNIQUE KEY ba_user_id (ba_user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    dbDelta($sql_map);

    // Sync Token Table (NEW)
    $table_name_sync_tokens = $wpdb->prefix . 'ba_sync_tokens'; // New table name
    $sql_sync_tokens = "CREATE TABLE IF NOT EXISTS $table_name_sync_tokens (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT(20) UNSIGNED NOT NULL,
        token VARCHAR(128) NOT NULL, -- Increased length for secure tokens
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token(64)), -- Index part of the token for performance/lookup
        KEY wp_user_id (wp_user_id),
        KEY expires_at (expires_at)
    ) $charset_collate;";
    dbDelta($sql_sync_tokens); // Create the new table

    // NOTE: Ensure ba_users and ba_sessions tables are created elsewhere (likely by Better Auth itself or previous setup)
}

/**
 * Output buffer callback to modify Set-Cookie headers for WordPress auth cookies.
 *
 * Ensures SameSite=None and Secure attributes are present, overriding any
 * potential modifications made later in the execution cycle.
 * This approach is used because standard filters (like auth_cookie_attributes)
 * proved insufficient in the specific environment, likely due to conflicts or
 * server configurations stripping the SameSite attribute after filter execution.
 * Output buffering allows modification of headers at the last moment before sending.
 *
 * @since TBD // Add version
 * @param string $buffer The output buffer content (HTML, etc.).
 * @return string The original buffer (headers are modified directly using header functions).
 */
function asap_modify_cookie_headers_callback( $buffer ) {
    // Check if headers have already been sent; if so, we cannot modify them.
    if ( headers_sent() ) {
        return $buffer;
    }

    // Define the names of WordPress Authentication cookies to target.
    // Using a map for quick lookups.
    $auth_cookie_names = [
        AUTH_COOKIE        => true,
        SECURE_AUTH_COOKIE => true,
        LOGGED_IN_COOKIE   => true,
        // TEST_COOKIE is sometimes used during login, but typically doesn't need SameSite=None.
        // Add it here if modification is found necessary for login process compatibility.
        // TEST_COOKIE          => true,
    ];

    $final_headers = [];

    // Iterate through all headers PHP intends to send.
    foreach ( headers_list() as $header ) {
        $header_lower = strtolower($header);
        // Check if this is a Set-Cookie header.
        if ( strpos( $header_lower, 'set-cookie:' ) === 0 ) {
            // Attempt to extract the cookie name.
            if ( preg_match( '/^Set-Cookie:\s*([^=]+)=/i', $header, $matches ) ) {
                $cookie_name = $matches[1];

                // Is this one of the WP auth cookies we need to enforce attributes on?
                if ( isset($auth_cookie_names[$cookie_name]) ) {
                    // Yes. Start modifying the original header string.
                    // 1. Remove any existing SameSite attribute (case-insensitive).
                    $modified_header = preg_replace( '/;\s*SameSite=(Lax|Strict|None)/i', '', $header );
                    // 2. Remove any existing Secure attribute (case-insensitive).
                    $modified_header = preg_replace( '/;\s*Secure/i', '', $modified_header );

                    // 3. Append the required attributes reliably.
                    // Note: We append '; Secure' separately from '; SameSite=None' just in case
                    // future WP versions or plugins add Secure but not SameSite, 
                    // though the preg_replace above should handle it.
                    $modified_header .= '; SameSite=None; Secure';

                    // Add the fully reconstructed/modified header to our list.
                    $final_headers[] = $modified_header;

                } else {
                    // It's a different cookie (e.g., session, third-party).
                    // Preserve it unmodified in our final list.
                    $final_headers[] = $header;
                }
            } else {
                // Malformed Set-Cookie header (no cookie name?). Preserve it as is.
                $final_headers[] = $header;
            }
        } else {
            // Not a Set-Cookie header (e.g., Content-Type). Preserve it as is.
            $final_headers[] = $header;
        }
    }

    // Remove *all* original Set-Cookie headers. This is crucial because PHP might
    // have queued multiple headers for the same cookie name, and we only want
    // our final modified versions to be sent.
    header_remove('Set-Cookie');

    // Re-add all the headers we collected (original non-cookie ones, modified auth cookies, original other cookies).
    // The 'false' parameter in header() is important; it prevents replacing existing
    // headers of the same name, allowing multiple Set-Cookie headers to be sent.
    foreach ( $final_headers as $final_header ) {
        header( $final_header, false );
    }

    // Return the original page content buffer, unmodified.
    // All header manipulation was done directly using header() functions.
    return $buffer;
}

/**
 * Start output buffering early to capture and modify cookie headers.
 * Hooks into 'plugins_loaded' at priority 0 to run as early as possible,
 * ensuring it catches headers set during WordPress initialization, but
 * after essential constants (like AUTH_COOKIE) are defined.
 *
 * @since TBD // Add version
 */
function asap_start_output_buffering_for_cookies() {
    // Avoid buffering during admin requests, CLI processes, or potentially AJAX 
    // where full page buffering might be unnecessary or cause issues.
    if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_ajax() ) {
        return;
    }
    // Start the output buffer, specifying our callback function.
    ob_start( 'asap_modify_cookie_headers_callback' );
}
// Hook early, but after plugins are loaded so constants like AUTH_COOKIE are defined.
add_action( 'plugins_loaded', 'asap_start_output_buffering_for_cookies', 0 );

/**
 * Filter the current user determination based on request origin.
 *
 * Prevents the auth cookie from being successfully used by requests
 * originating from non-whitelisted domains, even if SameSite=None
 * allows the cookie to be sent.
 *
 * @since TBD // Add appropriate version
 *
 * @param int|false $user_id The user ID determined by WordPress so far, or false.
 * @return int|false The user ID if the origin is allowed, or false if disallowed.
 */
function asap_filter_user_by_origin( $user_id ) {
    // If no user was initially determined by cookie, nothing to filter.
    if ( ! $user_id ) {
        return $user_id;
    }

    // Define the whitelist of allowed origins
    $whitelist = [
        'https://localhost:5173',      // Local SvelteKit Dev
        'https://app.asapdigest.com',  // Production SvelteKit App
        'https://asapdigest.local',    // Local WP Admin/Site
        'https://asapdigest.com',      // Production WP Admin/Site
    ];

    // Allow requests where ORIGIN is not set (e.g., same-origin, some server-side)
    if ( ! isset( $_SERVER['HTTP_ORIGIN'] ) || empty( $_SERVER['HTTP_ORIGIN'] ) ) {
        // Check if the request appears to be same-origin based on HOST vs REFERER as a fallback
        $http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $http_referer_host = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer_parts = wp_parse_url($_SERVER['HTTP_REFERER']);
            if ($referer_parts && isset($referer_parts['host'])) {
                $http_referer_host = $referer_parts['host'];
            }
        }

        // If host matches referer host, likely same-origin or direct access - allow.
        if (!empty($http_host) && $http_host === $http_referer_host) {
             return $user_id;
        }
        
        // If Origin isn't set, and it doesn't appear to be a simple same-origin request,
        // we lean towards caution and deny unless specifically whitelisted elsewhere.
        // However, for maximum compatibility with non-browser clients or direct access,
        // let's tentatively allow if Origin is not set. Revisit if issues arise.
        return $user_id; 
    }

    $origin = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
    $parsed_origin = wp_parse_url( $origin );

    // Ensure we have scheme and host after parsing
    if ( ! $parsed_origin || ! isset( $parsed_origin['scheme'] ) || ! isset( $parsed_origin['host'] ) ) {
        // Invalid Origin header format
        return false; // Deny requests with malformed Origin headers if a user was found
    }

    // Reconstruct the origin without path, query, etc. Add port if non-standard.
    $origin_base = $parsed_origin['scheme'] . '://' . $parsed_origin['host'];
    if ( isset( $parsed_origin['port'] ) ) {
         // Add port only if it's non-standard for the scheme
         if ( ( $parsed_origin['scheme'] === 'http' && $parsed_origin['port'] !== 80 ) || ( $parsed_origin['scheme'] === 'https' && $parsed_origin['port'] !== 443 ) ) {
              $origin_base .= ':' . $parsed_origin['port'];
         }
    }


    // Check against the whitelist
    if ( ! in_array( $origin_base, $whitelist, true ) ) {
        // Origin is not whitelisted, deny user recognition
        return false; // Tell WordPress no user is logged in for this request
    }

    // Origin is whitelisted, allow the originally determined user ID
    return $user_id;
}
// Add the filter with a priority higher than default (10) to run after basic checks.
add_filter( 'determine_current_user', 'asap_filter_user_by_origin', 20 );

/**
 * Generates and stores a single-use sync token upon successful WP login.
 *
 * @hook add_action('wp_login', 'asap_generate_sync_token_on_login', 10, 2)
 * @param string $user_login The user's login name.
 * @param WP_User $user WP_User object of the logged-in user.
 * @return void
 * @created 04.16.25 | 12:05 PM PDT
 */
function asap_generate_sync_token_on_login($user_login, $user) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ba_sync_tokens';
    $wp_user_id = $user->ID;

    // Ensure table exists (belt-and-suspenders, ideally created elsewhere)
    asap_ensure_database_tables();

    try {
        // Generate a secure token
        $token = bin2hex(random_bytes(32)); // 64 characters hex

        // Set expiry (e.g., 2 minutes from now)
        $expires_at = date('Y-m-d H:i:s', time() + 120); // 120 seconds = 2 minutes

        // Insert the new token
        $inserted = $wpdb->insert(
            $table_name,
            [
                'wp_user_id' => $wp_user_id,
                'token'      => $token,
                'expires_at' => $expires_at,
            ],
            [
                '%d', // wp_user_id
                '%s', // token
                '%s', // expires_at
            ]
        );

        if ($inserted === false) {
            error_log("ASAP Digest: Failed to insert sync token for user $wp_user_id. DB Error: " . $wpdb->last_error);
        } else {
             error_log("ASAP Digest: Generated sync token for user $wp_user_id."); // Use error_log for debugging server-side
        }

    } catch (Exception $e) {
        error_log("ASAP Digest: Exception generating sync token for user $wp_user_id: " . $e->getMessage());
    }
}
add_action('wp_login', 'asap_generate_sync_token_on_login', 10, 2);

/**
 * Deletes any active sync tokens for a user upon WP logout.
 *
 * @hook add_action('wp_logout', 'asap_delete_sync_token_on_logout', 10, 1)
 * @param int $user_id The ID of the user logging out.
 * @return void
 * @created 04.16.25 | 12:10 PM PDT
 */
function asap_delete_sync_token_on_logout($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ba_sync_tokens';

    if (empty($user_id)) {
        return; // Should not happen on wp_logout, but check anyway
    }

    // Delete all tokens for this user ID
    $deleted = $wpdb->delete(
        $table_name,
        ['wp_user_id' => $user_id],
        ['%d'] // Format for wp_user_id
    );

    if ($deleted === false) {
        error_log("ASAP Digest: Failed to delete sync tokens for user $user_id on logout. DB Error: " . $wpdb->last_error);
    } elseif ($deleted > 0) {
        error_log("ASAP Digest: Deleted $deleted sync token(s) for user $user_id on logout.");
    } else {
        // No tokens found to delete, which is normal
        error_log("ASAP Digest: No sync tokens found to delete for user $user_id on logout.");
    }
}
add_action('wp_logout', 'asap_delete_sync_token_on_logout', 10, 1);

/**
 * Injects the SK auth bridge script into the WP footer.
 * This script runs inside the hidden iframe loaded by the SK app.
 *
 * @hook add_action('wp_footer', 'asap_inject_sk_auth_bridge_script')
 * @since TBD // Add version when stable
 * @return void
 * @created 04.16.25 | 12:15 PM PDT
 */
function asap_inject_sk_auth_bridge_script() {
    error_log("ASAP Digest: asap_inject_sk_auth_bridge_script triggered."); // DEBUG Entry

    // Determine target SK origin based on environment
    $current_site_url = site_url();
    $sk_origin = '';
    if (strpos($current_site_url, 'asapdigest.local') !== false) {
        $sk_origin = 'https://localhost:5173'; // From PE - CTXT
    } elseif (strpos($current_site_url, 'asapdigest.com') !== false) {
        $sk_origin = 'https://app.asapdigest.com'; // Production SK App URL
    } else {
        error_log("ASAP Digest Bridge: Could not determine target SK origin. Current site: " . $current_site_url);
        return; 
    }
    error_log("ASAP Digest Bridge: Target SK origin: " . $sk_origin); // DEBUG Origin

    // Initialize token variable
    $sync_token = null;
    $is_wp_logged_in = is_user_logged_in(); // Check login status once
    error_log("ASAP Digest Bridge: WP Logged In Status: " . ($is_wp_logged_in ? 'true' : 'false')); // DEBUG Login Status

    // Check if user is logged into WordPress
    if ( $is_wp_logged_in ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ba_sync_tokens';
        $user_id = get_current_user_id();
        error_log("ASAP Digest Bridge: User ID: " . $user_id); // DEBUG User ID

        // Check if the table exists first to prevent errors
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        error_log("ASAP Digest Bridge: Token table ($table_name) exists: " . ($table_exists ? 'true' : 'false')); // DEBUG Table Check

        if ($table_exists) {
            $sync_token = $wpdb->get_var( $wpdb->prepare(
                "SELECT token FROM $table_name WHERE wp_user_id = %d AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1",
                $user_id
            ) );
            error_log("ASAP Digest Bridge: Token query result: " . ($sync_token ? 'Token Found' : 'No Token Found/Expired')); // DEBUG Token Query
        } else {
            error_log("ASAP Digest Bridge: Sync token table ($table_name) not found in hook.");
        }
    }

    // Output the JavaScript for postMessage communication
    ?>
    <script id="sk-auth-bridge-script">
        (function() {
            const targetOrigin = '<?php echo esc_js($sk_origin); ?>';
            const syncToken = <?php echo $sync_token ? "'" . esc_js($sync_token) . "'" : 'null'; ?>;
            const isLoggedIn = <?php echo $is_wp_logged_in ? 'true' : 'false'; ?>;

            // Minimal logging in production environments
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            console.log('[WP Bridge Script] Running. Target:', targetOrigin, 'LoggedIn:', isLoggedIn, 'Token Found:', !!syncToken);
            <?php endif; ?>

            // Only try to postMessage if potentially in an iframe from the correct target
            if (window.parent && window.parent !== window.self) {
                 // --- ADDED DEBUG LOG ---
                 <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                 console.log('[WP Bridge Script] Attempting postMessage...');
                 <?php endif; ?>
                 // --- END ADDED DEBUG LOG ---
                if (syncToken) {
                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    console.log('[WP Bridge Script] Sending wpAuthToken to parent');
                    <?php endif; ?>
                    window.parent.postMessage({ type: 'wpAuthToken', token: syncToken }, targetOrigin);
                } else {
                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    console.log('[WP Bridge Script] Sending wpAuthStatus to parent');
                    <?php endif; ?>
                    window.parent.postMessage({ type: 'wpAuthStatus', loggedIn: isLoggedIn, tokenFound: false }, targetOrigin);
                }
            } else {
                 <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                 // console.log('[WP Bridge Script] Not running in a child iframe or parent is inaccessible.');
                 <?php endif; ?>
            }
        })();
    </script>
    <?php
    error_log("ASAP Digest Bridge: Script output finished."); // DEBUG End of Function
}
// Add the action hook to run in the footer for both frontend and admin
add_action('wp_footer', 'asap_inject_sk_auth_bridge_script');
add_action('admin_footer', 'asap_inject_sk_auth_bridge_script'); // ADDED FOR ADMIN AREA

/**
 * @description Register ASAP Digest Central Command menu and submenus
 * @return void
 * @example
 * // Called during admin_menu action
 * asap_add_central_command_menu();
 * @created 03.30.25 | 04:35 PM PDT
 */
function asap_add_central_command_menu() {
    // Add the main menu item
    add_menu_page(
        '⚡️ Central Command',  // Page title
        '⚡️ Central Command',  // Menu title
        'manage_options',       // Capability
        'asap-central-command', // Menu slug
        'asap_render_central_command_dashboard', // Callback function
        'dashicons-superhero',  // Icon
        3                       // Position after Dashboard and Posts
    );

    // Add submenus
    add_submenu_page(
        'asap-central-command',
        'Digest Management',
        'Digests',
        'manage_options',
        'asap-digest-management',
        'asap_render_digest_management'
    );

    add_submenu_page(
        'asap-central-command',
        'User Statistics',
        'User Stats',
        'manage_options',
        'asap-user-stats',
        'asap_render_user_stats'
    );

    // Re-add Auth Settings submenu if needed, ensure callback asap_render_better_auth_settings exists
    add_submenu_page(
        'asap-central-command',
        'Better Auth Settings',
        'Auth Settings',
        'manage_options',
        'asap-auth-settings',
        'asap_render_better_auth_settings' // Callback exists in better-auth-config.php
    );

    add_submenu_page(
        'asap-central-command',
        'ASAP Settings',
        'Settings',
        'manage_options',
        'asap-settings',
        'asap_render_settings'
    );
}