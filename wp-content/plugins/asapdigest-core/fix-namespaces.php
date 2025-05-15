<?php
/**
 * Namespace Standardization Script
 * Converts all "AsapDigest" namespaces to "ASAPDigest" for consistency
 */

// Files that need namespace updating
$files_to_update = [
    // AI Components
    'includes/ai/adapters/class-anthropic-adapter.php',
    'includes/ai/adapters/class-huggingface-adapter.php',
    'includes/ai/adapters/class-openai-adapter.php',
    'includes/ai/class-ai-service-manager.php',
    'includes/ai/diagnostics/class-ai-debugger.php',
    'includes/ai/diagnostics/class-connection-tester.php',
    'includes/ai/diagnostics/class-error-classifier.php',
    'includes/ai/interfaces/class-ai-debuggable.php',
    'includes/ai/interfaces/class-ai-provider-adapter.php',
    'includes/ai/interfaces/interface-ai-provider.php',
    
    // Crawler Components
    'includes/class-content-processor.php',
    'includes/class-content-source-manager.php',
    'includes/class-content-storage.php',
    'includes/crawler/adapters/class-api-adapter.php',
    'includes/crawler/adapters/class-rss-adapter.php',
    'includes/crawler/adapters/class-scraper-adapter.php',
    'includes/crawler/class-content-crawler.php',
    'includes/crawler/class-content-source-manager.php',
    'includes/crawler/class-content-storage.php',
    'includes/crawler/interfaces/class-content-source-adapter.php',
];

// Plugin root directory
$plugin_dir = __DIR__;

echo "Starting namespace standardization...\n";
$changes_count = 0;

foreach ($files_to_update as $file_path) {
    $full_path = $plugin_dir . '/' . $file_path;
    
    // Check if file exists
    if (!file_exists($full_path)) {
        echo "WARNING: File not found: {$file_path}\n";
        continue;
    }
    
    // Get file contents
    $content = file_get_contents($full_path);
    if ($content === false) {
        echo "ERROR: Could not read file: {$file_path}\n";
        continue;
    }
    
    // Replace namespace declaration
    $updated_content = preg_replace('/namespace\s+AsapDigest\\\/i', 'namespace ASAPDigest\\', $content);
    
    // Also update any uses of AsapDigest within the file
    $updated_content = preg_replace('/use\s+AsapDigest\\\/i', 'use ASAPDigest\\', $updated_content);
    $updated_content = preg_replace('/new\s+\\\\AsapDigest\\\/i', 'new \\ASAPDigest\\', $updated_content);
    $updated_content = preg_replace('/extends\s+\\\\AsapDigest\\\/i', 'extends \\ASAPDigest\\', $updated_content);
    $updated_content = preg_replace('/implements\s+\\\\AsapDigest\\\/i', 'implements \\ASAPDigest\\', $updated_content);
    
    // Write updated content back to file
    if ($content !== $updated_content) {
        if (file_put_contents($full_path, $updated_content) !== false) {
            echo "Updated: {$file_path}\n";
            $changes_count++;
        } else {
            echo "ERROR: Could not write to file: {$file_path}\n";
        }
    } else {
        echo "No changes needed for: {$file_path}\n";
    }
}

echo "Complete! Updated {$changes_count} files.\n"; 