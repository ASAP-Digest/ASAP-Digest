<?php
/**
 * Plugin Name:     ASAP Digest Core
 * Plugin URI:      https://asapdigest.com/
 * Description:     Core functionality for ASAPDigest.com
 * Author:          ASAP Digest
 * Author URI:      https://philoveracity.com/
 * Text Domain:     adc
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         ASAPDigest_Core
 */


 global $wpdb;

 // Create custom tables on plugin activation
 function asap_create_tables() {
   global $wpdb;
   $charset_collate = $wpdb->get_charset_collate();


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
     FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
   ) $charset_collate;";
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($digests_sql);


   // Migration: Move existing digests from options table
   $option_prefix = 'asap_digest_';
   $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '$option_prefix%'", ARRAY_A);
   foreach ($options as $option) {
     $digest_id = str_replace($option_prefix, '', $option['option_name']);
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
     UNIQUE KEY unique_endpoint (endpoint),
     INDEX idx_user_id (user_id)
   ) $charset_collate;";
   dbDelta($notifications_sql);
 }
 register_activation_hook(__FILE__, 'asap_create_tables');


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
   $wpdb->query("DELETE FROM $digests_table WHERE created_at < '$cutoff_date'");
   $wpdb->query("DELETE FROM $notifications_table WHERE created_at < '$cutoff_date'");
 }
 add_action('asap_cleanup_data', 'asap_cleanup_data');


 function create_asap_cpts() {
   $args = [
     'public' => true,
     'show_in_graphql' => true,
     'graphql_single_name' => 'Post',
     'graphql_plural_name' => 'Posts',
     'supports' => ['title', 'editor', 'thumbnail'],
     'has_archive' => true,
     'menu_icon' => 'dashicons-admin-post',
   ];
   register_post_type('article', array_merge($args, ['label' => '⚡️ - Articles']));
   register_post_type('podcast', array_merge($args, ['label' => '⚡️ - Podcasts']));
   register_post_type('keyterm', array_merge($args, ['label' => '⚡️ - Key Terms']));
   register_post_type('financial', array_merge($args, ['label' => '⚡️ - Financial Bites']));
   register_post_type('xpost', array_merge($args, ['label' => '⚡️ - X Posts']));
   register_post_type('reddit', array_merge($args, ['label' => '⚡️ - Reddit Buzz']));
   register_post_type('event', array_merge($args, ['label' => '⚡️ - Events']));
   register_post_type('polymarket', array_merge($args, ['label' => '⚡️ - Polymarket']));
 }
 add_action('init', 'create_asap_cpts');


 // Register custom REST API endpoint for digest generation
 function asap_register_rest_routes() {
   register_rest_route('asap/v1', '/digest', [
     'methods' => 'GET',
     'callback' => 'asap_generate_digest',
     'permission_callback' => function () {
       return current_user_can('read');
     },
   ]);
 }
 add_action('rest_api_init', 'asap_register_rest_routes');


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
   $wpdb->insert(
     $digests_table,
     ['content' => $digest, 'share_link' => get_rest_url(null, "asap/v1/digest/{$digest_id}")],
     ['%s', '%s']
   );


   // Trigger podcast generation
   $response = wp_remote_post('https://asapdigest.com/api/generate-podcast' || 'https://asapdigest.local/api/generate-podcast' || 'http://asapdigest.local/api/generate-podcast' || 'http://localhost:5173/api/generate-podcast', [
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


   foreach ($subscriptions as $sub) {
     $subscription = [
       'endpoint' => $sub['endpoint'],
       'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
     ];
     $response = wp_remote_post('https://asapdigest.com/api/send-push' || 'https://asapdigest.local/api/send-push' || 'http://asapdigest.local/api/send-push' || 'http://localhost:5173/api/send-push', [
       'body' => json_encode(['subscription' => $subscription, 'payload' => $payload]),
       'headers' => ['Content-Type' => 'application/json'],
     ]);
     if (is_wp_error($response)) {
       error_log('Push notification error: ' . $response->get_error_message());
     }
   }


   return rest_ensure_response(['success' => true]);
 }


 // Add CORS headers for SvelteKit frontend
 function asap_add_cors_headers() {
   header('Access-Control-Allow-Origin: https://asapdigest.com') || header('Access-Control-Allow-Origin: https://asapdigest.local') || header('Access-Control-Allow-Origin: http://asapdigest.local') || header('Access-Control-Allow-Origin: http://localhost:5173');
   header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
   header('Access-Control-Allow-Headers: Authorization, Content-Type');
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

// Cron System for Digest Delivery
add_filter('cron_schedules', function($schedules) {
  $schedules['five_min_sms'] = [
    'interval' => 300,
    'display' => __('Every 5 Minutes for SMS')
  ];
  return $schedules;
});
