﻿# ASAP DIGEST BUILD 

<!-- 
IMPORTANT REMINDER FOR LLM ASSISTANTS:
This roadmap file has been split into smaller, more manageable files for LLM processing.
PLEASE USE THE FOLLOWING FILES INSTEAD OF THIS ONE:
- ASAP_DIGEST_ROADMAP_LLM_1.md - Project overview and initial setup
- ASAP_DIGEST_ROADMAP_LLM_2.md - Frontend dependencies and core infrastructure
- ASAP_DIGEST_ROADMAP_LLM_3.md - Widget implementations (Podcast, Key Term, Financial)
- ASAP_DIGEST_ROADMAP_LLM_4.md - Widget implementations (X Post, Reddit, Event, Polymarket)
- ASAP_DIGEST_ROADMAP_LLM_5.md - Main dashboard, profile settings, plans page and admin area
- ASAP_DIGEST_ROADMAP_LLM_6.md - Additional features (digest, push notifications, feedback)
- ASAP_DIGEST_ROADMAP_LLM_7.md - Performance metrics, multi-device sync, authentication and testing

These files contain the same information but in a format that is easier for LLMs to process.
Always check task status in ROADMAP_TASKS.md before beginning new work.
-->

## ASAP Digest - Devour Insights at AI Speed

## General Summary

ASAP Digest is an innovative digital platform engineered to deliver personalized, curated insights at lightning speed—empowering users to literally "devour insights at AI speed." The comprehensive development roadmap outlines the creation of a robust, hybrid solution that combines a SvelteKit-based frontend with a WordPress headless CMS backend. This architecture supports dynamic, rapidly updating content while leveraging state-of-the-art technologies like GraphQL, REST APIs, and advanced AI summarization powered by Hugging Face Transformers.

Key features include a suite of interactive, audio-enhanced widgets (for articles, podcasts, financial bites, X posts, Reddit buzz, and more) that integrate text-to-speech and rich multimedia experiences. A standout component is the daily podcast generation—an AI-driven, multi-host conversation between virtual hosts Alex and Jamie that transforms static digests into engaging, conversational audio experiences. Additional innovative capabilities, such as the unified "Digest Time Machine," allow users to explore their entire history of digests with contextual data including sentiment analysis, mood tracking, and personal life moments, and even schedule future revisits with push notifications.

Overall, the project's roadmap and supporting documents emphasize speed, precision, and personalization. With seamless PWA functionality, secure API integrations (including AWS SES/S3, Stripe, and Twilio), and an architecture designed for scalability and high performance, ASAP Digest is set to revolutionize information consumption—enabling users to quickly and effortlessly consume, reflect on, and share their daily insights in a truly immersive and agile manner.

## Condensed Description

ASAP Digest uses AI to rapidly deliver personalized insights via a SvelteKit-WordPress hybrid. Its interactive widgets, AI-driven daily podcast, and "Digest Time Machine" let users quickly consume and revisit curated content.



## Build & Development Plan

Below is the complete, updated development plan for the ASAP Digest project as of March 09, 2025, incorporating all tasks, subtasks, enhancements, and improvements discussed. This includes the original structure, PWA enhancements, user experience features, custom MariaDB tables, Web Push implementation, text-to-speech (TTS) improvements, daily podcast generation, Lucide Svelte icon integration, and other additions like feedback mechanisms, progress tracking, and content filtering. The plan is presented in Markdown format within a codeblock, ensuring clarity and structure for implementation.


```markdown
# Development Plan for ASAP Digest

## Task 1: Configure Core Infrastructure for ASAP Digest

### Subtask 1.1: Set Up SvelteKit Project
- **Action**: Initialize a new SvelteKit project to serve as the frontend for ASAP Digest.
- **Steps**:
  1. Run the following command to create a new SvelteKit project:
     ```bash
     pnpm create svelte@latest asapdigest
     ```
     - Select: SvelteKit with TypeScript: No, ESLint: Yes, Prettier: Yes, Playwright: Yes.
  2. Navigate to the project directory:
     ```bash
     cd asapdigest
     ```
  3. Install dependencies:
     ```bash
     pnpm install
     ```
  4. Start the development server:
     ```bash
     pnpm run dev
     ```
     - Verify the default app at `https://localhost:5173`.
- **Purpose**: Establishes the foundation for the frontend application using SvelteKit with linting and testing tools.


### Subtask 1.2: Configure WordPress as a Headless CMS
- **Action**: Set up a WordPress instance with necessary plugins to act as a headless CMS.
- **Steps**:
  1. Install a local WordPress instance using LocalWP.
     - Ensure the database uses MariaDB.
  2. Log into the WordPress admin dashboard (e.g., `https://asapdigest.local/wp-admin`).
  3. Navigate to "Settings" > "Permalinks" and set to "Post name" for clean URLs.
- **Purpose**: Configures WordPress as a headless CMS with MariaDB, preparing it for GraphQL and REST API usage.


### Subtask 1.3: Define Custom Post Types and Advanced Custom Fields in WordPress
- **Action**: Create Custom Post Types (CPTs) and Advanced Custom Fields (ACF) in WordPress, and set up custom MariaDB tables for digests and notifications.
- **Steps**:
  1. Log into WordPress and navigate to "Plugins".
  2. Install and activate "Advanced Custom Fields Pro".
  3. Create a new file `wp-content/plugins/asapdigest-core/asapdigest-core.php` with the following content:
     ```php
     <?php
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
       register_post_type('article', array_merge($args, ['label' => 'Articles']));
       register_post_type('podcast', array_merge($args, ['label' => 'Podcasts']));
       register_post_type('keyterm', array_merge($args, ['label' => 'Key Terms']));
       register_post_type('financial', array_merge($args, ['label' => 'Financial Bites']));
       register_post_type('xpost', array_merge($args, ['label' => 'X Posts']));
       register_post_type('reddit', array_merge($args, ['label' => 'Reddit Buzz']));
       register_post_type('event', array_merge($args, ['label' => 'Events']));
       register_post_type('polymarket', array_merge($args, ['label' => 'Polymarket']));
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
       $response = wp_remote_post('https://asapdigest.com/api/generate-podcast' || 'https://asapdigest.local/api/generate-podcast' || 'http://asapdigest.local/api/generate-podcast' || 'https://localhost:5173/api/generate-podcast', [
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
         $response = wp_remote_post('https://asapdigest.com/api/send-push' || 'https://asapdigest.local/api/send-push' || 'http://asapdigest.local/api/send-push' || 'https://localhost:5173/api/send-push', [
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
       header('Access-Control-Allow-Origin: https://asapdigest.com') || header('Access-Control-Allow-Origin: https://asapdigest.local') || header('Access-Control-Allow-Origin: http://asapdigest.local') || header('Access-Control-Allow-Origin: https://localhost:5173');
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
   ```

  4. In the WordPress admin, navigate to "Custom Fields" > "Add New" to create field groups:
     - **Article**: `summary` (Text), `source` (Text), `timestamp` (Date), `image` (Image).
     - **Podcast**: `summary` (Text), `episode` (Number), `duration` (Number).
     - **Key Term**: `mentions` (Repeater with subfield `mention` as Text).
     - **Financial**: `summary` (Text), `ticker` (Text), `change` (Number).
     - **X Post**: `text` (Text), `handle` (Text), `likes` (Number).
     - **Reddit**: `summary` (Text), `subreddit` (Text), `upvotes` (Number).
     - **Event**: `description` (Text), `date` (Date), `location` (Text).
     - **Polymarket**: `changes` (Repeater with subfield `change` as Text).
     - Set each field group to be available for its respective CPT.
  5. Install and activate "WP GraphQL" and "WP GraphQL ACF" plugins via the WordPress admin.
- **Purpose**: Defines CPTs, sets up custom MariaDB tables for digests and notifications, schedules data cleanup, exposes REST endpoints for digest and podcast management, and configures CORS for SvelteKit.


### Subtask 1.4: Install and Configure Frontend Dependencies
- **Action**: Install all required libraries, including Lucide Svelte for icons.
- **Steps**:
  1. Run the following command to install dependencies:
     ```bash
     pnpm i install @better-auth/svelte @huggingface/transformers @urql/svelte graphql svelte-dnd-action svelte-chartjs chart.js wavesurfer.js @stripe/stripe-js @aws-sdk/client-ses @aws-sdk/client-s3 twilio @shadcn/svelte workbox-window web-push @google-analytics/ga4 lucide-svelte
     ```
  2. Initialize ShadCN in the project:
     ```bash
     npx shadcn-svelte@latest init
     ```
     - Select default options.
  3. Create or update `src/app.css`:
     ```css
     @import 'shadcn-svelte/styles.css';
     body {
       font-family: 'Times New Roman', Times, serif;
       background-color: #f5f5f5;
       margin: 0;
       padding: 0;
     }
     [data-lucide] {
       stroke: currentColor;
     }
     ```
  4. Create `public/manifest.json`:
     **Status Update (2025/03/22)**: Web App Manifest has been fully configured with all required fields, PWA icon assets (placeholder versions), and theme colors. Task completed.
     ```json
     {
       "name": "ASAP Digest",
       "short_name": "ASAP Digest",
       "description": "Your daily digest of news, podcasts, and markets",
       "start_url": "/",
       "display": "standalone",
       "background_color": "#f5f5f5",
       "theme_color": "#00ffff",
       "icons": [
         {
           "src": "/icons/icon-192x192.png",
           "sizes": "192x192",
           "type": "image/png"
         },
         {
           "src": "/icons/icon-512x512.png",
           "sizes": "512x512",
           "type": "image/png"
         }
       ]
     }
     ```
     - Add placeholder icon files (`public/icons/icon-192x192.png`, `public/icons/icon-512x512.png`).
  5. Update `src/app.html`:
     ```html
     <!DOCTYPE html>
     <html lang="en">
       <head>
         <meta charset="utf-8" />
         <meta name="viewport" content="width=device-width, initial-scale=1" />
         <link rel="manifest" href="/manifest.json" />
         <meta name="theme-color" content="#00ffff" />
         <title>ASAP Digest</title>
         <!-- Google Analytics -->
         <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
         <script>
           window.dataLayer = window.dataLayer || [];
           function gtag(){dataLayer.push(arguments);}
           gtag('js', new Date());
           gtag('config', 'G-XXXXXXXXXX');
         </script>
       </head>
       <body>%sveltekit.body%</body>
     </html>
     ```
     - Replace `G-XXXXXXXXXX` with your Google Analytics Measurement ID.
- **Purpose**: Prepares the frontend environment with PWA dependencies, Lucide Svelte icons, and Google Analytics.


### Subtask 1.5: Configure Service Worker for PWA
- **Action**: Implement a service worker to enable offline mode, caching, and push notifications.
- **Status Update (2025/03/22)**: Service worker implementation continuing with caching strategies and offline functionality. Installation prompt implementation is also in progress.
- **Steps**:
  1. Install Workbox CLI globally:
     ```bash
     pnpm install -g workbox-cli
     ```
  2. Create `src/service-worker.js`:
     ```javascript
     import { precacheAndRoute } from 'workbox-precaching';
     import { registerRoute } from 'workbox-routing';
     import { NetworkFirst, CacheFirst } from 'workbox-strategies';
     import { setCacheNameDetails } from 'workbox-core';
     import { BackgroundSyncPlugin } from 'workbox-background-sync';


     setCacheNameDetails({
       prefix: 'asapdigest',
       precache: 'precache',
       runtime: 'runtime',
     });


     precacheAndRoute(self.__WB_MANIFEST);


     // Cache API responses (e.g., GraphQL queries)
     registerRoute(
       ({ url }) => url.pathname.startsWith('/graphql'),
       new NetworkFirst({
         cacheName: 'api-cache',
         plugins: [
           {
             cacheWillUpdate: async ({ request, response }) => {
               return response && response.status === 200 ? response : null;
             },
           },
         ],
       })
     );


     // Cache static assets
     registerRoute(
       ({ request }) => request.destination === 'image' || request.destination === 'script' || request.destination === 'style',
       new CacheFirst({
         cacheName: 'static-assets',
       })
     );


     // Background sync for digest updates
     const bgSyncPlugin = new BackgroundSyncPlugin('digest-queue', {
       maxRetentionTime: 24 * 60,
     });


     registerRoute(
       ({ url }) => url.pathname === '/wp-json/asap/v1/digest',
       new NetworkFirst({
         cacheName: 'digest-cache',
         plugins: [bgSyncPlugin],
       })
     );


     // Offline fallback
     self.addEventListener('install', (event) => {
       event.waitUntil(
         caches.open('offline-fallback').then((cache) => {
           return cache.addAll(['/offline.html']);
         })
       );
     });


     self.addEventListener('fetch', (event) => {
       if (!navigator.onLine) {
         event.respondWith(
           caches.match('/offline.html').then((response) => response || new Response('Offline', { status: 503 }))
         );
       }
     });


     // Push notification handling
     self.addEventListener('push', (event) => {
       // Push API event handling
       const data = event.data?.json();
       event.waitUntil(
         self.registration.showNotification(data.title, {
           body: data.body,
           icon: '/icons/icon-192x192.png'
         })
       );
     });


     // Sync authentication on reconnection
     self.addEventListener('online', () => {
       if ('authClient' in self) {
         authClient.refreshToken().catch(console.error);
       }
     });
     ```
  3. Create `public/offline.html`:
     ```html
     <!DOCTYPE html>
     <html lang="en">
       <head>
         <meta charset="utf-8" />
         <meta name="viewport" content="width=device-width, initial-scale=1" />
         <title>Offline</title>
       </head>
       <body>
         <h1>You are offline</h1>
         <p>Some features may be unavailable. Please check your connection.</p>
       </body>
     </html>
     ```
  4. Update `svelte.config.js`:
     ```javascript
     import adapter from '@sveltejs/adapter-auto';
     import { vitePreprocess } from '@sveltejs/kit/vite';


     /** @type {import('@sveltejs/kit').Config} */
     const config = {
       kit: {
         adapter: adapter(),
         serviceWorker: {
           src: 'src/service-worker.js',
           output: 'service-worker',
           register: true,
         },
       },
       preprocess: vitePreprocess(),
     };


     export default config;
     ```
  5. Generate the precache manifest:
     ```bash
     workbox generateSW src/service-worker.js --globDirectory public --globPatterns '**.{html,js,css,png,jpg,jpeg,svg}' --swDest public/service-worker.js
     ```
- **Purpose**: Configures a service worker for offline mode, caching, push notifications, and background sync.


### Subtask 1.6: Set Up Authentication with Better Auth
- **Action**: Integrate Better Auth for user authentication.
- **Steps**:
  1. Create `src/lib/auth.js`:
     ```javascript
     import { BetterAuth } from '@better-auth/svelte';
     export const authClient = new BetterAuth({
       baseUrl: 'https://your-auth-server.com',
       providers: [
         { id: 'google', name: 'Google', type: 'oauth' },
       ],
     });
     ```
     - Replace `baseUrl` with your Better Auth server URL.
  2. Create `src/lib/AuthButtons.svelte`:
     ```svelte
     <script>
       import { authClient } from '$lib/auth';
       import { Button } from '$lib/components/ui';
       const { data: session, signIn, signOut } = authClient.useSession();
     </script>
     {#if $session}
       <Button on:click={() => signOut()}>Logout</Button>
     {:else}
       <Button on:click={() => signIn('google')}>Login with Google</Button>
     {/if}
     ```
- **Purpose**: Integrates Better Auth for authentication with Google OAuth, session management, and route protection.


### Subtask 1.7: Set Up GraphQL Client for WordPress Data
- **Action**: Configure a GraphQL client to fetch data from WordPress.
- **Steps**:
  1. Create `src/lib/graphql-client.js`:
     ```javascript
     import { createClient } from '@urql/svelte';
     export const client = createClient({
       url: 'https://your-wordpress-site.com/graphql',
       fetchOptions: () => {
         return {
           headers: {
             authorization: `Bearer ${localStorage.getItem('auth-token') || ''}`,
           },
         };
       },
     });
     ```
     - Replace `url` with your WordPress GraphQL endpoint.
- **Purpose**: Establishes a GraphQL client to query WordPress data with authentication support.


### Subtask 1.8: Set Up AI Summarization
- **Action**: Configure Hugging Face Transformers.js for AI summarization.
- **Steps**:
  1. Create `src/lib/ai-summarizer.js`:
     ```javascript
     import { pipeline } from '@huggingface/transformers';


     let summarizer = null;


     export async function initializeSummarizer() {
       if (!summarizer) {
         summarizer = await pipeline('summarization', 'distilbart-cnn-12-6');
       }
     }


     export async function summarizeText(text) {
       if (!summarizer) {
         await initializeSummarizer();
       }
       try {
         const result = await summarizer(text, { max_length: 100, min_length: 30 });
         return result[0].summary_text;
       } catch (error) {
         console.error('Summarization error:', error);
         return 'Summary unavailable.';
       }
     }
     ```
- **Purpose**: Sets up AI summarization for concise widget content.


## Task 2: Implement Individual Widgets in SvelteKit


### Subtask 2.1: Implement Article Widget
- **Action**: Create a Svelte component for the Article widget with Lucide Svelte icons, AI summarization, and TTS.
- **Steps**:
  1. Create `src/lib/ArticleWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { summarizeText } from '$lib/ai-summarizer';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Newspaper, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let summary = '';
       let source = '';
       let timestamp = '';
       let imageUrl = '';
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`article_${id}`);
           if (cachedData) {
             const { title: cachedTitle, summary: cachedSummary, source: cachedSource, timestamp: cachedTimestamp, imageUrl: cachedImageUrl } = JSON.parse(cachedData);
             title = cachedTitle;
             summary = cachedSummary;
             source = cachedSource;
             timestamp = cachedTimestamp;
             imageUrl = cachedImageUrl;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfArticle { content source timestamp image }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         const content = post.acfArticle.content || '';
         summary = await summarizeText(content);
         source = post.acfArticle.source || 'Unknown';
         timestamp = post.acfArticle.timestamp || new Date().toISOString().split('T')[0];
         imageUrl = post.acfArticle.image?.sourceUrl || '/placeholder.jpg';
         localStorage.setItem(`article_${id}`, JSON.stringify({ title, summary, source, timestamp, imageUrl }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
           dispatch('playAudio', { text: summary });
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
           dispatch('stopAudio');
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, summary, expanded });
       }


       async function shareArticle() {
         const shareData = {
           title: title,
           text: summary,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Article', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${summary} - ${window.location.href}`);
             alert('Article link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Article Summary">
         <CardHeader>
           <div class="flex items-center">
             <CardTitle class="text-xl font-serif flex items-center">
               <Newspaper class="mr-2 w-6 h-6" />{title}
             </CardTitle>
             <img src={imageUrl} alt="Article Image" class="w-16 h-16 object-cover rounded ml-4 hover:scale-110 transition-transform duration-200 ease-in-out" />
           </div>
           <p class="text-sm text-gray-600">{source}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{summary || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{timestamp}</span>
             <div class="space-x-2">
               <span class="text-sm font-bold {summary?.includes('positive') ? 'text-green-500' : summary?.includes('negative') ? 'text-red-500' : 'text-gray-500'}">{summary?.includes('positive') ? '[+]' : summary?.includes('negative') ? '[-]' : '[~]'}</span>
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse article' : 'Expand article'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareArticle} aria-label="Share article">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={summary} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{summary}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">Read Full Article</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Article widget with ShadCN styling, AI summarization, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.2: Implement Podcast Widget
- **Action**: Create a Svelte component for the Podcast widget with Lucide Svelte icons and TTS.
- **Status Update (2025/03/22)**: Component skeleton completed, audio playback implementation in progress with play/pause functionality, offline state detection, expandable/collapsible view for detailed information, sharing functionality, and audio progress tracking/visualization in development.
- **Steps**:
  1. Create `src/lib/PodcastWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { summarizeText } from '$lib/ai-summarizer';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Mic, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let summary = '';
       let episode = 0;
       let duration = 0;
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`podcast_${id}`);
           if (cachedData) {
             const { title: cachedTitle, summary: cachedSummary, episode: cachedEpisode, duration: cachedDuration } = JSON.parse(cachedData);
             title = cachedTitle;
             summary = cachedSummary;
             episode = cachedEpisode;
             duration = cachedDuration;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfPodcast { summary episode duration }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         const content = post.acfPodcast.summary || '';
         summary = await summarizeText(content);
         episode = post.acfPodcast.episode || 0;
         duration = post.acfPodcast.duration || 0;
         localStorage.setItem(`podcast_${id}`, JSON.stringify({ title, summary, episode, duration }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
           dispatch('playAudio', { text: summary });
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
           dispatch('stopAudio');
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, summary, expanded });
       }


       async function sharePodcast() {
         const shareData = {
           title: title,
           text: summary,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Podcast', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${summary} - ${window.location.href}`);
             alert('Podcast link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Podcast Summary">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <Mic class="mr-2 w-6 h-6" />{title}
           </CardTitle>
           <p class="text-sm text-gray-600">Episode {episode}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{summary || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{duration} mins</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse podcast' : 'Expand podcast'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={sharePodcast} aria-label="Share podcast">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={summary} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{summary}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">Listen to Podcast</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Podcast widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.3: Implement Key Term Widget
- **Action**: Create a Svelte component for the Key Term widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/KeyTermWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Key, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let mentions = [];
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`keyterm_${id}`);
           if (cachedData) {
             const { title: cachedTitle, mentions: cachedMentions } = JSON.parse(cachedData);
             title = cachedTitle;
             mentions = cachedMentions;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfKeyterm { mentions }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         mentions = post.acfKeyterm.mentions || [];
         localStorage.setItem(`keyterm_${id}`, JSON.stringify({ title, mentions }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, expanded });
       }


       async function shareKeyTerm() {
         const shareData = {
           title: title,
           text: `Mentions: ${mentions.join(', ')}`,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'KeyTerm', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${shareData.text} - ${shareData.url}`);
             alert('Key Term link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Key Term Mentions">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <Key class="mr-2 w-6 h-6" />{title}
           </CardTitle>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">Mentions: {mentions.join(', ') || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{mentions.length} mentions</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse key term' : 'Expand key term'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareKeyTerm} aria-label="Share key term">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={`Mentions: ${mentions.join(', ')}`} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <ul class="text-sm list-disc pl-4">
                 {#each mentions as mention}
                   <li>{mention}</li>
                 {/each}
               </ul>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Key Term widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.4: Implement Financial Widget
- **Action**: Create a Svelte component for the Financial widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/FinancialWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { summarizeText } from '$lib/ai-summarizer';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { DollarSign, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let summary = '';
       let ticker = '';
       let change = 0;
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`financial_${id}`);
           if (cachedData) {
             const { title: cachedTitle, summary: cachedSummary, ticker: cachedTicker, change: cachedChange } = JSON.parse(cachedData);
             title = cachedTitle;
             summary = cachedSummary;
             ticker = cachedTicker;
             change = cachedChange;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfFinancial { summary ticker change }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         const content = post.acfFinancial.summary || '';
         summary = await summarizeText(content);
         ticker = post.acfFinancial.ticker || 'Unknown';
         change = post.acfFinancial.change || 0;
         localStorage.setItem(`financial_${id}`, JSON.stringify({ title, summary, ticker, change }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
           dispatch('playAudio', { text: summary });
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
           dispatch('stopAudio');
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, summary, expanded });
       }


       async function shareFinancial() {
         const shareData = {
           title: title,
           text: summary,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Financial', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${summary} - ${window.location.href}`);
             alert('Financial Bite link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Financial Summary">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <DollarSign class="mr-2 w-6 h-6" />{title}
           </CardTitle>
           <p class="text-sm text-gray-600">{ticker}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{summary || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm {change >= 0 ? 'text-green-500' : 'text-red-500'}">{change >= 0 ? '+' : ''}{change}%</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse financial bite' : 'Expand financial bite'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareFinancial} aria-label="Share financial bite">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={summary} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{summary}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">View Details</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Financial widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.5: Implement X Post Widget
- **Action**: Create a Svelte component for the X Post widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/XPostWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Twitter, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let text = '';
       let handle = '';
       let likes = 0;
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`xpost_${id}`);
           if (cachedData) {
             const { title: cachedTitle, text: cachedText, handle: cachedHandle, likes: cachedLikes } = JSON.parse(cachedData);
             title = cachedTitle;
             text = cachedText;
             handle = cachedHandle;
             likes = cachedLikes;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfXpost { text handle likes }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         text = post.acfXpost.text || '';
         handle = post.acfXpost.handle || 'Unknown';
         likes = post.acfXpost.likes || 0;
         localStorage.setItem(`xpost_${id}`, JSON.stringify({ title, text, handle, likes }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, expanded });
       }


       async function shareXPost() {
         const shareData = {
           title: title,
           text: text,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'XPost', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${text} - ${window.location.href}`);
             alert('X Post link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="X Post">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <Twitter class="mr-2 w-6 h-6" />{title}
           </CardTitle>
           <p class="text-sm text-gray-600">@{handle}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{text || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{likes} likes</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse X post' : 'Expand X post'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareXPost} aria-label="Share X post">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={text} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{text}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">View on X</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the X Post widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.6: Implement Reddit Widget
- **Action**: Create a Svelte component for the Reddit widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/RedditWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Megaphone, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let summary = '';
       let subreddit = '';
       let upvotes = 0;
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`reddit_${id}`);
           if (cachedData) {
             const { title: cachedTitle, summary: cachedSummary, subreddit: cachedSubreddit, upvotes: cachedUpvotes } = JSON.parse(cachedData);
             title = cachedTitle;
             summary = cachedSummary;
             subreddit = cachedSubreddit;
             upvotes = cachedUpvotes;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfReddit { summary subreddit upvotes }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         summary = post.acfReddit.summary || '';
         subreddit = post.acfReddit.subreddit || 'Unknown';
         upvotes = post.acfReddit.upvotes || 0;
         localStorage.setItem(`reddit_${id}`, JSON.stringify({ title, summary, subreddit, upvotes }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, summary, expanded });
       }


       async function shareReddit() {
         const shareData = {
           title: title,
           text: summary,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Reddit', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${summary} - ${window.location.href}`);
             alert('Reddit post link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Reddit Summary">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <Megaphone class="mr-2 w-6 h-6" />{title}
           </CardTitle>
           <p class="text-sm text-gray-600">r/{subreddit}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{summary || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{upvotes} upvotes</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse Reddit post' : 'Expand Reddit post'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareReddit} aria-label="Share Reddit post">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={summary} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{summary}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">View on Reddit</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Reddit widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.7: Implement Event Widget
- **Action**: Create a Svelte component for the Event widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/EventWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { Calendar, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let description = '';
       let date = '';
       let location = '';
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`event_${id}`);
           if (cachedData) {
             const { title: cachedTitle, description: cachedDescription, date: cachedDate, location: cachedLocation } = JSON.parse(cachedData);
             title = cachedTitle;
             description = cachedDescription;
             date = cachedDate;
             location = cachedLocation;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfEvent { description date location }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         description = post.acfEvent.description || '';
         date = post.acfEvent.date || new Date().toISOString().split('T')[0];
         location = post.acfEvent.location || 'Unknown';
         localStorage.setItem(`event_${id}`, JSON.stringify({ title, description, date, location }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, description, expanded });
       }


       async function shareEvent() {
         const shareData = {
           title: title,
           text: description,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Event', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${description} - ${window.location.href}`);
             alert('Event link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Event Details">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <Calendar class="mr-2 w-6 h-6" />{title}
           </CardTitle>
           <p class="text-sm text-gray-600">{date}</p>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">{description || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{location}</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse event' : 'Expand event'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={shareEvent} aria-label="Share event">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={description} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <p class="text-sm">{description}</p>
               <a href="#" class="text-cyan-400 hover:text-cyan-600">Learn More</a>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Event widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.8: Implement Polymarket Widget
- **Action**: Create a Svelte component for the Polymarket widget with Lucide Svelte icons and TTS.
- **Steps**:
  1. Create `src/lib/PolymarketWidget.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import { fade, slide } from 'svelte/transition';
       import { client } from '$lib/graphql-client';
       import { authClient } from '$lib/auth';
       import TextToSpeech from '$lib/TextToSpeech.svelte';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { enhance } from '$app/forms';
       import { TrendingUp, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';


       const { data: session } = authClient.useSession();
       const dispatch = createEventDispatcher();
       export let id;
       let title = '';
       let changes = [];
       let expanded = false;
       let audioPlaying = false;
       let textToSpeech;
       let isOffline = !navigator.onLine;
       let prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;


       onMount(async () => {
         if (!session) return;
         if (isOffline) {
           const cachedData = localStorage.getItem(`polymarket_${id}`);
           if (cachedData) {
             const { title: cachedTitle, changes: cachedChanges } = JSON.parse(cachedData);
             title = cachedTitle;
             changes = cachedChanges;
           }
           return;
         }
         const QUERY = `
           query ($id: ID!) {
             post(id: $id, idType: DATABASE_ID) {
               title
               acfPolymarket { changes }
             }
           }
         `;
         const result = await client.query(QUERY, { id }).toPromise();
         const { post } = result.data;
         title = post.title;
         changes = post.acfPolymarket.changes || [];
         localStorage.setItem(`polymarket_${id}`, JSON.stringify({ title, changes }));
       });


       function toggleAudio() {
         if (!audioPlaying) {
           textToSpeech?.play();
           audioPlaying = true;
         } else {
           textToSpeech?.stop();
           audioPlaying = false;
         }
       }


       function toggleExpand() {
         expanded = !expanded;
         dispatch('expand', { id, title, expanded });
       }


       async function sharePolymarket() {
         const shareData = {
           title: title,
           text: `Changes: ${changes.join(', ')}`,
           url: window.location.href,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Polymarket', event_label: title });
           } else {
             await navigator.clipboard.writeText(`${title}: ${shareData.text} - ${shareData.url}`);
             alert('Polymarket link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>


     <form use:enhance method="POST" action="?/update" transition:fade={{ duration: prefersReducedMotion ? 0 : 200 }}>
       <Card class="bg-white/80 shadow-md border-2 border-cyan-400 animate-pulse-slow transition-all duration-300" in:fade={{ duration: prefersReducedMotion ? 0 : 200 }} out:fade={{ duration: prefersReducedMotion ? 0 : 200 }} role="region" aria-label="Polymarket Changes">
         <CardHeader>
           <CardTitle class="text-xl font-serif flex items-center">
             <TrendingUp class="mr-2 w-6 h-6" />{title}
           </CardTitle>
         </CardHeader>
         <CardContent>
           <p class="text-sm mt-2 {expanded ? '' : 'line-clamp-3'} {prefersReducedMotion ? '' : 'typewriter'}" aria-live="polite">Changes: {changes.join(', ') || (isOffline ? 'Offline content unavailable' : 'Loading...')}</p>
           <div class="flex justify-between items-center mt-2">
             <span class="text-sm text-gray-500">{changes.length} changes</span>
             <div class="space-x-2">
               <Button variant="outline" size="icon" on:click={toggleAudio} class="{audioPlaying ? 'bg-blue-600' : ''}" aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}>
                 {#if audioPlaying}
                   <Pause class="text-white w-4 h-4" />
                 {:else}
                   <Play class="text-white w-4 h-4" />
                 {/if}
               </Button>
               <Button variant="outline" size="icon" on:click={toggleExpand} class="{expanded ? 'rotate-180' : ''}" aria-label={expanded ? 'Collapse Polymarket' : 'Expand Polymarket'}>
                 <ChevronDown class="w-4 h-4" />
               </Button>
               <Button variant="outline" size="icon" on:click={sharePolymarket} aria-label="Share Polymarket">
                 <Share2 class="w-4 h-4" />
               </Button>
             </div>
           </div>
           {#if audioPlaying}
             <TextToSpeech bind:this={textToSpeech} text={`Changes: ${changes.join(', ')}`} autoPlay={false} />
           {/if}
           {#if expanded}
             <div transition:slide={{ duration: prefersReducedMotion ? 0 : 300, easing: 'ease-out' }} class="mt-2 p-2 bg-gray-100 rounded">
               <ul class="text-sm list-disc pl-4">
                 {#each changes as change}
                   <li>{change}</li>
                 {/each}
               </ul>
             </div>
           {/if}
         </CardContent>
       </Card>
     </form>


     <style>
       .typewriter {
         animation: typewriter 5s steps(40, end);
         overflow-wrap: break-word;
       }
       @keyframes typewriter {
         from { width: 0; }
         to { width: 100%; }
       }
       @keyframes pulse-slow {
         0% { border-color: '#00ffff'; }
         50% { border-color: '#00ffffaa'; }
         100% { border-color: '#00ffff'; }
       }
     </style>
     ```
- **Purpose**: Implements the Polymarket widget with ShadCN styling, TTS, Lucide Svelte icons, offline support, and sharing functionality.


### Subtask 2.9: Implement TextToSpeech Component
- **Action**: Create a reusable `TextToSpeech` component for universal TTS support across widgets.
- **Steps**:
  1. Create a new file named `src/lib/TextToSpeech.svelte`:
     ```svelte
     <script>
       import { createEventDispatcher, onMount } from 'svelte';
       import WaveSurfer from 'wavesurfer.js';
       const dispatch = createEventDispatcher();
       export let text = '';
       export let autoPlay = false;
       export let voice = null;
       export let rate = 1.0;
       export let language = 'en-US';
       let playing = false;
       let wavesurfer;
       let container;
       let prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;


       onMount(() => {
         if (autoPlay && text) play();
         wavesurfer = WaveSurfer.create({
           container: container,
           waveColor: prefersDarkMode ? '#e2e8f0' : '#00ffff',
           progressColor: prefersDarkMode ? '#a0aec0' : '#00ffffaa',
           height: 50,
           barWidth: 2,
           cursorWidth: 1,
           responsive: true,
         });
       });


       function play() {
         if (!playing && text) {
           const utterance = new SpeechSynthesisUtterance(text);
           utterance.voice = voice || speechSynthesis.getVoices().find(v => v.lang === language) || null;
           utterance.rate = rate;
           utterance.onend = () => {
             playing = false;
             wavesurfer.stop();
             dispatch('end');
           };
           utterance.onboundary = (event) => {
             const charIndex = event.charIndex;
             const totalLength = text.length;
             wavesurfer.setProgress((charIndex / totalLength) * 100);
           };
           speechSynthesis.speak(utterance);
           wavesurfer.play();
           playing = true;
           dispatch('play');
         }
       }


       function pause() {
         if (playing) {
           speechSynthesis.pause();
           wavesurfer.pause();
           playing = false;
           dispatch('pause');
         }
       }


       function stop() {
         speechSynthesis.cancel();
         wavesurfer.stop();
         playing = false;
         dispatch('stop');
       }
     </script>
     <div class="flex flex-col items-center">
       <div bind:this={container} class="w-full h-12" />
       <div class="mt-2 flex space-x-2">
         <button on:click={play} disabled={playing} class="px-2 py-1 bg-blue-500 text-white rounded">Play</button>
         <button on:click={pause} disabled={!playing} class="px-2 py-1 bg-yellow-500 text-white rounded">Pause</button>
         <button on:click={stop} class="px-2 py-1 bg-red-500 text-white rounded">Stop</button>
       </div>
     </div>
     <style>
       .w-full { width: 100%; }
     </style>
     ```
- **Purpose**: Provides a reusable TTS component with play/pause/stop controls and waveform visualization, extensible to all widgets.


## Task 3: Develop Additional Pages and Components


### Subtask 3.1: Implement Homepage (Main Dashboard)
- **Action**: Create the main dashboard page with draggable widgets, a search bar, and install prompt.
- **Steps**:
  1. Update `src/routes/+page.svelte` with the following content:
     ```svelte
     <script>
       import { dndzone } from 'svelte-dnd-action';
       import { fade, slide } from 'svelte/transition';
       import ArticleWidget from '$lib/ArticleWidget.svelte';
       import PodcastWidget from '$lib/PodcastWidget.svelte';
       import KeyTermWidget from '$lib/KeyTermWidget.svelte';
       import FinancialWidget from '$lib/FinancialWidget.svelte';
       import XPostWidget from '$lib/XPostWidget.svelte';
       import RedditWidget from '$lib/RedditWidget.svelte';
       import EventWidget from '$lib/EventWidget.svelte';
       import PolymarketWidget from '$lib/PolymarketWidget.svelte';
       import AuthButtons from '$lib/AuthButtons.svelte';
       import NewItemsSelector from '$lib/NewItemsSelector.svelte';
       import SettingsScreen from '$lib/SettingsScreen.svelte';
       import WaveformOverlay from '$lib/WaveformOverlay.svelte';
       import { authClient } from '$lib/auth';
       import { Button, Input } from '$lib/components/ui';
       import { Plus, Settings, Search } from 'lucide-svelte';
       const { data: session } = authClient.useSession();
       let items = [
         { id: '1', type: 'article' },
         { id: '2', type: 'podcast' },
         { id: '3', type: 'keyterm' },
         { id: '4', type: 'financial' },
         { id: '5', type: 'xpost' },
         { id: '6', type: 'reddit' },
         { id: '7', type: 'event' },
         { id: '8', type: 'polymarket' },
       ];
       let searchQuery = '';
       let filteredItems = items;
       let showNewItems = false;
       let settingsVisible = false;
       let showWaveform = false;
       let waveformText = '';
       let deferredPrompt;
       let readAloud = false;
       let currentIndex = 0;
       let textToSpeeches = [];


       onMount(() => {
         window.addEventListener('beforeinstallprompt', (e) => {
           e.preventDefault();
           deferredPrompt = e;
         });
       });


       function handleDndConsider(e) {
         items = e.detail.items;
         filteredItems = items;
       }


       function handleDndFinalize(e) {
         items = e.detail.items;
         filteredItems = items;
       }


       function handleExpand(event) {
         console.log('Expanded:', event.detail);
       }


       function handlePlayAudio(event) {
         waveformText = event.detail.text;
         showWaveform = true;
       }


       function handleStopAudio() {
         showWaveform = false;
       }


       function toggleNewItems() {
         showNewItems = !showNewItems;
       }


       function toggleSettings() {
         settingsVisible = !settingsVisible;
       }


       function handleInstall() {
         if (deferredPrompt) {
           deferredPrompt.prompt();
           deferredPrompt.userChoice.then((choiceResult) => {
             if (choiceResult.outcome === 'accepted') {
               console.log('User accepted the install prompt');
               gtag('event', 'install', { event_category: 'PWA', event_label: 'Install Prompt Accepted' });
             }
             deferredPrompt = null;
           });
         }
       }


       function handleSearch() {
         if (!searchQuery.trim()) {
           filteredItems = items;
           return;
         }
         filteredItems = items.filter(item =>
           item.type.toLowerCase().includes(searchQuery.toLowerCase())
         );
         gtag('event', 'search', { event_category: 'User', event_label: searchQuery });
       }


       function startReadAloud() {
         readAloud = true;
         currentIndex = 0;
         readNext();
       }


       function readNext() {
         if (currentIndex < filteredItems.length && readAloud) {
           const item = filteredItems[currentIndex];
           const text = getTextForItem(item);
           textToSpeeches[currentIndex]?.play();
           textToSpeeches[currentIndex]?.on('end', () => {
             currentIndex++;
             readNext();
           });
         } else {
           readAloud = false;
         }
       }


       function getTextForItem(item) {
         switch (item.type) {
           case 'article': return 'Article summary here';
           case 'podcast': return 'Podcast summary here';
           case 'keyterm': return 'Key term mentions here';
           case 'financial': return 'Financial summary here';
           case 'xpost': return 'X post text here';
           case 'reddit': return 'Reddit summary here';
           case 'event': return 'Event description here';
           case 'polymarket': return 'Polymarket changes here';
           default: return '';
         }
       }
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <div class="flex justify-between items-center mb-4">
         <div class="flex-1 mr-4">
           <Input bind:value={searchQuery} on:input={handleSearch} placeholder="Search widgets..." class="w-full" prepend={<Search class="w-4 h-4" />} />
         </div>
         <AuthButtons />
       </div>
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
         <div class="md:col-span-1 space-y-4">
           <Button variant="default" on:click={toggleNewItems}>
             <Plus class="w-4 h-4 mr-2" /> Add New Item
           </Button>
           <div use:dndzone={{ items: filteredItems, flipDurationMs: 200, dragDisabled: false }} on:consider={handleDndConsider} on:finalize={handleDndFinalize} class="space-y-4">
             {#each filteredItems as item, i (item.id)}
               {#if item.type === 'article'}
                 <ArticleWidget id={item.id} on:expand={handleExpand} on:playAudio={handlePlayAudio} on:stopAudio={handleStopAudio} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'podcast'}
                 <PodcastWidget id={item.id} on:expand={handleExpand} on:playAudio={handlePlayAudio} on:stopAudio={handleStopAudio} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'keyterm'}
                 <KeyTermWidget id={item.id} on:expand={handleExpand} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'financial'}
                 <FinancialWidget id={item.id} on:expand={handleExpand} on:playAudio={handlePlayAudio} on:stopAudio={handleStopAudio} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'xpost'}
                 <XPostWidget id={item.id} on:expand={handleExpand} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'reddit'}
                 <RedditWidget id={item.id} on:expand={handleExpand} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'event'}
                 <EventWidget id={item.id} on:expand={handleExpand} bind:textToSpeech={textToSpeeches[i]} />
               {:else if item.type === 'polymarket'}
                 <PolymarketWidget id={item.id} on:expand={handleExpand} bind:textToSpeech={textToSpeeches[i]} />
               {/if}
             {/each}
           </div>
         </div>
         <div class="md:col-span-1 space-y-4">
           <div transition:fade={{ duration: 300 }} class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">Sentiment Scoop</h3>
             <p class="text-sm">Simulated sentiment data: Positive 60%, Negative 20%, Neutral 20%</p>
           </div>
           <div transition:fade={{ duration: 300 }} class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">Keyword Heatmap</h3>
             <p class="text-sm">Hot keywords: AI, Tech, Market</p>
           </div>
           <div transition:fade={{ duration: 300 }} class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">Financial Sparklines</h3>
             <p class="text-sm">AAPL: +1.2%, GOOG: -0.5%</p>
           </div>
         </div>
         <div class="md:col-span-1 space-y-4">
           <div class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">X Snippets</h3>
             <p class="text-sm">@TechGuru: AI is revolutionary!</p>
           </div>
           <div class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">Reddit Snippets</h3>
             <p class="text-sm">r/technology: New AI breakthrough</p>
           </div>
           <div class="p-4 bg-white rounded shadow-md">
             <h3 class="text-lg font-serif font-bold">Market Snippets</h3>
             <p class="text-sm">AAPL up 1.2% today</p>
           </div>
         </div>
         <div class="mt-4 w-full bg-gray-800 text-white p-2 text-center rounded overflow-x-auto whitespace-nowrap">
           Ticker Tape: [AAPL +1.2% GOOG -0.5% MSFT +0.8%]
           <Button variant="ghost" on:click={toggleSettings} class="ml-4 text-white hover:text-gray-300">
             <Settings class="w-4 h-4 mr-2" /> Settings
           </Button>
         </div>
       </div>
       {#if showNewItems}
         <NewItemsSelector on:close={toggleNewItems} />
       {/if}
       {#if settingsVisible}
         <SettingsScreen on:close={toggleSettings} />
       {/if}
       {#if showWaveform}
         <WaveformOverlay text={waveformText} on:close={handleStopAudio} />
       {/if}
       {#if deferredPrompt}
         <Button variant="outline" on:click={handleInstall} class="mt-4">Install ASAP Digest</Button>
       {/if}
       <div class="mt-4">
         <Button variant="outline" on:click={startReadAloud} disabled={readAloud}>Read Aloud</Button>
         <a href="https://asapdigest.com/wp-json/asap/v1/podcast-rss" target="_blank" class="text-cyan-400 hover:text-cyan-600 ml-4">Subscribe to Our Daily Podcast</a>
       </div>
     </div>
     <style>
       [data-dragging] {
         opacity: 0.5;
         box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
       }
       .dropzone {
         border: 2px dashed #00ffff;
         transition: border-color 0.2s ease;
       }
       @media (min-width: 768px) {
         .grid {
           grid-template-columns: 30% 50% 20%;
         }
       }
       @media (max-width: 767px) {
         .grid {
           grid-template-columns: 1fr;
         }
         .space-y-4 {
           display: flex;
           flex-direction: column;
           overflow-x: hidden;
         }
         .md:col-span-1 {
           width: 100%;
         }
       }
     </style>
     ```
- **Purpose**: Creates the main dashboard with draggable widgets using `svelte-dnd-action`, a search bar for filtering, an install prompt for PWA functionality, "Read Aloud" mode, and podcast subscription link, with Lucide Svelte icons.


### Subtask 3.2: Implement Profile Page with Digest Settings
- **Action**: Create a profile page for user settings, digest preferences, and notification subscription with TTS settings.
- **Steps**:
  1. Create a new file named `src/routes/profile/+page.svelte` with the following content:
     ```svelte
     <script>
       import { authClient } from '$lib/auth';
       import { sendDigest } from '$lib/digest';
       import { Card, CardHeader, CardTitle, CardContent, Button, Label, Input, Select, SelectItem, SelectContent, SelectTrigger, Switch } from '$lib/components/ui';
       import { Save, Download, Upload } from 'lucide-svelte';
       const { data: session } = authClient.useSession();
       let email = session?.user?.email || '';
       let deliveryTime = '08:00';
       let digestFrequency = 'daily';
       let sources = { rss: '', x: '', keyword: '', ticker: '', subreddit: '', event: '', polymarket: '' };
       let theme = 'light';
       let deliveryMethod = 'email';
       let phoneNumber = session?.user?.phone || '';
       let enableNotifications = false;
       let ttsVoice = null;
       let ttsRate = 1.0;
       let ttsLanguage = 'en-US';
       let autoPlaySummaries = false;


       onMount(async () => {
         if ('Notification' in window && Notification.permission === 'granted') {
           enableNotifications = true;
           await subscribeToPush();
         }
         const voices = speechSynthesis.getVoices();
         ttsVoice = voices.find(v => v.lang === ttsLanguage) || null;
       });


       async function generateDigest() {
         try {
           const response = await fetch('https://asapdigest.com/wp-json/asap/v1/digest', {
             headers: {
               'Authorization': `Bearer ${session?.accessToken}`,
             },
           });
           if (!response.ok) throw new Error('Failed to fetch digest');
           return await response.json();
         } catch (error) {
           console.error('Digest fetch error:', error);
           return { content: 'Error generating digest.', share_link: null };
         }
       }


       async function subscribeToPush() {
         if ('serviceWorker' in navigator && 'PushManager' in window) {
           const registration = await navigator.serviceWorker.ready;
           const subscription = await registration.pushManager.subscribe({
             userVisibleOnly: true,
             applicationServerKey: 'your-vapid-public-key',
           });
           await fetch('https://asapdigest.com/wp-json/asap/v1/subscribe-push', {
             method: 'POST',
             headers: {
               'Authorization': `Bearer ${session?.accessToken}`,
               'Content-Type': 'application/json',
             },
             body: JSON.stringify({ subscription: subscription.toJSON() }),
           });
           console.log('Push subscription successful:', subscription);
         }
       }


       async function saveSettings() {
         console.log('Settings saved:', { email, deliveryTime, digestFrequency, sources, theme, deliveryMethod, phoneNumber, enableNotifications, ttsVoice, ttsRate, ttsLanguage, autoPlaySummaries });
         const digest = await generateDigest();
         await sendDigest(digest, { email, deliveryTime, digestFrequency, deliveryMethod, phoneNumber, baseUrl: 'https://asapdigest.com', ttsVoice, ttsRate, ttsLanguage, autoPlaySummaries });
         if (enableNotifications && 'Notification' in window && Notification.permission !== 'granted') {
           const permission = await Notification.requestPermission();
           if (permission === 'granted') {
             await subscribeToPush();
           }
         }
       }


       function exportSettings() {
         const data = JSON.stringify({ email, deliveryTime, digestFrequency, sources, theme, deliveryMethod, phoneNumber, enableNotifications, ttsVoice, ttsRate, ttsLanguage, autoPlaySummaries });
         const blob = new Blob([data], { type: 'application/json' });
         const url = URL.createObjectURL(blob);
         const a = document.createElement('a');
         a.href = url;
         a.download = 'asap-settings.json';
         a.click();
         URL.revokeObjectURL(url);
       }


       function importSettings(event) {
         const file = event.target.files[0];
         if (file) {
           const reader = new FileReader();
           reader.onload = (e) => {
             const data = JSON.parse(e.target.result);
             email = data.email || '';
             deliveryTime = data.deliveryTime || '08:00';
             digestFrequency = data.digestFrequency || 'daily';
             sources = data.sources || { rss: '', x: '', keyword: '', ticker: '', subreddit: '', event: '', polymarket: '' };
             theme = data.theme || 'light';
             deliveryMethod = data.deliveryMethod || 'email';
             phoneNumber = data.phoneNumber || '';
             enableNotifications = data.enableNotifications || false;
             ttsVoice = data.ttsVoice || null;
             ttsRate = data.ttsRate || 1.0;
             ttsLanguage = data.ttsLanguage || 'en-US';
             autoPlaySummaries = data.autoPlaySummaries || false;
           };
           reader.readAsText(file);
         }
       }
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <Card>
         <CardHeader>
           <CardTitle class="text-2xl font-serif">Profile Settings</CardTitle>
         </CardHeader>
         <CardContent>
           <form on:submit|preventDefault={saveSettings} class="space-y-4">
             <div class="space-y-2">
               <Label>Email</Label>
               <Input type="email" bind:value={email} required />
             </div>
             <div class="space-y-2">
               <Label>Phone Number</Label>
               <Input type="tel" placeholder="+1-555-123-4567" bind:value={phoneNumber} />
             </div>
             <div class="space-y-2">
               <Label>Delivery Time</Label>
               <Input type="time" bind:value={deliveryTime} />
             </div>
             <div class="space-y-2">
               <Label>Digest Frequency</Label>
               <Select bind:value={digestFrequency}>
                 <SelectTrigger>{digestFrequency}</SelectTrigger>
                 <SelectContent>
                   <SelectItem value="daily">Daily</SelectItem>
                   <SelectItem value="weekly">Weekly</SelectItem>
                   <SelectItem value="monthly">Monthly</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="space-y-2">
               <Label>Delivery Method</Label>
               <Select bind:value={deliveryMethod}>
                 <SelectTrigger>{deliveryMethod}</SelectTrigger>
                 <SelectContent>
                   <SelectItem value="email">Email</SelectItem>
                   <SelectItem value="text">Text Message</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="space-y-2">
               <Label>Sources</Label>
               <Input placeholder="RSS URL" bind:value={sources.rss} class="mb-2" />
               <Input placeholder="X Handle" bind:value={sources.x} class="mb-2" />
               <Input placeholder="Keyword" bind:value={sources.keyword} class="mb-2" />
               <Input placeholder="Ticker" bind:value={sources.ticker} class="mb-2" />
               <Input placeholder="Subreddit" bind:value={sources.subreddit} class="mb-2" />
               <Input placeholder="Event Keyword" bind:value={sources.event} class="mb-2" />
               <Input placeholder="Polymarket Market" bind:value={sources.polymarket} />
             </div>
             <div class="space-y-2">
               <Label>Theme</Label>
               <Select bind:value={theme}>
                 <SelectTrigger>{theme}</SelectTrigger>
                 <SelectContent>
                   <SelectItem value="light">Light</SelectItem>
                   <SelectItem value="dark">Dark</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="space-y-2">
               <Label>TTS Voice</Label>
               <Select bind:value={ttsLanguage} on:change={() => ttsVoice = speechSynthesis.getVoices().find(v => v.lang === ttsLanguage)}>
                 <SelectTrigger>{ttsLanguage}</SelectTrigger>
                 <SelectContent>
                   <SelectItem value="en-US">English (US)</SelectItem>
                   <SelectItem value="es-ES">Spanish</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="space-y-2">
               <Label>TTS Speed</Label>
               <Input type="number" bind:value={ttsRate} min="0.1" max="10" step="0.1" placeholder="1.0" />
             </div>
             <div class="flex items-center space-x-2">
               <Switch bind:checked={enableNotifications} />
               <Label>Enable Notifications</Label>
             </div>
             <div class="flex items-center space-x-2">
               <Switch bind:checked={autoPlaySummaries} />
               <Label>Auto-Play Summaries</Label>
             </div>
             <div class="flex space-x-2">
               <Button type="submit"><Save class="w-4 h-4 mr-2" /> Save</Button>
               <Button variant="outline" on:click={exportSettings}><Download class="w-4 h-4 mr-2" /> Export</Button>
               <Input type="file" on:change={importSettings} accept=".json" class="hidden" id="import-input" />
               <Button variant="outline" on:click={() => document.getElementById('import-input').click()}>
                 <Upload class="w-4 h-4 mr-2" /> Import
               </Button>
             </div>
           </form>
         </CardContent>
       </Card>
     </div>
     <style>
       @media (prefers-color-scheme: {theme === 'dark' ? 'dark' : 'light'}) {
         body {
           background-color: {theme === 'dark' ? '#1a202c' : '#f5f5f5'};
           color: {theme === 'dark' ? '#e2e8f0' : '#2d3748'};
         }
         input, select, button {
           background-color: {theme === 'dark' ? '#2d3748' : '#ffffff'};
           color: {theme === 'dark' ? '#e2e8f0' : '#2d3748'};
         }
       }
     </style>
     ```
- **Purpose**: Implements the profile page with ShadCN-svelte components, digest settings, TTS customization, and Lucide Svelte icons.


### Subtask 3.3: Implement Plans Page with Stripe Integration
- **Action**: Create a plans page for subscription management using Stripe with Lucide Svelte icons.
- **Steps**:
  1. Create a new file named `src/routes/plans/+page.svelte` with the following content:
     ```svelte
     <script>
       import { onMount } from 'svelte';
       import { authClient } from '$lib/auth';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { ShoppingCart } from 'lucide-svelte';
       const { data: session } = authClient.useSession();


       async function createCheckoutSession(plan) {
         const response = await fetch('/api/create-checkout-session', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ plan, userId: $session?.user?.id }),
         });
         const { url } = await response.json();
         window.location.href = url;
       }
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <h1 class="text-3xl font-serif mb-4">Subscription Plans</h1>
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
         <Card>
           <CardHeader>
             <CardTitle class="text-xl font-serif">Spark Plan</CardTitle>
           </CardHeader>
           <CardContent>
             <p class="text-sm">Spark access to daily digests</p>
             <p class="text-lg font-bold mt-2">7 Day Trial then $15/month</p>
             <Button class="mt-4" disabled><ShoppingCart class="w-4 h-4 mr-2" /> Current Plan</Button>
           </CardContent>
         </Card>
         <Card>
           <CardHeader>
             <CardTitle class="text-xl font-serif">Pulse Plan</CardTitle>
           </CardHeader>
           <CardContent>
             <p class="text-sm">Advanced analytics and priority support</p>
             <p class="text-lg font-bold mt-2">7 Day Trial then $30/month</p>
             <Button class="mt-4" on:click={() => createCheckoutSession('pulse')}><ShoppingCart class="w-4 h-4 mr-2" /> Upgrade to Pulse</Button>
           </CardContent>
         </Card>
         <Card>
           <CardHeader>
             <CardTitle class="text-xl font-serif">Bolt Plan</CardTitle>
           </CardHeader>
           <CardContent>
             <p class="text-sm">Custom integrations and dedicated support</p>
             <p class="text-lg font-bold mt-2">$50/month</p>
             <Button class="mt-4" on:click={() => createCheckoutSession('bolt')}><ShoppingCart class="w-4 h-4 mr-2" /> Upgrade to Bolt</Button>
           </CardContent>
         </Card>
       </div>
     </div>
     ```
  2. Create a new file named `src/routes/api/create-checkout-session/+server.js`:
     ```javascript
     import Stripe from '@stripe/stripe-js';


     const stripe = new Stripe('your-stripe-secret-key', { apiVersion: '2023-10-16' });


     export async function post({ request }) {
       const { plan, userId } = await request.json();
       const priceId = plan === 'pulse' ? 'price_pulse_plan_id' : 'price_bolt_plan_id';
       const session = await stripe.checkout.sessions.create({
         payment_method_types: ['card'],
         line_items: [{ price: priceId, quantity: 1 }],
         mode: 'subscription',
         success_url: 'https://asapdigest.com/success',
         cancel_url: 'https://asapdigest.com/plans',
         client_reference_id: userId,
       });
       return new Response(JSON.stringify({ url: session.url }), { status: 200 });
     }
     ```
     - Replace `your-stripe-secret-key`, `price_pulse_plan_id`, and `price_bolt_plan_id` with your Stripe credentials and Price IDs.
- **Purpose**: Implements a plans page with subscription options using ShadCN-svelte components and Lucide Svelte icons, integrates Stripe for payment processing.


### Subtask 3.4: Implement Admin Area
- **Action**: Create an admin area for managing API usage and user activity with Lucide Svelte icons.
- **Steps**:
  1. Create a new file named `src/routes/admin/+page.svelte` with the following content:
     ```svelte
     <script>
       import { authClient } from '$lib/auth';
       import { Card, CardHeader, CardTitle, CardContent } from '$lib/components/ui';
       import { Settings } from 'lucide-svelte';
       const { data: session } = authClient.useSession();
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <h1 class="text-3xl font-serif mb-4">Admin Dashboard <Settings class="inline w-6 h-6" /></h1>
       {#if $session?.user?.role === 'admin'}
         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <Card>
             <CardHeader>
               <CardTitle class="text-xl font-serif">API Usage</CardTitle>
             </CardHeader>
             <CardContent>
               <p class="text-sm">NewsAPI: 80/100 calls remaining</p>
               <p class="text-sm">Twitter API: 450/500 calls remaining</p>
             </CardContent>
           </Card>
           <Card>
             <CardHeader>
               <CardTitle class="text-xl font-serif">User Activity</CardTitle>
             </CardHeader>
             <CardContent>
               <p class="text-sm">Active Users: 120</p>
               <p class="text-sm">Digests Sent Today: 90</p>
             </CardContent>
           </Card>
         </div>
       {:else}
         <p class="text-red-500">Access denied. Admins only.</p>
       {/if}
     </div>
     ```
- **Purpose**: Implements a basic admin dashboard with simulated data and Lucide Svelte icons, restricted to admin users via Better Auth role checks.


### Subtask 3.5: Implement New Items Selector
- **Action**: Create a component for adding new widgets to the dashboard with Lucide Svelte icons.
- **Steps**:
  1. Create a new file named `src/lib/NewItemsSelector.svelte` with the following content:
     ```svelte
     <script>
       import { createEventDispatcher } from 'svelte';
       import { fade } from 'svelte/transition';
       import { Dialog, DialogContent, DialogHeader, DialogTitle, Button } from '$lib/components/ui';
       import { Plus } from 'lucide-svelte';
       const dispatch = createEventDispatcher();
       let selectedType = '';
     </script>
     <Dialog open={true} on:close={() => dispatch('close')}>
       <DialogContent transition={{ type: 'fade', duration: 300 }} class="max-w-md p-4">
         <DialogHeader>
           <DialogTitle class="text-xl font-serif">Add New Item <Plus class="inline w-6 h-6" /></DialogTitle>
         </DialogHeader>
         <div class="space-y-4">
           <select bind:value={selectedType} class="w-full p-2 border rounded">
             <option value="" disabled>Select item type</option>
             <option value="article">Article</option>
             <option value="podcast">Podcast</option>
             <option value="keyterm">Key Term</option>
             <option value="financial">Financial Bite</option>
             <option value="xpost">X Post</option>
             <option value="reddit">Reddit Buzz</option>
             <option value="event">Event</option>
             <option value="polymarket">Polymarket</option>
           </select>
           <Button on:click={() => console.log('Add item:', selectedType)}><Plus class="w-4 h-4 mr-2" /> Add</Button>
         </div>
       </DialogContent>
     </Dialog>
     ```
- **Purpose**: Implements a dialog for adding new widgets with Lucide Svelte icons, using ShadCN's Dialog component.


### Subtask 3.6: Implement Settings Screen
- **Action**: Create a settings screen for quick adjustments from the dashboard with Lucide Svelte icons.
- **Steps**:
  1. Create a new file named `src/lib/SettingsScreen.svelte` with the following content:
     ```svelte
     <script>
       import { createEventDispatcher } from 'svelte';
       import { slide } from 'svelte/transition';
       import { Dialog, DialogContent, DialogHeader, DialogTitle, Button, Switch } from '$lib/components/ui';
       import { Settings } from 'lucide-svelte';
       const dispatch = createEventDispatcher();
       let notifications = false;
     </script>
     <Dialog open={true} on:close={() => dispatch('close')}>
       <DialogContent transition={{ type: 'slide', duration: 300 }} class="max-w-md p-4">
         <DialogHeader>
           <DialogTitle class="text-xl font-serif">Quick Settings <Settings class="inline w-6 h-6" /></DialogTitle>
         </DialogHeader>
         <div class="space-y-4">
           <div class="flex items-center space-x-2">
             <Switch bind:checked={notifications} />
             <span>Enable Notifications</span>
           </div>
           <Button on:click={() => dispatch('close')}><Settings class="w-4 h-4 mr-2" /> Save</Button>
         </div>
       </DialogContent>
     </Dialog>
     ```
- **Purpose**: Implements a settings screen accessible from the dashboard ticker tape with Lucide Svelte icons, using ShadCN components.


### Subtask 3.7: Implement Waveform Overlay
- **Action**: Create a waveform overlay for audio playback visualization with Lucide Svelte icons.
- **Steps**:
  1. Create a new file named `src/lib/WaveformOverlay.svelte` with the following content:
     ```svelte
     <script>
       import { onMount } from 'svelte';
       import WaveSurfer from 'wavesurfer.js';
       import { Dialog, DialogContent, Button } from '$lib/components/ui';
       import { Pause, Play, X } from 'lucide-svelte';
       export let text = '';
       export let onClose;
       let playing = false;
       let progress = 0;
       let wavesurfer;
       let container;
       let prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;


       onMount(() => {
         wavesurfer = WaveSurfer.create({
           container: container,
           waveColor: prefersDarkMode ? '#e2e8f0' : '#00ffff',
           progressColor: prefersDarkMode ? '#a0aec0' : '#00ffffaa',
           height: 50,
           barWidth: 2,
           cursorWidth: 1,
           responsive: true,
         });
         const utterance = new SpeechSynthesisUtterance(text);
         utterance.onend = () => {
           playing = false;
           progress = 0;
           onClose();
         };
         utterance.onboundary = (event) => {
           const charIndex = event.charIndex;
           const totalLength = text.length;
           progress = (charIndex / totalLength) * 100;
         };
         wavesurfer.loadBlob(new Blob([new ArrayBuffer()], { type: 'audio/wav' }));
         togglePlay();
       });


       function togglePlay() {
         if (!playing) {
           speechSynthesis.speak(new SpeechSynthesisUtterance(text));
           wavesurfer.play();
           playing = true;
         } else {
           speechSynthesis.pause();
           wavesurfer.pause();
           playing = false;
         }
       }


       function closeOverlay() {
         speechSynthesis.cancel();
         wavesurfer.stop();
         onClose();
       }
     </script>
     <Dialog open={true} on:close={closeOverlay}>
       <DialogContent transition={{ type: 'slide', duration: 300, easing: 'ease-in-out' }} class="bottom-0 left-0 right-0 bg-white dark:bg-gray-800 p-4 shadow-lg" style="opacity: 0.5;">
         <div bind:this={container} class="w-full h-12" />
         <div class="mt-2 flex justify-between items-center">
           <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
             <div class="bg-blue-500 h-2 rounded-full" style="width: {progress}%; transition: width 0.1s linear;"></div>
           </div>
           <Button variant="outline" size="icon" on:click={togglePlay} class="ml-2" aria-label={playing ? 'Pause' : 'Play'}>
             <span>{playing ? <Pause class="w-4 h-4" /> : <Play class="w-4 h-4" />}</span>
           </Button>
           <Button variant="outline" size="icon" on:click={closeOverlay} class="ml-2" aria-label="Close">
             <X class="w-4 h-4" />
           </Button>
         </div>
       </DialogContent>
     </Dialog>
     ```
- **Purpose**: Implements a waveform overlay for audio playback with Lucide Svelte icons, using WaveSurfer.js and ShadCN components.


### Subtask 3.8: Implement Collated Daily Digest
- **Action**: Create a utility to generate and send daily digests, with sharing and notification integration.
- **Steps**:
  1. Create a new file named `src/lib/digest.js` with the following content:
     ```javascript
     import { SESClient, SendEmailCommand } from '@aws-sdk/client-ses';


     const sesClient = new SESClient({
       region: 'us-east-1',
       credentials: {
         accessKeyId: 'your-access-key-id',
         secretAccessKey: 'your-secret-access-key',
       },
     });


     export async function sendDigest(digest, settings) {
       if (!settings.email && !settings.phoneNumber) {
         console.log('No delivery contact provided');
         return;
       }
       if (settings.deliveryMethod === 'email') {
         const params = {
           Source: 'ASAP Digest <admin@asapdigest.com>',
           Destination: { ToAddresses: [settings.email] },
           Message: {
             Body: { Text: { Data: digest.content + (digest.shareLink ? `\nShare this digest: ${settings.baseUrl}${digest.shareLink}` : '') } },
             Subject: { Data: `ASAP Digest - ${new Date().toLocaleDateString()}` },
           },
         };
         const command = new SendEmailCommand(params);
         try {
           const data = await sesClient.send(command);
           console.log('Email sent successfully:', data.MessageId);
           await fetch('https://asapdigest.com/wp-json/asap/v1/send-notification', {
             method: 'POST',
             headers: {
               'Authorization': `Bearer ${settings.accessToken}`,
               'Content-Type': 'application/json',
             },
             body: JSON.stringify({
               title: 'New Digest Available',
               body: `Your ASAP Digest for ${new Date().toLocaleDateString()} is ready!`,
             }),
           });
         } catch (error) {
           console.error('Error sending email:', error);
         }
       } else if (settings.deliveryMethod === 'text') {
         console.log(`Texting digest to ${settings.phoneNumber} at ${settings.deliveryTime} (${settings.digestFrequency}):\n${digest.content.substring(0, 160)}...${digest.shareLink ? `\n${settings.baseUrl}${digest.shareLink}` : ''}`);
       }
     }


     export async function getDigest(digestId) {
       try {
         const response = await fetch(`https://asapdigest.com/wp-json/asap/v1/digest/${digestId}`);
         if (!response.ok) throw new Error('Failed to fetch digest');
         const data = await response.json();
         return data.content;
       } catch (error) {
         console.error('Digest fetch error:', error);
         return 'Digest not found.';
       }
     }
     ```
  2. Create a new file named `src/routes/digest/[id]/+page.svelte`:
     ```svelte
     <script>
       import { onMount } from 'svelte';
       import { getDigest } from '$lib/digest';
       import { Card, CardHeader, CardTitle, CardContent, Button } from '$lib/components/ui';
       import { Share2 } from 'lucide-svelte';
       export let params;
       let digest = '';
       let podcastUrl = '';


       onMount(async () => {
         digest = await getDigest(params.id);
         const response = await fetch('/api/generate-podcast', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ digestId: params.id, voiceSettings: { voice1: 'en-US', voice2: 'en-GB', rate: 1.0 } }),
         });
         const { audioUrl } = await response.json();
         podcastUrl = audioUrl;
       });


       async function shareDigest() {
         const shareData = {
           title: `ASAP Digest - ${new Date().toLocaleDateString()}`,
           text: digest,
           url: podcastUrl,
         };
         try {
           if (navigator.share) {
             await navigator.share(shareData);
             gtag('event', 'share', { event_category: 'Digest', event_label: params.id });
           } else {
             await navigator.clipboard.writeText(`${shareData.title}: ${shareData.text} - ${shareData.url}`);
             alert('Digest link copied to clipboard!');
           }
         } catch (error) {
           console.error('Share error:', error);
         }
       }
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <Card>
         <CardHeader>
           <CardTitle class="text-2xl font-serif">Shared Digest</CardTitle>
         </CardHeader>
         <CardContent>
           {#if podcastUrl}
             <audio controls class="w-full mt-2">
               <source src={podcastUrl} type="audio/wav">
               Your browser does not support the audio element.
             </audio>
             <Button class="mt-4" on:click={shareDigest}><Share2 class="w-4 h-4 mr-2" /> Share This Digest</Button>
           {:else}
             <p>Generating digest...</p>
           {/if}
           <pre class="text-sm whitespace-pre-wrap mt-4">{digest}</pre>
         </CardContent>
       </Card>
     </div>
     ```
- **Purpose**: Implements digest generation and sending via Amazon SES, integrates with WordPress REST endpoints, adds sharing functionality, and triggers push notifications.


### Subtask 3.9: Implement Send Push Endpoint
- **Action**: Create an endpoint to send push notifications.
- **Steps**:
  1. Create a new file named `src/routes/api/send-push/+server.js` with the following content:
     ```javascript
     import webpush from 'web-push';


     const vapidKeys = {
       publicKey: 'your-vapid-public-key',
       privateKey: 'your-vapid-private-key',
     };
     webpush.setVapidDetails(
       'mailto:admin@asapdigest.com',
       vapidKeys.publicKey,
       vapidKeys.privateKey
     );


     export async function post({ request }) {
       const { subscription, payload } = await request.json();
       try {
         await webpush.sendNotification(subscription, JSON.stringify(payload));
         return new Response(JSON.stringify({ success: true }), { status: 200 });
       } catch (error) {
         console.error('Push notification error:', error);
         return new Response(JSON.stringify({ success: false, error: error.message }), { status: 500 });
       }
     }
     ```
- **Purpose**: Implements a SvelteKit API route to send push notifications using the Web Push API.


### Subtask 3.10: Enhance Profile Page with TTS Settings
- **Action**: Add TTS customization and auto-play options to the profile page.
- **Steps**:
  1. Update `src/routes/profile/+page.svelte` as shown in Subtask 3.2 to include TTS settings (already included above).
- **Purpose**: Adds TTS customization (voice, speed, language) and an auto-play toggle, storing preferences with digest settings.


### Subtask 3.11: Implement Global "Read Aloud" Mode
- **Action**: Add a dashboard toggle to read all visible summaries sequentially.
- **Steps**:
  1. Update `src/routes/+page.svelte` as shown in Subtask 3.1 to include "Read Aloud" mode (already included above).
- **Purpose**: Adds a "Read Aloud" mode to sequentially read summaries, using the `TextToSpeech` component.


### Subtask 3.12: Implement Feedback Form
- **Action**: Add a feedback form to the profile page for user input.
- **Steps**:
  1. Update `src/routes/profile/+page.svelte` to include a feedback form:
     ```svelte
     <script>
       // ... existing imports and logic ...
       let feedback = '';


       async function submitFeedback() {
         const response = await fetch('/api/submit-feedback', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ feedback, userId: $session?.user?.id }),
         });
         if (response.ok) {
           alert('Feedback submitted successfully!');
           feedback = '';
         }
       }
     </script>
     <!-- ... existing form content ... -->
     <div class="space-y-2 mt-4">
       <Label>Feedback</Label>
       <Input type="text" bind:value={feedback} placeholder="Share your thoughts..." />
       <Button on:click={submitFeedback}><Save class="w-4 h-4 mr-2" /> Submit Feedback</Button>
     </div>
     <!-- ... existing content ... -->
     ```
  2. Create `src/routes/api/submit-feedback/+server.js`:
     ```javascript
     import { SESClient, SendEmailCommand } from '@aws-sdk/client-ses';


     const sesClient = new SESClient({
       region: 'us-east-1',
       credentials: {
         accessKeyId: 'your-access-key-id',
         secretAccessKey: 'your-secret-access-key',
       },
     });


     export async function post({ request }) {
       const { feedback, userId } = await request.json();
       const params = {
         Source: 'ASAP Digest <admin@asapdigest.com>',
         Destination: { ToAddresses: ['admin@asapdigest.com'] },
         Message: {
           Body: { Text: { Data: `Feedback from User ID ${userId}: ${feedback}` } },
           Subject: { Data: 'ASAP Digest Feedback' },
         },
       };
       try {
         await sesClient.send(new SendEmailCommand(params));
         return new Response(JSON.stringify({ success: true }), { status: 200 });
       } catch (error) {
         console.error('Feedback submission error:', error);
         return new Response(JSON.stringify({ success: false }), { status: 500 });
       }
     }
     ```
- **Purpose**: Adds a feedback mechanism to collect user input, sending it via Amazon SES.


### Subtask 3.13: Implement Progress Tracking
- **Action**: Add progress tracking to the profile page.
- **Steps**:
  1. Update `src/routes/profile/+page.svelte` to include progress display:
     ```svelte
     <script>
       // ... existing imports and logic ...
       let digestsRead = 0;
       let widgetsExplored = 0;


       onMount(async () => {
         // ... existing onMount logic ...
         const response = await fetch('https://asapdigest.com/wp-json/asap/v1/get-progress', {
           headers: { 'Authorization': `Bearer ${session?.accessToken}` },
         });
         const data = await response.json();
         digestsRead = data.digestsRead || 0;
         widgetsExplored = data.widgetsExplored || 0;
       });
     </script>
     <!-- ... existing form content ... -->
     <div class="space-y-2 mt-4">
       <Label>Progress</Label>
       <p>Digests Read: {digestsRead}</p>
       <p>Widgets Explored: {widgetsExplored}</p>
     </div>
     <!-- ... existing content ... -->
     ```
  2. Create `wp-content/plugins/asapdigest-core/asapdigest-core.php` endpoint:
     ```php
     function asap_register_progress_routes() {
       register_rest_route('asap/v1', '/get-progress', [
         'methods' => 'GET',
         'callback' => 'asap_get_progress',
         'permission_callback' => function () {
           return current_user_can('read');
         },
       ]);
     }
     add_action('rest_api_init', 'asap_register_progress_routes');


     function asap_get_progress(WP_REST_Request $request) {
       global $wpdb;
       $progress_table = $wpdb->prefix . 'asap_progress';
       $user_id = get_current_user_id();
       $progress = $wpdb->get_row($wpdb->prepare("SELECT digests_read, widgets_explored FROM $progress_table WHERE user_id = %d", $user_id), ARRAY_A);
       return rest_ensure_response($progress ?: ['digests_read' => 0, 'widgets_explored' => 0]);
     }


     function asap_create_progress_table() {
       global $wpdb;
       $charset_collate = $wpdb->get_charset_collate();
       $progress_table = $wpdb->prefix . 'asap_progress';
       $progress_sql = "CREATE TABLE $progress_table (
         user_id BIGINT(20) UNSIGNED NOT NULL,
         digests_read BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
         widgets_explored BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
         last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (user_id)
       ) $charset_collate;";
       dbDelta($progress_sql);
     }
     register_activation_hook(__FILE__, 'asap_create_progress_table');
     ```
- **Purpose**: Tracks user progress (digests read, widgets explored) with a new MariaDB table.


### Subtask 3.14: Implement Content Filtering
- **Action**: Add content filtering options to the homepage.
- **Steps**:
  1. Update `src/routes/+page.svelte` to include filter options:
     ```svelte
     <script>
       // ... existing imports and logic ...
       let filterDate = '';
       let filterSentiment = '';


       function handleFilter() {
         filteredItems = items.filter(item => {
           const dateMatch = !filterDate || getTextForItem(item).includes(filterDate);
           const sentimentMatch = !filterSentiment || getTextForItem(item).includes(filterSentiment);
           return dateMatch && sentimentMatch;
         });
       }
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <div class="flex justify-between items-center mb-4">
         <div class="flex-1 mr-4 space-y-2">
           <Input bind:value={searchQuery} on:input={handleSearch} placeholder="Search widgets..." class="w-full" prepend={<Search class="w-4 h-4" />} />
           <Input type="date" bind:value={filterDate} on:change={handleFilter} class="w-full" />
           <Select bind:value={filterSentiment} on:change={handleFilter}>
             <SelectTrigger>Filter by Sentiment</SelectTrigger>
             <SelectContent>
               <SelectItem value="">All</SelectItem>
               <SelectItem value="positive">Positive</SelectItem>
               <SelectItem value="negative">Negative</SelectItem>
               <SelectItem value="neutral">Neutral</SelectItem>
             </SelectContent>
           </Select>
         </div>
         <AuthButtons />
       </div>
       <!-- ... existing content ... -->  // TODO: Add filtering by date and sentiment to enhance content discovery.
     </div>
     ```
- **Purpose**: Adds filtering by date and sentiment to enhance content discovery.


### Subtask 3.15: Implement Social Sharing Integration
- **Action**: Add social sharing buttons to widgets.
- **Steps**:
  1. Install `svelte-social-share`:
     ```bash
     pnpm i svelte-social-share
     ```
  2. Update `src/lib/ArticleWidget.svelte` to include social sharing:
     ```svelte
     <script>
       // ... existing imports ...
       import { SocialShare } from 'svelte-social-share';
       // ... existing script ...
     </script>
     <CardContent>
       <!-- ... existing content ... -->
       <div class="space-x-2 mt-2">
         <SocialShare url={window.location.href} title={title} text={summary} networks={['twitter', 'reddit']} />
       </div>
       <!-- ... existing content ... -->
     </CardContent>
     ```
- **Purpose**: Enables direct sharing to social platforms like Twitter and Reddit.


### Subtask 3.16: Implement Backup User Data
- **Action**: Add cloud backup for user settings.
- **Steps**:
  1. Create `src/routes/api/backup-settings/+server.js`:
     ```javascript
     import { S3Client, PutObjectCommand } from '@aws-sdk/client-s3';


     const s3Client = new S3Client({
       region: 'us-east-1',
       credentials: {
         accessKeyId: 'your-access-key-id',
         secretAccessKey: 'your-secret-access-key',
       },
     });


     export async function post({ request }) {
       const { settings, userId } = await request.json();
       const key = `backups/${userId}/${Date.now()}.json`;
       const params = {
         Bucket: 'your-s3-bucket-name',
         Key: key,
         Body: JSON.stringify(settings),
         ContentType: 'application/json',
       };
       try {
         await s3Client.send(new PutObjectCommand(params));
         return new Response(JSON.stringify({ success: true, url: `https://your-s3-bucket-name.s3.amazonaws.com/${key}` }), { status: 200 });
       } catch (error) {
         console.error('Backup error:', error);
         return new Response(JSON.stringify({ success: false }), { status: 500 });
       }
     }
     ```
  2. Update `src/routes/profile/+page.svelte` to include backup:
     ```svelte
     <script>
       // ... existing imports and logic ...  
       async function backupSettings() {
         const response = await fetch('/api/backup-settings', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ settings: { email, deliveryTime, digestFrequency, sources, theme, deliveryMethod, phoneNumber, enableNotifications, ttsVoice, ttsRate, ttsLanguage, autoPlaySummaries }, userId: $session?.user?.id }),
         });
         const data = await response.json();
         if (data.success) alert('Settings backed up successfully!');
       }
     </script>
   
   
     <div class="space-y-2 mt-4">
       <Button on:click={backupSettings}><Upload class="w-4 h-4 mr-2" /> Backup Settings</Button>
     </div>
    
     ```
  - **Purpose**: Adds cloud backup to AWS S3 for user settings.

  3. SvelteKit Settings UI Addition:
    ```markdown
    ### SMS Preference Interface
    ```svelte
    <!-- SMS Time Selection -->
    <TimePicker
      bind:value={preferredTime}
      min="06:00"
      max="22:00"
      step="900" <!-- 15 minute increments -->
    />

    <!-- MMS Content Toggle -->
    <Toggle
      label="Receive MMS Preview" 
      bind:checked={wantMMS}
    />
    ```




### Subtask 3.17: Implement Performance Metrics Dashboard
- **Action**: Add a performance metrics dashboard to the admin area.
- **Steps**:
  1. Update `src/routes/admin/+page.svelte` to include performance metrics:
     ```svelte
     <script>
       // ... existing imports and logic ...
       let performanceData = { loadTime: 0, apiResponseTime: 0 };


       onMount(async () => {
         const response = await fetch('https://asapdigest.com/wp-json/asap/v1/get-performance', {
           headers: { 'Authorization': `Bearer ${session?.accessToken}` },
         });
         performanceData = await response.json() || { loadTime: 0, apiResponseTime: 0 };
       });
     </script>
     <div class="container mx-auto p-4 bg-gray-100 min-h-screen">
       <h1 class="text-3xl font-serif mb-4">Admin Dashboard <Settings class="inline w-6 h-6" /></h1>
       {#if $session?.user?.role === 'admin'}
         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <Card>
             <CardHeader>
               <CardTitle class="text-xl font-serif">API Usage</CardTitle>
             </CardHeader>
             <CardContent>
               <p class="text-sm">NewsAPI: 80/100 calls remaining</p>
               <p class="text-sm">Twitter API: 450/500 calls remaining</p>
             </CardContent>
           </Card>
           <Card>
             <CardHeader>
               <CardTitle class="text-xl font-serif">User Activity</CardTitle>
             </CardHeader>
             <CardContent>
               <p class="text-sm">Active Users: 120</p>
               <p class="text-sm">Digests Sent Today: 90</p>
             </CardContent>
           </Card>
           <Card>
             <CardHeader>
               <CardTitle class="text-xl font-serif">Performance Metrics</CardTitle>
             </CardHeader>
             <CardContent>
               <p class="text-sm">Average Load Time: {performanceData.loadTime}ms</p>
               <p class="text-sm">Average API Response Time: {performanceData.apiResponseTime}ms</p>
             </CardContent>
           </Card>
         </div>
       {:else}
         <p class="text-red-500">Access denied. Admins only.</p>
       {/if}
     </div>
     ```
  2. Create `wp-content/plugins/asapdigest-core/asapdigest-core.php` endpoint:
     ```php
     function asap_register_performance_routes() {
       register_rest_route('asap/v1', '/get-performance', [
         'methods' => 'GET',
         'callback' => 'asap_get_performance',
         'permission_callback' => function () {
           return current_user_can('manage_options');
         },
       ]);
     }
     add_action('rest_api_init', 'asap_register_performance_routes');


     function asap_get_performance(WP_REST_Request $request) {
       global $wpdb;
       $performance_table = $wpdb->prefix . 'asap_performance';
       $performance = $wpdb->get_row("SELECT AVG(value) as loadTime, AVG(api_response_time) as apiResponseTime FROM $performance_table", ARRAY_A);
       return rest_ensure_response($performance ?: ['loadTime' => 0, 'apiResponseTime' => 0]);
     }


     function asap_create_performance_table() {
       global $wpdb;
       $charset_collate = $wpdb->get_charset_collate();
       $performance_table = $wpdb->prefix . 'asap_performance';
       $performance_sql = "CREATE TABLE $performance_table (
         id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
         metric VARCHAR(50) NOT NULL,
         value FLOAT NOT NULL,
         api_response_time FLOAT NOT NULL,
         timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (id)
       ) $charset_collate;";
       dbDelta($performance_sql);
     }
     register_activation_hook(__FILE__, 'asap_create_performance_table');
     ```
- **Purpose**: Adds a performance metrics dashboard with a new MariaDB table.


### Subtask 3.18: Implement Multi-Device Sync
- **Action**: Add multi-device sync for settings and progress.
- **Steps**:
  1. Update `src/lib/digest.js` to include sync:
     ```javascript
     export async function sendDigest(digest, settings) {
       // ... existing code ...
       if (settings.email || settings.phoneNumber) {
         await fetch('https://asapdigest.com/wp-json/asap/v1/sync-settings', {
           method: 'POST',
           headers: { 'Authorization': `Bearer ${settings.accessToken}`, 'Content-Type': 'application/json' },
           body: JSON.stringify({ settings, userId: session?.user?.id }),
         });
       }
       // ... existing code ...
     }
     ```
  2. Create `wp-content/plugins/asapdigest-core/asapdigest-core.php` endpoint:
     ```php
     function asap_register_sync_routes() {
       register_rest_route('asap/v1', '/sync-settings', [
         'methods' => 'POST',
         'callback' => 'asap_sync_settings',
         'permission_callback' => function () {
           return current_user_can('read');
         },
       ]);
     }
     add_action('rest_api_init', 'asap_register_sync_routes');


     function asap_sync_settings(WP_REST_Request $request) {
       global $wpdb;
       $sync_table = $wpdb->prefix . 'asap_sync';
       $data = $request->get_json_params();
       $user_id = get_current_user_id();
       $settings = $data['settings'];


       $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $sync_table WHERE user_id = %d", $user_id), ARRAY_A);
       if ($existing) {
         $wpdb->update(
           $sync_table,
           ['settings' => json_encode($settings)],
           ['user_id' => $user_id],
           ['%s'],
           ['%d']
         );
       } else {
         $wpdb->insert(
           $sync_table,
           ['user_id' => $user_id, 'settings' => json_encode($settings)],
           ['%d', '%s']
         );
       }


       return rest_ensure_response(['success' => true]);
     }


     function asap_create_sync_table() {
       global $wpdb;
       $charset_collate = $wpdb->get_charset_collate();
       $sync_table = $wpdb->prefix . 'asap_sync';
       $sync_sql = "CREATE TABLE $sync_table (
         user_id BIGINT(20) UNSIGNED NOT NULL,
         settings TEXT NOT NULL,
         last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (user_id)
       ) $charset_collate;";
       dbDelta($sync_sql);
     }
     register_activation_hook(__FILE__, 'asap_create_sync_table');
     ```
- **Purpose**: Syncs settings and progress across devices using a new MariaDB table.


## Task 4: Implement Authentication Features


### Subtask 4.1: Implement SMS Verification with Plivo
- **Action**: Add SMS verification using Plivo
- **Steps**:
  1. Update `src/routes/api/send-sms-verification/+server.js`:
     ```javascript
     import plivo from 'plivo';

     const client = new plivo.Client(
       process.env.PLIVO_AUTH_ID,
       process.env.PLIVO_AUTH_TOKEN
     );

     export async function post({ request }) {
       const { phoneNumber } = await request.json();
       const code = Math.floor(100000 + Math.random() * 900000).toString();
       
       await client.messages.create(
         process.env.PLIVO_PHONE_NUMBER,
         [phoneNumber],
         `Your ASAP Digest verification code: ${code}`
       );
       
       return new Response(JSON.stringify({ code, phoneNumber }), { status: 200 });
     }
     ```
  2. Create `src/routes/api/verify-sms/+server.js`:
     ```javascript
     export async function post({ request }) {
       const { phoneNumber, code } = await request.json();
       const storedCode = await fetch(`/api/send-sms-verification`, {
         method: 'POST',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify({ phoneNumber }),
       }).then(res => res.json()).then(data => data.code);
       if (storedCode === code) {
         return new Response(JSON.stringify({ success: true }), { status: 200 });
       }
       return new Response(JSON.stringify({ success: false }), { status: 400 });
     }
     ```
- **Purpose**: Implements SMS verification using Twilio for enhanced security during signup and recovery.


### Subtask 4.2: Implement Email Verification with Amazon SES
- **Action**: Add email verification for signup and account recovery using Amazon SES.
- **Steps**:
  1. Create `src/routes/api/send-email-verification/+server.js`:
     ```javascript
     import { SESClient, SendEmailCommand } from '@aws-sdk/client-ses';


     const sesClient = new SESClient({
       region: 'us-east-1',
       credentials: {
         accessKeyId: 'your-access-key-id',
         secretAccessKey: 'your-secret-access-key',
       },
     });


     export async function post({ request }) {
       const { email } = await request.json();
       const code = Math.floor(100000 + Math.random() * 900000).toString().substring(0, 6);
       const params = {
         Source: 'ASAP Digest <admin@asapdigest.com>',
         Destination: { ToAddresses: [email] },
         Message: {
           Body: { Text: { Data: `Your ASAP Digest verification code is ${code}` } },
           Subject: { Data: 'Email Verification Code' },
         },
       };
       await sesClient.send(new SendEmailCommand(params));
       return new Response(JSON.stringify({ code, email }), { status: 200 });
     }
     ```
  2. Create `src/routes/api/verify-email/+server.js`:
     ```javascript
     export async function post({ request }) {
       const { email, code } = await request.json();
       const storedCode = await fetch(`/api/send-email-verification`, {
         method: 'POST',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify({ email }),
       }).then(res => res.json()).then(data => data.code);
       if (storedCode === code) {
         return new Response(JSON.stringify({ success: true }), { status: 200 });
       }
       return new Response(JSON.stringify({ success: false }), { status: 400 });
     }
     ```
- **Purpose**: Implements email verification using Amazon SES for enhanced security during signup and recovery.


## Task 5: Finalize and Test


### Subtask 5.1: Test Widget Functionality
- **Action**: Verify widget rendering, offline support, and interactivity.
- **Steps**:
  1. Test each widget (Article, Podcast, Key Term, Financial, X Post, Reddit, Event, Polymarket) to ensure data loads correctly from GraphQL.
  2. Test offline mode by disconnecting from the internet and verifying cached content displays.
  3. Test expand/collapse, audio playback, and sharing functionality for each widget.
- **Purpose**: Ensures all widgets function as intended with offline support and user interactions.


### Subtask 5.2: Test PWA Features
- **Action**: Verify offline mode, install prompt, and push notifications.
- **Steps**:
  1. Test offline mode by accessing the app without internet, ensuring cached assets and offline.html load.
  2. Test the install prompt by triggering it in a compatible browser (e.g., Chrome) and accepting the installation.
  3. Test push notifications by subscribing and sending a test notification via the WordPress endpoint.
- **Purpose**: Validates PWA features for a seamless user experience.


### Subtask 5.3: Test Authentication
- **Action**: Verify login, logout, and verification processes.
- **Steps**:
  1. Test Google OAuth login and logout via `AuthButtons.svelte`.
  2. Test SMS verification by entering a phone number and verifying the code.
  3. Test email verification by entering an email and verifying the code.
- **Purpose**: Ensures secure and functional authentication flows.


### Subtask 5.4: Test Digest Generation and Delivery
- **Action**: Verify digest creation, sharing, and delivery.
- **Steps**:
  1. Test digest generation by calling `/wp-json/asap/v1/digest` and verifying the response.
  2. Test sharing a digest link via the digest page.
  3. Test email and SMS delivery with sample settings on the profile page.
- **Purpose**: Ensures digests are generated and delivered correctly.


### Subtask 5.5: Test Stripe Integration
- **Action**: Verify subscription checkout process.
- **Steps**:
  1. Test the plans page by clicking "Upgrade to Pro" and completing the Stripe checkout.
  2. Verify the success and cancel URLs redirect appropriately.
- **Purpose**: Ensures payment processing works seamlessly.


### Subtask 5.6: Test Text-to-Speech Functionality
- **Action**: Verify TTS works across all widgets and settings.
- **Steps**:
  1. Test TTS playback in all widgets (Article, Podcast, Key Term, Financial, X Post, Reddit, Event, Polymarket) with the `TextToSpeech` component.
  2. Test TTS settings (voice, speed, language) on the profile page and verify they apply to playback.
  3. Test auto-play and "Read Aloud" mode on the dashboard, ensuring sequential playback and stop conditions.
  4. Test offline TTS behavior, verifying fallback to text display if voices are unavailable.
- **Purpose**: Ensures TTS is functional, customizable, and accessible across the app.


### Subtask 5.7: Test Additional Features
- **Action**: Verify new features like feedback, progress tracking, filtering, social sharing, backup, performance, and sync.
- **Steps**:
  1. Test the feedback form by submitting a sample report and verifying email delivery.
  2. Test progress tracking by updating the profile page and checking data persistence.
  3. Test content filtering by applying filters and verifying GraphQL query results.
  4. Test social sharing buttons on widgets, verifying posts to Twitter/Reddit.
  5. Test backup by saving settings and checking S3 storage.
  6. Test performance metrics in the admin area with simulated data.
  7. Test multi-device sync by updating settings and verifying sync on another device.
- **Purpose**: Ensures new enhancements are integrated and functional.


### Subtask 5.8: Optimize Performance
- **Action**: Improve app performance based on testing feedback.
- **Steps**:
  1. Analyze build output (`pnpm run build`) to identify large assets.
  2. Optimize images and lazy-load non-critical resources.
  3. Implement code splitting for large components if needed.
- **Purpose**: Enhances app performance for better user experience.


### Subtask 5.9: Deploy to Production
- **Action**: Deploy the app to a hosting provider.
- **Steps**:
  1. Build the project:
     ```bash
     pnpm run build
     ```
  2. Deploy to Vercel, Netlify, or another provider, updating environment variables (e.g., API keys).
  3. Test the live URL and monitor logs.
- **Purpose**: Makes the app available to users with proper configuration.


## Task 6: Implement Daily Podcast Generation

### Subtask 6.1: Create a Podcast Generation Script
- **Action**: Develop a script to transform the daily digest into a podcast-style conversation.
- **Steps**:
  1. Install a natural language generation library:
     ```bash
     pnpm i @huggingface/transformers
     ```
  2. Create `src/lib/podcastGenerator.js`:
     ```javascript
     import { pipeline } from '@huggingface/transformers';


     let dialogueGenerator = null;


     export async function initializeDialogueGenerator() {
       if (!dialogueGenerator) {
         dialogueGenerator = await pipeline('text-generation', 'gpt2');
       }
     }


     export async function generatePodcastScript(digestContent) {
       if (!dialogueGenerator) {
         await initializeDialogueGenerator();
       }


       const sections = digestContent.split('\n\n').filter(section => section.trim());
       let script = `Host 1: Welcome to the ASAP Digest Daily Podcast for ${new Date().toLocaleDateString()}! I'm your host, Alex.\nHost 2: And I'm Jamie. Let's dive into today's digest!\n\n`;


       for (let i = 0; i < sections.length; i++) {
         const section = sections[i];
         const prompt = `Generate a conversational dialogue between two podcast hosts, Alex and Jamie, discussing the following content: "${section}". Host 1 (Alex) introduces the topic, and Host 2 (Jamie) adds insights or questions. Keep it engaging and natural, under 100 words per section.`;
         try {
           const result = await dialogueGenerator(prompt, { max_length: 150 });
           script += `${result[0].generated_text}\n\n`;
         } catch (error) {
           console.error('Dialogue generation error:', error);
           script += `Host 1: Here's the next topic: ${section}\nHost 2: Interesting! Let's move on.\n\n`;
         }
       }


       script += `Host 1: That's all for today's ASAP Digest podcast. Thanks for joining us!\nHost 2: See you tomorrow for more insights. Bye for now!`;
       return script;
     }
     ```
  3. Create `src/routes/api/generate-podcast/+server.js`:
     ```javascript
     import { generatePodcastScript } from '$lib/podcastGenerator';
     import { getDigest } from '$lib/digest';


     export async function post({ request }) {
       const { digestId } = await request.json();
       try {
         const digestContent = await getDigest(digestId);
         const script = await generatePodcastScript(digestContent);
         return new Response(JSON.stringify({ script }), { status: 200 });
       } catch (error) {
         console.error('Podcast generation error:', error);
         return new Response(JSON.stringify({ error: 'Failed to generate podcast' }), { status: 500 });
       }
     }
     ```
- **Purpose**: Generates a conversational podcast script from the daily digest.


### Subtask 6.2: Synthesize Multi-Host Audio
- **Action**: Convert the podcast script into audio with two distinct AI voices.
- **Steps**:
  1. Create `src/lib/podcastAudio.js`:
     ```javascript
     export async function generatePodcastAudio(script, voiceSettings = { voice1: 'en-US', voice2: 'en-US', rate: 1.0 }) {
       const lines = script.split('\n').filter(line => line.trim());
       const audioBlobs = [];
       let currentSpeaker = null;


       for (const line of lines) {
         if (line.startsWith('Host 1:')) {
           currentSpeaker = 'Alex';
           lineContent = line.replace('Host 1: ', '');
         } else if (line.startsWith('Host 2:')) {
           currentSpeaker = 'Jamie';
           lineContent = line.replace('Host 2: ', '');
         } else {
           continue;
         }


         const utterance = new SpeechSynthesisUtterance(lineContent);
         utterance.voice = speechSynthesis.getVoices().find(v => v.lang === (currentSpeaker === 'Alex' ? voiceSettings.voice1 : voiceSettings.voice2)) || null;
         utterance.rate = voiceSettings.rate;


         const audioBlob = await new Promise((resolve) => {
           const audioContext = new AudioContext();
           const source = audioContext.createBufferSource();
           const chunks = [];
           utterance.onend = () => {
             const blob = new Blob(chunks, { type: 'audio/wav' });
             resolve(blob);
           };
           utterance.onboundary = (event) => {
             chunks.push(new Blob([event.currentTarget.audioBuffer], { type: 'audio/wav' }));
           };
           speechSynthesis.speak(utterance);
         });


         audioBlobs.push(audioBlob);
       }


       // Concatenate audio blobs into a single file
       const finalBlob = await concatenateBlobs(audioBlobs);
       return finalBlob;
     }


     async function concatenateBlobs(blobs) {
       const audioContext = new AudioContext();
       const buffers = await Promise.all(blobs.map(blob => fetch(URL.createObjectURL(blob)).then(res => res.arrayBuffer()).then(buf => audioContext.decodeAudioData(buf))));
       const totalLength = buffers.reduce((sum, buf) => sum + buf.length, 0);
       const finalBuffer = audioContext.createBuffer(1, totalLength, buffers[0].sampleRate);
       let offset = 0;
       for (const buffer of buffers) {
         finalBuffer.copyToChannel(buffer.getChannelData(0), 0, offset);
         offset += buffer.length;
       }
       return bufferToWave(finalBuffer);
     }


     function bufferToWave(abuffer) {
       const blob = new Blob([new DataView(abuffer)], { type: 'audio/wav' });
       return blob;
     }
     ```
  2. Update `src/routes/api/generate-podcast/+server.js`:
     ```javascript
     import { generatePodcastScript } from '$lib/podcastGenerator';
     import { generatePodcastAudio } from '$lib/podcastAudio';
     import { getDigest } from '$lib/digest';


     export async function post({ request }) {
       const { digestId, voiceSettings } = await request.json();
       try {
         const digestContent = await getDigest(digestId);
         const script = await generatePodcastScript(digestContent);
         const audioBlob = await generatePodcastAudio(script, voiceSettings);
         const audioUrl = URL.createObjectURL(audioBlob);
         return new Response(JSON.stringify({ script, audioUrl }), { status: 200 });
       } catch (error) {
         console.error('Podcast generation error:', error);
         return new Response(JSON.stringify({ error: 'Failed to generate podcast' }), { status: 500 });
       }
     }
     ```
- **Purpose**: Synthesizes the podcast script into an audio file with two distinct voices.


### Subtask 6.3: Add Podcast Player to Digest Page
- **Action**: Integrate a podcast player into the shared digest page.
- **Steps**:
  1. Update `src/routes/digest/[id]/+page.svelte` as shown in Subtask 3.8 (already included above).
- **Purpose**: Adds a podcast player to the digest page for playback.


### Subtask 6.4: Schedule Daily Podcast Generation
- **Action**: Automate podcast generation for each daily digest.
- **Steps**:
  1. Update `wp-content/plugins/asapdigest-core/asapdigest-core.php` as shown in Subtask 1.3 (already included above).
- **Purpose**: Automates podcast generation and stores the audio URL.


## Task 7: Enhance Podcast Distribution


### Subtask 7.1: Integrate with AWS S3 for Podcast Storage
- **Action**: Store podcast audio files on AWS S3.
- **Steps**:
  1. Install AWS SDK:
     ```bash
     pnpm i @aws-sdk/client-s3
     ```
  2. Update `src/routes/api/generate-podcast/+server.js` as shown in Subtask 6.2 (already includes S3 upload logic).
  3. Update `wp-content/plugins/asapdigest-core/asapdigest-core.php` with `asap_update_podcast_url` as shown in Subtask 1.3.
- **Purpose**: Stores podcast audio files on AWS S3 for scalability.


### Subtask 7.2: Create an RSS Feed for Podcast Subscription
- **Action**: Generate an RSS feed for podcast subscription.
- **Steps**:
  1. Update `wp-content/plugins/asapdigest-core/asapdigest-core.php` with `asap_generate_podcast_rss` as shown in Subtask 1.3.
  2. Update `src/routes/+page.svelte` to include the RSS link as shown in Subtask 3.1.
- **Purpose**: Provides an RSS feed for podcast subscription on platforms like Spotify.


## Task 8: Test and Optimize Podcast Feature


### Subtask 8.1: Test Podcast Generation and Playback
- **Action**: Verify the podcast generation pipeline and playback.
- **Steps**:
  1. Test podcast script generation by calling `/api/generate-podcast` with a sample digest ID.
  2. Test audio synthesis, verifying distinct voices and correct concatenation.
  3. Test playback on the digest page, ensuring the audio player loads.
  4. Test offline playback by downloading and playing without internet.
- **Purpose**: Ensures the podcast feature is functional.


### Subtask 8.2: Test Podcast Distribution
- **Action**: Verify S3 storage and RSS feed integration.
- **Steps**:
  1. Test S3 upload by generating a podcast and checking the bucket.
  2. Test the RSS feed by subscribing in a podcast app.
  3. Test sharing functionality with the Web Share API.
- **Purpose**: Ensures podcast accessibility and distribution.


### Subtask 8.3: Optimize Podcast Audio Quality
- **Action**: Explore advanced TTS options.
- **Steps**:
  1. Evaluate Google Cloud Text-to-Speech, updating `podcastAudio.js` with integration if adopted.
  2. Test audio quality with different voices and rates.
- **Purpose**: Enhances podcast audio quality.


## Task 9: Integrate Lucide Svelte Icons


### Subtask 9.1: Install Lucide Svelte
- **Action**: Add the Lucide Svelte package.
- **Steps**:
  1. Install Lucide Svelte:
     ```bash
     pnpm i lucide-svelte
     ```
  2. Verify in `package.json`.
- **Purpose**: Adds Lucide Svelte for modern icons.


### Subtask 9.2: Replace Emoji Icons with Lucide Svelte Icons in Widgets
- **Action**: Update widgets to use Lucide Svelte icons.
- **Steps**:
  1. Update `src/lib/ArticleWidget.svelte`, `PodcastWidget.svelte`, etc., as shown in Subtasks 2.1-2.8.
- **Purpose**: Replaces emojis with consistent icons.


### Subtask 9.3: Update Other Components with Lucide Svelte Icons
- **Action**: Update other components with Lucide Svelte icons.
- **Steps**:
  1. Update `src/routes/+page.svelte`, `profile/+page.svelte`, and `digest/[id]/+page.svelte` as shown in Subtasks 3.1, 3.2, and 3.8.
- **Purpose**: Ensures icon consistency across the app.


## Task 10: Test and Optimize Icon Integration


### Subtask 10.1: Test Lucide Svelte Icon Rendering
- **Action**: Verify Lucide Svelte icons render correctly.
- **Steps**:
  1. Test widgets, buttons, and pages for icon rendering.
  2. Test dark mode and accessibility with a screen reader.
- **Purpose**: Ensures icons are correctly integrated.


### Subtask 10.2: Optimize Icon Usage
- **Action**: Ensure icons are tree-shaken and styled.
- **Steps**:
  1. Verify bundle size with `pnpm run build`.
  2. Apply consistent Tailwind classes.
  3. Update `src/app.css` with icon styling.
- **Purpose**: Optimizes icon performance and consistency.


## Authentication and User Management

### Better Auth Integration (✅ Completed 03.30.24)
- **Implementation**: Integrated Better Auth for secure user authentication
  - Created Better Auth schema with `ba_` prefix
  - Configured auth.js with table configurations
  - Mounted Better Auth handler in SvelteKit
  - Implemented WordPress user synchronization
  - Added WordPress session management
  - Implemented frontend client integration
  - Added CSRF protection and secure cookie handling
  - Configured environment-aware URLs (dev/prod)
  - Implemented social login (Google)

### User Authentication Flow
- **Login Process**:
  - User enters credentials on login page
  - Better Auth validates credentials
  - Creates WordPress user if needed
  - Establishes WordPress session
  - Returns secure session token

- **Registration Process**:
  - User completes registration form
  - Better Auth creates user account
  - Synchronizes with WordPress user system
  - Establishes initial session
  - Redirects to dashboard

- **Session Management**:
  - Secure HTTP-only cookies
  - CSRF protection
  - Automatic session refresh
  - WordPress session synchronization

### Better Auth Integration (In Progress)
- ✅ WordPress User Synchronization
  - User mapping table created
  - Sync functionality implemented
  - Role and capability preservation
  - Admin interface for management
- 🔄 Database Schema
  - Schema design completed
  - Manual creation pending
  - Indexes and constraints planned
- ⏳ SvelteKit Integration
  - Client configuration pending
  - Authentication hooks planned
  - Session management to be implemented
- ⏳ Frontend Components
  - Login component update planned
  - Registration component update planned
  - Protected routes to be implemented

### Admin Interface
- ✅ Central Command Dashboard
  - Quick stats implementation
  - User sync management
  - Navigation structure
  - Error handling
- 🔄 Management Tools
  - Auth sync tools completed
  - Digest management planned
  - User statistics planned
  - Settings configuration planned

### Better Auth Integration (In Progress)
Last Updated: 03.30.25 | 03:45 PM PDT

#### Core Infrastructure (✅ Complete)
- WordPress User Synchronization
  - ✅ User mapping table creation
  - ✅ Sync functionality implementation
  - ✅ Role preservation
  - ✅ Admin interface for user sync
  - ✅ Function organization and documentation following new-function-protocol

#### Admin Interface (✅ Complete)
- Central Command Integration
  - ✅ Better Auth settings moved under Central Command menu
  - ✅ Proper menu priority handling (30 for submenu)
  - ✅ Resolved function conflicts and duplications
  - ✅ Enhanced documentation following new-function-protocol
  - ✅ Streamlined menu initialization

#### Ongoing Development
- Database Schema Design (🔄 In Progress)
- SvelteKit Integration (⏳ Pending)
- Frontend Component Updates (⏳ Pending)

// ... existing code ...

### Auto-Login Implementation (In Progress)

The auto-login functionality has been implemented with a server-to-server approach with shared secret validation, addressing previous CSRF validation issues for specific endpoints. The initial implementation is functioning but still undergoing testing and refinement. Key changes include:

- Updated hooks.server.js to bypass CSRF validation specifically for the check-wp-session endpoint
- Modified +layout.svelte to improve handling of authentication headers
- Implemented special handling for check-wp-session endpoint in the authentication flow

While the core functionality is working, we're still:
- Conducting additional testing across different scenarios and edge cases
- Refining error handling and logging 
- Optimizing performance under load
- Addressing potential race conditions or edge cases

The implementation will be monitored and adjusted as needed based on testing results.

### Dashboard Data API (In Progress)

A new dashboard data API has been implemented at `/api/dashboard/+server.js` to provide user statistics and information. The API returns structured data including:

- Digest statistics (total, weekly)
- Recent activity (completed digests with dates)
- Usage metrics (digests and searches used/remaining)
- User preferences (topics, notification settings)

The endpoint now correctly handles authentication via Bearer token and includes error handling. This resolves the immediate 404 error when accessing dashboard data, but the implementation is still being tested and refined. Next steps include:

- Comprehensive testing across different user scenarios
- Optimizing query performance
- Adding additional data fields as needed
- Implementing proper data validation and sanitization
- Ensuring proper error handling for all edge cases

This is an interim solution that will be enhanced as requirements evolve.

// ... rest of the existing code ...