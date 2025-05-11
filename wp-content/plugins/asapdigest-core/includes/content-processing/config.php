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