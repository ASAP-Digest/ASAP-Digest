/**
 * API Proxy Utility
 * Reusable proxy pattern for legitimate cross-domain API scenarios
 * 
 * Use cases:
 * - Webhook forwarding to external services
 * - External API integrations (third-party services)
 * - Development/testing scenarios with different domains
 * - Server-to-server authentication bridging
 * 
 * This utility repurposes the proxy pattern that was originally created
 * for digest builder operations before GraphQL implementation.
 */

import { json } from '@sveltejs/kit';

/**
 * @typedef {Object} ProxyConfig
 * @property {string} targetUrl - The target URL to proxy to
 * @property {string} method - HTTP method (GET, POST, PUT, DELETE)
 * @property {Object} [headers] - Additional headers to include
 * @property {Object} [authConfig] - Authentication configuration
 * @property {string} [authConfig.type] - Auth type: 'bearer', 'basic', 'custom'
 * @property {string} [authConfig.token] - Auth token
 * @property {Object} [authConfig.customHeaders] - Custom auth headers
 * @property {boolean} [forwardClientAuth] - Whether to forward client auth headers
 * @property {boolean} [addServerAuth] - Whether to add server-to-server auth
 */

/**
 * Generic API proxy handler for SvelteKit server routes
 * @param {Request} request - The incoming request
 * @param {ProxyConfig} config - Proxy configuration
 * @returns {Promise<Response>} Proxied response
 */
export async function proxyApiRequest(request, config) {
  try {
    const {
      targetUrl,
      method,
      headers: configHeaders = {},
      authConfig = {},
      forwardClientAuth = true,
      addServerAuth = false
    } = config;

    // Build headers
    const headers = {
      'Content-Type': 'application/json',
      ...configHeaders
    };

    // Forward client authentication if requested
    if (forwardClientAuth) {
      const authHeader = request.headers.get('authorization');
      if (authHeader) {
        headers['Authorization'] = authHeader;
      }
    }

    // Add server-to-server authentication if requested
    if (addServerAuth) {
      const syncSecret = process.env.BETTER_AUTH_SECRET || 'development-sync-secret-v6';
      headers['X-ASAP-Sync-Secret'] = syncSecret;
      headers['X-ASAP-Request-Source'] = 'sveltekit-server';
    }

    // Add specific authentication based on config
    if (authConfig.type === 'bearer' && authConfig.token) {
      headers['Authorization'] = `Bearer ${authConfig.token}`;
    } else if (authConfig.type === 'basic' && authConfig.token) {
      headers['Authorization'] = `Basic ${authConfig.token}`;
    } else if (authConfig.type === 'custom' && authConfig.customHeaders) {
      Object.assign(headers, authConfig.customHeaders);
    }

    // Get request body for POST/PUT requests
    let body = null;
    if (['POST', 'PUT', 'PATCH'].includes(method.toUpperCase())) {
      try {
        body = JSON.stringify(await request.json());
      } catch (e) {
        // Request might not have JSON body
        console.warn('[API Proxy] No JSON body found in request');
      }
    }

    console.log('[API Proxy] Forwarding request to:', targetUrl);
    console.log('[API Proxy] Method:', method);
    console.log('[API Proxy] Headers:', headers);

    const response = await fetch(targetUrl, {
      method: method.toUpperCase(),
      headers: headers,
      body: body
    });

    console.log('[API Proxy] Response status:', response.status);

    if (!response.ok) {
      const errorText = await response.text();
      console.error('[API Proxy] Error response:', errorText);
      return json({
        success: false,
        error: `Proxy target error: ${response.status} - ${errorText}`
      }, { status: response.status });
    }

    const data = await response.json();
    console.log('[API Proxy] Success, returning data');

    return json(data);

  } catch (error) {
    console.error('[API Proxy] Fetch error:', error);
    return json({
      success: false,
      error: `Proxy error: ${error instanceof Error ? error.message : 'Unknown error'}`
    }, { status: 500 });
  }
}

/**
 * Create a webhook proxy handler
 * Useful for forwarding webhooks to external services
 * @param {string} targetWebhookUrl - Target webhook URL
 * @param {Object} [options] - Additional options
 * @returns {Function} SvelteKit request handler
 */
export function createWebhookProxy(targetWebhookUrl, options = {}) {
  return async (event) => {
    const config = {
      targetUrl: targetWebhookUrl,
      method: event.request.method,
      forwardClientAuth: false,
      addServerAuth: false,
      ...options
    };

    return await proxyApiRequest(event.request, config);
  };
}

/**
 * Create an external API proxy handler
 * Useful for integrating with third-party APIs
 * @param {string} baseUrl - Base URL of the external API
 * @param {Object} authConfig - Authentication configuration
 * @returns {Function} SvelteKit request handler
 */
export function createExternalApiProxy(baseUrl, authConfig = {}) {
  return async (event) => {
    const { url } = event;
    const pathSegments = url.pathname.split('/').filter(Boolean);
    
    // Remove the proxy route segments to get the actual API path
    const apiPath = pathSegments.slice(3).join('/'); // Assuming /api/proxy/service-name/...
    const targetUrl = `${baseUrl}/${apiPath}${url.search}`;

    const config = {
      targetUrl,
      method: event.request.method,
      authConfig,
      forwardClientAuth: false,
      addServerAuth: false
    };

    return await proxyApiRequest(event.request, config);
  };
}

/**
 * Create a development proxy handler
 * Useful for development scenarios with different domains
 * @param {string} targetDomain - Target domain for development
 * @returns {Function} SvelteKit request handler
 */
export function createDevelopmentProxy(targetDomain) {
  return async (event) => {
    const { url } = event;
    const pathSegments = url.pathname.split('/').filter(Boolean);
    
    // Remove the proxy route segments
    const apiPath = pathSegments.slice(2).join('/'); // Assuming /api/dev-proxy/...
    const targetUrl = `${targetDomain}/${apiPath}${url.search}`;

    const config = {
      targetUrl,
      method: event.request.method,
      forwardClientAuth: true,
      addServerAuth: true
    };

    return await proxyApiRequest(event.request, config);
  };
}

/**
 * Example usage patterns:
 * 
 * // Webhook forwarding
 * // /api/webhooks/stripe/+server.js
 * import { createWebhookProxy } from '$lib/utils/api-proxy.js';
 * export const POST = createWebhookProxy('https://external-service.com/webhook');
 * 
 * // External API integration
 * // /api/external/openai/+server.js
 * import { createExternalApiProxy } from '$lib/utils/api-proxy.js';
 * export const GET = createExternalApiProxy('https://api.openai.com/v1', {
 *   type: 'bearer',
 *   token: process.env.OPENAI_API_KEY
 * });
 * 
 * // Development proxy
 * // /api/dev-proxy/+server.js
 * import { createDevelopmentProxy } from '$lib/utils/api-proxy.js';
 * export const GET = createDevelopmentProxy('https://staging.example.com');
 * export const POST = createDevelopmentProxy('https://staging.example.com');
 */ 