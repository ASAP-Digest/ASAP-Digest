
Okay, based on our conversation and the functionalities outlined in the project (especially from the roadmap and your focus on a versatile "Swiss Army knife" platform), here's a comprehensive schema for the "Module" entity.

We'll use a single Custom Post Type (CPT) named `asap_module` and a meta field `_asap_module_type` to differentiate between various kinds of modules.

---

## Module Entity Schema (Custom Post Type: `asap_module`)

**1. Core CPT Configuration:**

*   **Slug:** `asap_module`
*   **Labels:**
    *   Singular Name: "Module"
    *   Plural Name: "Modules"
    *   (And other standard WordPress CPT labels like `add_new_item`, `edit_item`, etc.)
*   **Supports:**
    *   `title`: The primary title or descriptor of the module content (e.g., "Article Summary: AI Advances", "BTC/USD Price Update").
    *   `editor`: Can be used for the main textual content of the module, a detailed AI-generated summary, or even structured JSON if a rich text editor is preferred for certain module types.
    *   `author`: To associate the module instance with a WordPress user who added/configured it.
    *   `custom-fields`: Essential for all module-specific data and settings.
    *   `thumbnail`: Optionally, if a module can have a distinct visual representation separate from its source content (e.g., a custom icon or image for a user-created module).
    *   `revisions`: For tracking changes to module content or configuration.
*   **Public:** `false`. Modules are generally not intended to be browsed as standalone entities with their own permalinks. They exist as components within a Digest. If a direct link to a module's content is ever needed, it would likely be via an API endpoint or a special view scoped within its parent digest.
*   **Publicly Queryable:** `true` (to allow fetching via REST API/GraphQL when part of a digest, even if `public` is false).
*   **Show UI:** `true` (to manage modules in the WordPress admin).
*   **Show in Nav Menus:** `false`.
*   **Hierarchical:** `false`.
*   **Has Archive:** `false`.
*   **Show in REST API:** `true`.
    *   REST API Base: `wp/v2` (standard).
    *   REST Controller Class: `WP_REST_Posts_Controller` (standard).
*   **GraphQL Single Name:** `module`
*   **GraphQL Plural Name:** `modules`
*   **Menu Icon:** (e.g., `dashicons-screenoptions` or a custom SVG).
*   **Capability Type:** `post` (or custom capabilities like `asap_module_caps` for finer control).

**2. Standard WordPress Fields Utilized:**

*   `ID`: (BIGINT UNSIGNED) Unique identifier for each module post.
*   `post_title`: (TEXT) The main display title of the module.
*   `post_content`: (LONGTEXT) Primary content area. Usage varies by module type:
    *   Could hold the full text for a manually created module.
    *   Could store an AI-generated summary or a curated excerpt from ingested content.
    *   Could store structured data (e.g., JSON for a chart module if not using dedicated meta fields).
*   `post_author`: (BIGINT UNSIGNED) WordPress `user_ID` of the user who created/added this module instance to a digest or the system.
*   `post_date`: (DATETIME) Timestamp of when the module instance was created.
*   `post_modified`: (DATETIME) Timestamp of the last modification to the module's content or settings.
*   `post_status`: (VARCHAR(20))
    *   `publish`: Module is active and ready to be displayed within a digest.
    *   `draft`: Module is in an incomplete state.
    *   `pending`: Module might require review before being included (e.g., if user-generated content needs moderation).
    *   `private`: Module might only be visible to its author or specific roles within a digest context.
    *   `trash`: Marked for deletion.
*   `post_parent`: (BIGINT UNSIGNED) Stores the `ID` of the parent `asap_digest` CPT this module belongs to. This is a key relationship.
*   `menu_order`: (INT(11)) Can be used for a default ordering of modules if not explicitly defined by the parent digest's layout configuration (e.g., GridStack).
*   `post_name`: (VARCHAR(200)) URL-friendly slug, though less relevant if modules are not publicly browsable.
*   `post_excerpt`: (TEXT) Can be used for a very short summary or hover text, if applicable.

**3. Common Custom Post Meta Fields (prefix `_asap_module_`):**

    *   **Type & Source Identification:**
        *   `_asap_module_type`: (VARCHAR) **Critical discriminator.** Examples: "article_summary", "podcast_segment", "key_term", "financial_quote", "x_post", "reddit_thread", "event_item", "polymarket_card", "image_display", "video_embed", "text_block", "generic_embed".
        *   `_asap_module_source_ingested_content_id`: (BIGINT) Foreign key to `wp_asap_ingested_content.id` if this module's data originates from the Content Ingestion System. This is vital for traceability and leveraging pre-processed AI data.
        *   `_asap_module_original_source_url`: (TEXT/URL) The ultimate URL of the original content (e.g., article link, tweet permalink). Sourced from ingested content or manually entered.
        *   `_asap_module_data_provider_name`: (VARCHAR) Name of the API or system that provided the raw data (e.g., "OpenAI API", "WordPress RSS Feed", "AlphaVantage", "User Input").

    *   **AI-Enhanced Data (often populated from linked `wp_asap_ai_processed_content` via `_asap_module_source_ingested_content_id`):**
        *   `_asap_module_ai_generated_title`: (TEXT) An alternative title suggested or generated by AI.
        *   `_asap_module_ai_summary_text`: (LONGTEXT) AI-generated summary for this module. Could also populate `post_content`.
        *   `_asap_module_ai_keywords_json`: (JSON Array of strings) e.g., `["AI", "machine learning", "SvelteKit"]`
        *   `_asap_module_ai_entities_json`: (JSON Array of objects) e.g., `[{"text": "OpenAI", "type": "ORG"}, {"text": "Sam Altman", "type": "PERSON"}]`
        *   `_asap_module_ai_sentiment_score`: (FLOAT or VARCHAR) Sentiment score (-1.0 to 1.0 or "positive", "neutral", "negative").
        *   `_asap_module_ai_sentiment_details_json`: (JSON) More granular sentiment (e.g., aspects, emotions).
        *   `_asap_module_ai_content_quality_score`: (INT 0-100) Quality assessment of the source content.
        *   `_asap_module_ai_tts_audio_url`: (TEXT/URL) URL to a Text-to-Speech audio rendering of the module's primary text content.

    *   **Display & Interaction Settings:**
        *   `_asap_module_is_expanded_by_default`: (BOOLEAN - '0' or '1') Whether the module appears fully expanded or collapsed when the digest loads.
        *   `_asap_module_allow_user_resize`: (BOOLEAN - '0' or '1') If the user can resize this module in a grid layout.
        *   `_asap_module_allow_user_close`: (BOOLEAN - '0' or '1') If the user can "close" or hide this module from their view of the digest.
        *   `_asap_module_show_source_link_explicitly`: (BOOLEAN - '0' or '1')
        *   `_asap_module_show_publish_date`: (BOOLEAN - '0' or '1')
        *   `_asap_module_enable_sharing_options`: (BOOLEAN - '0' or '1') If sharing actions (e.g., share to social media, copy link to module) are enabled.
        *   `_asap_module_custom_css_classes`: (VARCHAR) For applying ad-hoc styling.

    *   **Data & Content Fields (Generic Fallbacks):**
        *   `_asap_module_primary_data_json`: (JSON) For modules with highly structured data not fitting other fields, or for data fetched directly from an API for this module instance.
        *   `_asap_module_secondary_text`: (TEXT) An additional text field for subtitles, captions, or supplementary info.
        *   `_asap_module_primary_image_url`: (TEXT/URL) A primary image associated with this module.
        *   `_asap_module_primary_link_url`: (TEXT/URL) A primary call-to-action or "learn more" link.
        *   `_asap_module_link_text`: (VARCHAR) Text for the `_asap_module_primary_link_url`.

    *   **User-Specific Context:**
        *   `_asap_module_user_notes_text`: (TEXT) Private notes a user might add to this module instance within their digest.

**4. Type-Specific Custom Post Meta Fields (prefix `_asap_module_` continued):**
    *(These supplement or provide more specific alternatives to the common fields above, based on `_asap_module_type`)*

    *   **`_asap_module_type: "article_summary"`**
        *   `_asap_module_article_original_title`: (TEXT)
        *   `_asap_module_article_author_name`: (TEXT)
        *   `_asap_module_article_publication_date_iso`: (DATETIME String - ISO 8601)
        *   `_asap_module_article_featured_image_url`: (TEXT/URL)
        *   `_asap_module_article_full_content_ref_id`: (BIGINT) Optional, if full article text is stored elsewhere (e.g., another CPT or table) and only summary is in `post_content`.

    *   **`_asap_module_type: "podcast_segment"`**
        *   `_asap_module_podcast_episode_title`: (TEXT)
        *   `_asap_module_podcast_show_name`: (TEXT)
        *   `_asap_module_podcast_audio_url`: (TEXT/URL) (Direct MP3 link)
        *   `_asap_module_podcast_segment_start_time_ms`: (INT) Milliseconds
        *   `_asap_module_podcast_segment_end_time_ms`: (INT) Milliseconds
        *   `_asap_module_podcast_transcript_segment_json`: (JSON or TEXT) Transcript for this specific segment.
        *   `_asap_module_podcast_cover_art_url`: (TEXT/URL)

    *   **`_asap_module_type: "key_term"`**
        *   (The term itself could be `post_title`, and definition in `post_content` or `_asap_module_ai_summary_text`)
        *   `_asap_module_keyterm_related_terms_json`: (JSON Array of strings or objects)
        *   `_asap_module_keyterm_source_references_json`: (JSON Array of objects) `[{"name": "Source Name", "url": "..."}]`

    *   **`_asap_module_type: "financial_quote"`**
        *   `_asap_module_financial_symbol`: (VARCHAR) e.g., "AAPL", "BTC-USD"
        *   `_asap_module_financial_instrument_type`: (VARCHAR) e.g., "stock", "cryptocurrency", "forex"
        *   `_asap_module_financial_current_price_decimal`: (VARCHAR or DECIMAL) Store as string for precision.
        *   `_asap_module_financial_change_amount_decimal`: (VARCHAR or DECIMAL)
        *   `_asap_module_financial_change_percent_decimal`: (VARCHAR or DECIMAL)
        *   `_asap_module_financial_market_cap_string`: (VARCHAR)
        *   `_asap_module_financial_last_updated_iso`: (DATETIME String - ISO 8601)
        *   `_asap_module_financial_chart_data_json`: (JSON for a mini-chart) `{"type": "line", "period": "1D", "data": [{"time": ..., "value": ...}]}`

    *   **`_asap_module_type: "x_post"`**
        *   `_asap_module_xpost_id_str`: (VARCHAR) The ID of the post.
        *   `_asap_module_xpost_author_username`: (VARCHAR)
        *   `_asap_module_xpost_author_display_name`: (VARCHAR)
        *   `_asap_module_xpost_text_content`: (TEXT)
        *   `_asap_module_xpost_created_at_iso`: (DATETIME String - ISO 8601)
        *   `_asap_module_xpost_metrics_json`: (JSON) `{"likes": 123, "reposts": 45, "replies": 6, "views": 1000}`
        *   `_asap_module_xpost_media_json`: (JSON Array) `[{"type": "image", "url": "..."}, {"type": "video_preview", "url": "..."}]`

    *   **`_asap_module_type: "reddit_thread"`**
        *   `_asap_module_reddit_post_id`: (VARCHAR)
        *   `_asap_module_reddit_subreddit_prefixed`: (VARCHAR) e.g., "r/technology"
        *   `_asap_module_reddit_post_title`: (TEXT) (can be `post_title` of module)
        *   `_asap_module_reddit_author_username`: (VARCHAR)
        *   `_asap_module_reddit_score_int`: (INT)
        *   `_asap_module_reddit_num_comments_int`: (INT)
        *   `_asap_module_reddit_created_at_iso`: (DATETIME String - ISO 8601)
        *   `_asap_module_reddit_top_comments_summary_json`: (JSON Array of objects, summarizing top few comments)
        *   `_asap_module_reddit_post_url`: (TEXT/URL)

    *   **`_asap_module_type: "event_item"`**
        *   (Event name can be `post_title`)
        *   `_asap_module_event_start_datetime_iso`: (DATETIME String - ISO 8601)
        *   `_asap_module_event_end_datetime_iso`: (DATETIME String - ISO 8601, optional)
        *   `_asap_module_event_timezone_str`: (VARCHAR) e.g., "America/New_York"
        *   `_asap_module_event_location_text`: (TEXT) e.g., "Online via Zoom" or "123 Main St, Anytown".
        *   `_asap_module_event_geo_coordinates_json`: (JSON) `{"latitude": 40.7128, "longitude": -74.0060}`
        *   `_asap_module_event_registration_url`: (TEXT/URL)
        *   `_asap_module_event_organizer_name`: (TEXT)

    *   **`_asap_module_type: "polymarket_card"`**
        *   (Market title/question can be `post_title` or in `post_content`)
        *   `_asap_module_polymarket_market_slug_or_id`: (VARCHAR)
        *   `_asap_module_polymarket_outcomes_data_json`: (JSON Array) `[{"name": "Yes", "price": "0.65", "probability_percent": 65, "volume_usd": "10000"}, ...]`
        *   `_asap_module_polymarket_resolution_criteria_text`: (TEXT)
        *   `_asap_module_polymarket_market_url`: (TEXT/URL)

    *   **`_asap_module_type: "image_display"`**
        *   (Image URL in `_asap_module_primary_image_url`, caption in `_asap_module_secondary_text` or `post_content`)
        *   `_asap_module_image_alt_text`: (TEXT)
        *   `_asap_module_image_source_credit_text`: (TEXT)
        *   `_asap_module_image_source_credit_url`: (TEXT/URL)

    *   **`_asap_module_type: "video_embed"`**
        *   `_asap_module_video_embed_url_or_id`: (TEXT/URL or ID for platform) e.g., YouTube video ID or full Vimeo URL.
        *   `_asap_module_video_platform_slug`: (VARCHAR) e.g., "youtube", "vimeo", "dailymotion", "self_hosted_mp4".
        *   (Title/caption can use common fields)

    *   **`_asap_module_type: "text_block"`**
        *   (Content primarily in `post_content`. This type is for manually entered rich text.)
        *   `_asap_module_text_formatting_options_json`: (JSON) e.g., `{"background_color": "#FFFFE0", "font_size_em": 1.1, "text_alignment": "center"}`

    *   **`_asap_module_type: "generic_embed"`**
        *   `_asap_module_embed_iframe_src_url`: (TEXT/URL) If it's a simple iframe.
        *   `_asap_module_embed_script_tag_url`: (TEXT/URL) If it requires a JS script.
        *   `_asap_module_embed_raw_html_code`: (LONGTEXT) For complex embed codes.
        *   `_asap_module_embed_height_px`: (INT) Suggested height.
        *   `_asap_module_embed_is_responsive`: (BOOLEAN - '0' or '1')

**5. Custom Taxonomies for `asap_module`:**

    *   **`asap_module_category`** (Optional)
        *   Slug: `asap_module_category`
        *   Hierarchical: `true`
        *   Purpose: For internal organization or if modules need categorization independent of their parent Digest's categories (e.g., "News Snippet", "Social Media Update", "Data Visualization", "Utility").
        *   Show in REST API: `true`

    *   **`asap_module_tag`** (Optional)
        *   Slug: `asap_module_tag`
        *   Hierarchical: `false`
        *   Purpose: For applying specific, fine-grained keywords to modules, perhaps for filtering a "module library" or for nuanced analytics.
        *   Show in REST API: `true`

**6. Relationships:**

*   **To Digest (`asap_digest` CPT):**
    *   Primarily through the `post_parent` field on the `asap_module` post, storing the `ID` of the parent `asap_digest`.
    *   The parent Digest may also store an ordered list of its module IDs in `_asap_digest_module_ids` (JSON array) for explicit sequencing and layout management.
*   **To Ingested Content (`wp_asap_ingested_content` table / `wp_asap_ai_processed_content` table):**
    *   The `_asap_module_source_ingested_content_id` meta field on the module links it to an entry in `wp_asap_ingested_content`. This, in turn, links to `wp_asap_ai_processed_content` for AI-enhanced data.

**7. Admin UI Considerations:**

*   The WordPress admin edit screen for `asap_module` should dynamically display the relevant "Type-Specific Custom Post Meta Fields" sections based on the value selected for `_asap_module_type`. This can be achieved with JavaScript that shows/hides meta boxes or field groups.
*   Consider using a repeatable field group or a flexible content block approach (like ACF Pro's Flexible Content) if modules themselves could contain sub-components, though this schema assumes modules are relatively atomic units.

This comprehensive schema provides a strong foundation for a highly flexible and extensible module system, catering to the diverse content types envisioned for ASAP Digest.
