// @file-marker CrawlerAPI
// SvelteKit API client for Content Ingestion & Indexing System

/**
 * Fetch all content sources
 * @returns {Promise<Array>} Array of sources
 */
export async function fetchSources() {
  const res = await fetch('/asap/v1/crawler/sources', { credentials: 'include' });
  if (!res.ok) throw new Error('Failed to fetch sources');
  const data = await res.json();
  return data.sources;
}

/**
 * Add a new content source
 * @param {Object} source - Source data
 * @returns {Promise<Object>} Result
 */
export async function addSource(source) {
  const res = await fetch('/asap/v1/crawler/sources', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(source)
  });
  if (!res.ok) throw new Error('Failed to add source');
  return await res.json();
}

/**
 * Update a content source
 * @param {number} id - Source ID
 * @param {Object} source - Source data
 * @returns {Promise<Object>} Result
 */
export async function updateSource(id, source) {
  const res = await fetch(`/asap/v1/crawler/sources/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(source)
  });
  if (!res.ok) throw new Error('Failed to update source');
  return await res.json();
}

/**
 * Get moderation queue
 * @returns {Promise<Array>} Array of pending posts
 */
export async function getModerationQueue() {
  const res = await fetch('/asap/v1/crawler/queue', { credentials: 'include' });
  if (!res.ok) throw new Error('Failed to fetch moderation queue');
  const data = await res.json();
  return data.queue;
}

/**
 * Approve a pending content item
 * @param {number} id - Post ID
 * @returns {Promise<Object>} Result
 */
export async function approveContent(id) {
  const res = await fetch(`/asap/v1/crawler/queue/approve/${id}`, {
    method: 'POST',
    credentials: 'include'
  });
  if (!res.ok) throw new Error('Failed to approve content');
  return await res.json();
}

/**
 * Reject a pending content item
 * @param {number} id - Post ID
 * @returns {Promise<Object>} Result
 */
export async function rejectContent(id) {
  const res = await fetch(`/asap/v1/crawler/queue/reject/${id}`, {
    method: 'POST',
    credentials: 'include'
  });
  if (!res.ok) throw new Error('Failed to reject content');
  return await res.json();
}

/**
 * Get crawler metrics
 * @returns {Promise<Object>} Metrics data
 */
export async function getMetrics() {
  const res = await fetch('/asap/v1/crawler/metrics', { credentials: 'include' });
  if (!res.ok) throw new Error('Failed to fetch metrics');
  return await res.json();
} 