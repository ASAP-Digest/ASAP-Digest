/**
 * Debug utility for testing the GraphQL content fetcher
 */

import { browser } from '$app/environment';
import { fetchCachedContent } from '$lib/api/content-fetcher.js';
import { getContentTypeDetails } from '$lib/api/content-service.js';

/**
 * Test the GraphQL content fetcher with all content types
 * @param {Object} options - Test options
 * @param {boolean} [options.bypassCache=true] - Whether to bypass cache
 * @param {string} [options.search=''] - Search query
 * @param {number} [options.limit=5] - Item limit
 */
export async function testContentFetcher(options = {}) {
  if (!browser) return;

  const { 
    bypassCache = true, 
    search = '', 
    limit = 5 
  } = options;

  console.group('🧪 Testing GraphQL Content Fetcher');
  console.log('Options:', options);

  const contentTypes = getContentTypeDetails().map(type => type.id);
  const results = {};
  const errors = {};

  // Test each content type
  for (const type of contentTypes) {
    try {
      console.log(`Fetching ${type}...`);
      
      const result = await fetchCachedContent(
        type, 
        { 
          limit, 
          search: search || undefined 
        }, 
        { 
          bypassCache 
        }
      );
      
      results[type] = result;
      console.log(`✅ ${type}: ${result.items.length} items fetched`);
      
      if (result.items.length > 0) {
        console.log(`📋 Sample item:`, result.items[0]);
      }
    } catch (err) {
      // Fix: Properly type the error
      const errorMessage = err instanceof Error ? err.message : String(err);
      errors[type] = errorMessage;
      console.error(`❌ ${type} error:`, err);
    }
  }

  // Summary
  console.log('📊 Summary:');
  for (const type of contentTypes) {
    const count = results[type]?.items.length || 0;
    const hasMore = results[type]?.pagination.hasNextPage || false;
    console.log(`${type}: ${count} items${hasMore ? ' (has more)' : ''}`);
  }

  if (Object.keys(errors).length > 0) {
    console.warn('⚠️ Errors encountered:', errors);
  }

  console.groupEnd();

  return { results, errors };
}

/**
 * Test the image optimization utility
 * @param {Object} options - Test options
 * @param {string} [options.contentType='article'] - Content type to test with
 * @param {number} [options.limit=2] - Number of items to test
 */
export async function testImageOptimization(options = {}) {
  if (!browser) return;

  const { contentType = 'article', limit = 2 } = options;

  console.group('🖼️ Testing Image Optimization');
  
  try {
    // Get items with images
    const result = await fetchCachedContent(
      contentType, 
      { limit: 10 }, 
      { bypassCache: true }
    );

    // Filter to items with images
    const itemsWithImages = result.items.filter(item => item.imageUrl);
    
    if (itemsWithImages.length === 0) {
      console.log('⚠️ No items with images found. Try a different content type.');
      console.groupEnd();
      return;
    }

    // Log image optimization for the items
    const sample = itemsWithImages.slice(0, limit);
    console.log(`🔍 Testing ${sample.length} images from ${contentType}...`);

    sample.forEach(item => {
      console.log(`📷 Original image: ${item.imageUrl}`);
      console.log(`🔍 Optimized image: ${item.optimizedImageUrl}`);
      console.log(`🔎 Thumbnail: ${item.thumbnailUrl}`);
    });

    console.log('✅ Image optimization test complete');
  } catch (error) {
    console.error('❌ Image optimization test error:', error);
  }

  console.groupEnd();
}

/**
 * Run all tests
 */
export function runAllTests() {
  console.group('🧪 Running all GraphQL and content integration tests');
  
  // Run basic test with default options
  testContentFetcher().then(() => {
    // Test with search query
    return testContentFetcher({ search: 'test', limit: 3 });
  }).then(() => {
    // Test image optimization
    return testImageOptimization();
  }).then(() => {
    console.log('🎉 All tests completed');
    console.groupEnd();
  }).catch(error => {
    console.error('❌ Test suite error:', error);
    console.groupEnd();
  });
}

// Auto-run tests if this module is loaded directly
if (browser && new URL(window.location.href).searchParams.get('test') === 'true') {
  console.log('🚀 Auto-running content integration tests...');
  setTimeout(runAllTests, 1000);
} 