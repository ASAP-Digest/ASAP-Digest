<?php
/**
 * @file-marker ASAP_Digest_Core
 * @location /wp-content/plugins/asapdigest-core/includes/class-rest-base.php
 */
// ... existing code ...
add_action('rest_api_init', function() {
  register_rest_route('asap/v1', '/ingest-digest-items', [
    'methods' => 'POST',
    'callback' => 'asap_ingest_digest_items',
    'permission_callback' => function() {
      return is_user_logged_in(); // Or Better Auth check
    }
  ]);
});

function asap_ingest_digest_items($request) {
  $params = $request->get_json_params();
  $user_id = $params['userId'];
  $digest_id = $params['digestId'] ?? null;
  $items = $params['items'] ?? [];
  $results = [];
  $errors = [];

  foreach ($items as $item) {
    // Example: Check for existing by URL or ID (pseudo-code, replace with real logic)
    $existing_id = asap_find_content_by_url_or_id($item['url'] ?? '', $item['id'] ?? '');
    if ($existing_id) {
      // Link to digest (pseudo-code)
      $digest_item_id = asap_link_to_digest($digest_id, $existing_id, $user_id);
      $results[] = [
        'id' => $item['id'] ?? '',
        'status' => 'already_exists',
        'wp_post_id' => $existing_id,
        'digest_item_id' => $digest_item_id
      ];
      continue;
    }
    // Ingest new item (pseudo-code)
    $wp_post_id = asap_ingest_new_content($item, $user_id);
    if ($wp_post_id) {
      $digest_item_id = asap_link_to_digest($digest_id, $wp_post_id, $user_id);
      $results[] = [
        'id' => $item['id'] ?? '',
        'status' => 'added',
        'wp_post_id' => $wp_post_id,
        'digest_item_id' => $digest_item_id
      ];
    } else {
      $results[] = [
        'id' => $item['id'] ?? '',
        'status' => 'error',
        'message' => 'Failed to ingest content.'
      ];
      $errors[] = [
        'itemId' => $item['id'] ?? '',
        'error' => 'Failed to ingest content.'
      ];
    }
  }

  return rest_ensure_response([
    'success' => true,
    'results' => $results,
    'errors' => $errors
  ]);
}

/**
 * Find existing content by canonical URL or unique ID.
 *
 * @param string $url Canonical URL of the content
 * @param string $id  External unique ID (optional)
 * @return int|false  WP Post ID if found, false otherwise
 */
function asap_find_content_by_url_or_id($url, $id = '') {
  global $wpdb;
  if (!empty($id)) {
    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_asap_external_id' AND meta_value = %s LIMIT 1",
      $id
    ));
    if ($post_id) return (int)$post_id;
  }
  if (!empty($url)) {
    $post_id = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_asap_canonical_url' AND meta_value = %s LIMIT 1",
      $url
    ));
    if ($post_id) return (int)$post_id;
  }
  return false;
}

/**
 * Ingest new content as a custom post type and store metadata.
 *
 * @param array $item  Content item data (id, url, title, type, etc.)
 * @param int   $user_id  User ID performing the ingestion
 * @return int|false  WP Post ID if created, false otherwise
 */
function asap_ingest_new_content($item, $user_id) {
  if (empty($item['title']) || empty($item['type'])) return false;
  $postarr = [
    'post_title'   => sanitize_text_field($item['title']),
    'post_status'  => 'publish',
    'post_type'    => 'asap_content',
    'post_author'  => $user_id,
    'post_content' => isset($item['summary']) ? sanitize_textarea_field($item['summary']) : '',
  ];
  $post_id = wp_insert_post($postarr, true);
  if (is_wp_error($post_id) || !$post_id) return false;
  // Store canonical URL and external ID for deduplication
  if (!empty($item['url'])) {
    update_post_meta($post_id, '_asap_canonical_url', esc_url_raw($item['url']));
  }
  if (!empty($item['id'])) {
    update_post_meta($post_id, '_asap_external_id', sanitize_text_field($item['id']));
  }
  if (!empty($item['type'])) {
    update_post_meta($post_id, '_asap_content_type', sanitize_text_field($item['type']));
  }
  if (!empty($item['image'])) {
    update_post_meta($post_id, '_asap_image_url', esc_url_raw($item['image']));
  }
  if (!empty($item['date'])) {
    update_post_meta($post_id, '_asap_date', sanitize_text_field($item['date']));
  }
  if (!empty($item['source'])) {
    update_post_meta($post_id, '_asap_source', sanitize_text_field($item['source']));
  }
  // Store raw metadata if present
  if (!empty($item['metadata']) && is_array($item['metadata'])) {
    update_post_meta($post_id, '_asap_metadata', wp_json_encode($item['metadata']));
  }
  return (int)$post_id;
}

/**
 * Link a content item to a user's digest (custom table or post meta).
 *
 * @param int|null $digest_id Digest ID (optional, can be null)
 * @param int $post_id Content WP Post ID
 * @param int $user_id User ID
 * @return int|false Digest item ID (row ID or meta ID), or false on failure
 */
function asap_link_to_digest($digest_id, $post_id, $user_id) {
  // For MVP, use post meta on the digest post or user meta as fallback
  if ($digest_id) {
    // Link to a digest post (assume 'asap_digest' post type)
    $meta_id = add_post_meta($digest_id, '_asap_digest_item', [
      'content_post_id' => $post_id,
      'added_by' => $user_id,
      'added_at' => current_time('mysql', 1)
    ]);
    return $meta_id ? (int)$meta_id : false;
  } else {
    // Fallback: store in user meta (for personal digests)
    $meta_id = add_user_meta($user_id, '_asap_digest_item', [
      'content_post_id' => $post_id,
      'added_at' => current_time('mysql', 1)
    ]);
    return $meta_id ? (int)$meta_id : false;
  }
}
// ... existing code ... 