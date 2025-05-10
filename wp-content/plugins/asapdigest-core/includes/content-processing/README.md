# Content Processing Pipeline

This directory contains components for implementing the Content Ingestion System (CIS-2) pipeline requirements. These components provide standardized content validation, deduplication, and quality scoring across the ASAP Digest platform.

## Components

### Content Validator (`class-content-validator.php`)

Validates content against defined rules including:
- Required fields
- Minimum content length
- Valid publish date
- Properly formatted source URL
- Content quality evaluation

```php
// Example usage
$validator = new ASAP_Digest_Content_Validator($content_data);
$is_valid = $validator->validate();

if (!$is_valid) {
    $errors = $validator->get_errors();
    // Handle errors
}

// Calculate quality score
$quality_score = $validator->calculate_quality_score();
```

### Content Deduplicator (`class-content-deduplicator.php`)

Handles deduplication through fingerprint generation and comparison:
- Generates consistent fingerprints based on normalized content
- Checks for existing fingerprints in the database
- Manages the content index for fast duplicate detection

```php
// Example usage
$deduplicator = new ASAP_Digest_Content_Deduplicator();
$fingerprint = $deduplicator->generate_fingerprint($content_data);
$duplicate_id = $deduplicator->is_duplicate($fingerprint);

if ($duplicate_id) {
    $duplicate_details = $deduplicator->get_duplicate_details($duplicate_id);
    // Handle duplicate content
}
```

### Content Processor (`class-content-processor.php`)

Central hub that ties together validation, deduplication, and storage:
- Processes content through the validation and deduplication pipeline
- Calculates quality scores
- Handles saving/updating content in the database

```php
// Example usage
$processor = new ASAP_Digest_Content_Processor();
$process_result = $processor->process($content_data);

if ($process_result['success']) {
    $save_result = $processor->save($process_result);
    // Content processed and saved successfully
} else {
    // Handle validation/duplicate errors
}
```

### Bootstrap (`bootstrap.php`)

Initializes the content processing pipeline and provides utility functions:
- Includes required component files
- Sets up action hooks for logging
- Provides `asap_digest_get_content_processor()` helper function

## Database Tables

This pipeline uses two main tables:

1. `wp_asap_ingested_content` - Stores the content data
2. `wp_asap_content_index` - Stores fingerprints for deduplication

## Implementation of CIS-2 Requirements

This pipeline implements the following CIS-2 requirements:

- ✅ **Content deduplication system** - Implemented via fingerprint generation and checking
- ✅ **Content validation rules** - Comprehensive validation for required fields, formatting, etc.
- ✅ **Content quality scoring** - Multi-factor quality scoring (completeness, recency, length, structure)

## How to Use in Your Code

```php
// Include the bootstrap file
require_once plugin_dir_path(__FILE__) . 'includes/content-processing/bootstrap.php';

// Get the processor instance
$processor = asap_digest_get_content_processor();

// Process and save content
$content_data = [
    'type' => 'article',
    'title' => 'My Article Title',
    'content' => 'Article content goes here...',
    'summary' => 'Brief summary',
    'source_url' => 'https://example.com/source',
    'publish_date' => '2025-05-01 14:30:00',
    'status' => 'published'
];

$process_result = $processor->process($content_data);

if ($process_result['success']) {
    $save_result = $processor->save($process_result);
    
    if ($save_result['success']) {
        $content_id = $save_result['content_id'];
        // Content saved successfully
    }
} else {
    // Handle validation/deduplication errors
    $errors = $process_result['errors'];
}
```

## Hooks and Actions

The pipeline fires the following action hooks:

- `asap_content_added` - When new content is added
- `asap_content_updated` - When existing content is updated
- `asap_content_deleted` - When content is deleted

Each hook receives the content ID and content data as parameters.

## Future Enhancements

Potential improvements for future versions:

1. More sophisticated deduplication with fuzzy matching
2. Advanced content quality metrics (readability, sentiment analysis)
3. Content categorization and tagging
4. Integration with AI analysis pipeline 