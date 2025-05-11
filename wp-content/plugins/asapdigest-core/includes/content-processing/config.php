<?php
/**
 * Content Processing Configuration
 *
 * Configuration constants and settings for content processing pipeline.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Whether to check URL reachability during validation (can slow down processing)
if (!defined('ASAP_VALIDATE_URL_REACHABILITY')) {
    define('ASAP_VALIDATE_URL_REACHABILITY', false);
}

// Quality score thresholds
if (!defined('ASAP_QUALITY_SCORE_EXCELLENT')) {
    define('ASAP_QUALITY_SCORE_EXCELLENT', 90);
}

if (!defined('ASAP_QUALITY_SCORE_GOOD')) {
    define('ASAP_QUALITY_SCORE_GOOD', 70);
}

if (!defined('ASAP_QUALITY_SCORE_AVERAGE')) {
    define('ASAP_QUALITY_SCORE_AVERAGE', 50);
}

if (!defined('ASAP_QUALITY_SCORE_POOR')) {
    define('ASAP_QUALITY_SCORE_POOR', 30);
}

// Minimum allowed quality score for content to be processed
if (!defined('ASAP_QUALITY_SCORE_MINIMUM')) {
    define('ASAP_QUALITY_SCORE_MINIMUM', 40);
}

// Auto-reject content if score is below this threshold
if (!defined('ASAP_QUALITY_SCORE_AUTO_REJECT')) {
    define('ASAP_QUALITY_SCORE_AUTO_REJECT', 25);
}

// Duplication handling
if (!defined('ASAP_DEDUPE_KEEP_HIGHEST_QUALITY')) {
    define('ASAP_DEDUPE_KEEP_HIGHEST_QUALITY', true);
}

// Maximum batch size for content processing
if (!defined('ASAP_CONTENT_BATCH_SIZE')) {
    define('ASAP_CONTENT_BATCH_SIZE', 50);
}

// How far back to look for duplicates in reporting (days)
if (!defined('ASAP_DUPLICATES_LOOKBACK_DAYS')) {
    define('ASAP_DUPLICATES_LOOKBACK_DAYS', 30);
}

// Content storage configuration
if (!defined('ASAP_CONTENT_STORAGE_CLASS')) {
    define('ASAP_CONTENT_STORAGE_CLASS', 'AsapDigest\\Crawler\\ContentStorage');
}

// Whether to use enhanced quality scoring
if (!defined('ASAP_USE_ENHANCED_QUALITY_SCORING')) {
    define('ASAP_USE_ENHANCED_QUALITY_SCORING', true);
}

// Whether to send notifications for poor quality content
if (!defined('ASAP_NOTIFY_POOR_QUALITY')) {
    define('ASAP_NOTIFY_POOR_QUALITY', true);
}

// Whether to use content storage integration (saves to both tables)
if (!defined('ASAP_USE_CONTENT_STORAGE_INTEGRATION')) {
    define('ASAP_USE_CONTENT_STORAGE_INTEGRATION', true);
}

// Minimum readability score threshold
if (!defined('ASAP_READABILITY_THRESHOLD')) {
    define('ASAP_READABILITY_THRESHOLD', 40);
}

// Whether to auto-generate summaries for content that doesn't have one
if (!defined('ASAP_AUTO_GENERATE_SUMMARIES')) {
    define('ASAP_AUTO_GENERATE_SUMMARIES', false);
}

// Maximum content items to keep in history per source
if (!defined('ASAP_MAX_CONTENT_HISTORY_PER_SOURCE')) {
    define('ASAP_MAX_CONTENT_HISTORY_PER_SOURCE', 100);
} 