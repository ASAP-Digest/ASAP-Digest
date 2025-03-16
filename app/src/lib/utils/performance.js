/**
 * Performance monitoring utility for ASAP Digest
 * Tracks key performance metrics and reports them to analytics
 */

// Constants for performance thresholds (in milliseconds)
const THRESHOLDS = {
    FCP: 1800, // First Contentful Paint
    LCP: 2500, // Largest Contentful Paint
    FID: 100,  // First Input Delay
    CLS: 0.1,  // Cumulative Layout Shift (score, not ms)
    TTI: 3800, // Time to Interactive
    TBT: 200,  // Total Blocking Time
};

// Initialize performance monitoring
export function initPerformanceMonitoring() {
    if (typeof window === 'undefined' || !('performance' in window)) {
        return;
    }

    // Report Web Vitals
    try {
        if ('PerformanceObserver' in window) {
            // Track First Contentful Paint (FCP)
            const fcpObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    if (entry.name === 'first-contentful-paint') {
                        console.log(`[Performance] FCP: ${entry.startTime.toFixed(0)}ms`);
                        reportMetric('FCP', entry.startTime, THRESHOLDS.FCP);
                        fcpObserver.disconnect();
                    }
                });
            });
            fcpObserver.observe({ type: 'paint', buffered: true });

            // Track Largest Contentful Paint (LCP)
            const lcpObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                if (lastEntry) {
                    console.log(`[Performance] LCP: ${lastEntry.startTime.toFixed(0)}ms`);
                    reportMetric('LCP', lastEntry.startTime, THRESHOLDS.LCP);
                }
            });
            lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });

            // Track First Input Delay (FID)
            const fidObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    if (entry.processingStart && entry.startTime) {
                        const fid = entry.processingStart - entry.startTime;
                        console.log(`[Performance] FID: ${fid.toFixed(1)}ms`);
                        reportMetric('FID', fid, THRESHOLDS.FID);
                        fidObserver.disconnect();
                    }
                });
            });
            fidObserver.observe({ type: 'first-input', buffered: true });

            // Track Cumulative Layout Shift (CLS)
            let clsValue = 0;
            let clsEntries = [];
            const clsObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    // Only count layout shifts without recent user input
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                        clsEntries.push(entry);
                    }
                });

                console.log(`[Performance] Current CLS: ${clsValue.toFixed(3)}`);
                reportMetric('CLS', clsValue, THRESHOLDS.CLS);
            });
            clsObserver.observe({ type: 'layout-shift', buffered: true });

            // Track long tasks for Total Blocking Time (TBT)
            let totalBlockingTime = 0;
            const longTaskObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    // Any task over 50ms is considered "blocking"
                    const blockingTime = entry.duration - 50;
                    if (blockingTime > 0) {
                        totalBlockingTime += blockingTime;
                        console.log(`[Performance] Long task: ${entry.duration.toFixed(0)}ms, TBT: ${totalBlockingTime.toFixed(0)}ms`);
                        reportMetric('TBT', totalBlockingTime, THRESHOLDS.TBT);
                    }
                });
            });
            longTaskObserver.observe({ type: 'longtask', buffered: true });

            // Track navigation and resource timing
            const navigationObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    if (entry.entryType === 'navigation') {
                        const navData = {
                            dns: entry.domainLookupEnd - entry.domainLookupStart,
                            tcp: entry.connectEnd - entry.connectStart,
                            ttfb: entry.responseStart - entry.requestStart,
                            download: entry.responseEnd - entry.responseStart,
                            domInteractive: entry.domInteractive,
                            domComplete: entry.domComplete,
                            loadEvent: entry.loadEventEnd - entry.loadEventStart,
                            totalTime: entry.loadEventEnd
                        };

                        console.log('[Performance] Navigation timing:', navData);

                        // Report Time to Interactive (TTI) approximation
                        reportMetric('TTI', entry.domInteractive, THRESHOLDS.TTI);
                    }
                });
            });
            navigationObserver.observe({ type: 'navigation', buffered: true });

            // Track resource loading performance
            const resourceObserver = new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach((entry) => {
                    // Only log resources that took longer than 500ms to load
                    if (entry.duration > 500) {
                        console.log(`[Performance] Slow resource: ${entry.name} - ${entry.duration.toFixed(0)}ms`);
                    }
                });
            });
            resourceObserver.observe({ type: 'resource', buffered: true });
        }

        // Track page transitions
        if (typeof document !== 'undefined') {
            let pageLoadStart = performance.now();

            document.addEventListener('sveltekit:start', () => {
                pageLoadStart = performance.now();
                console.log('[Performance] SvelteKit navigation started');
            });

            document.addEventListener('sveltekit:end', () => {
                const navigationTime = performance.now() - pageLoadStart;
                console.log(`[Performance] SvelteKit navigation completed in ${navigationTime.toFixed(0)}ms`);
                reportMetric('PageTransition', navigationTime, 300);
            });
        }
    } catch (error) {
        console.error('[Performance] Error setting up performance monitoring:', error);
    }
}

// Report a metric to analytics and console
function reportMetric(name, value, threshold) {
    // Determine if the metric is good or needs improvement
    const status = value <= threshold ? 'good' : 'needs-improvement';

    // Log to console with color coding
    const color = status === 'good' ? 'green' : 'orange';
    console.log(`%c[Performance] ${name}: ${value.toFixed(1)}ms (${status})`, `color: ${color}`);

    // Send to analytics if available
    if (window.gtag) {
        window.gtag('event', 'performance_metric', {
            metric_name: name,
            metric_value: value.toFixed(1),
            metric_status: status,
            metric_threshold: threshold,
            non_interaction: true
        });
    }

    // Store in localStorage for debugging
    try {
        const metrics = JSON.parse(localStorage.getItem('performance_metrics') || '{}');
        metrics[name] = { value, timestamp: Date.now(), status };
        localStorage.setItem('performance_metrics', JSON.stringify(metrics));
    } catch (e) {
        // Ignore storage errors
    }
}

// Get all stored performance metrics
export function getPerformanceMetrics() {
    try {
        return JSON.parse(localStorage.getItem('performance_metrics') || '{}');
    } catch (e) {
        return {};
    }
}

// Clear stored performance metrics
export function clearPerformanceMetrics() {
    localStorage.removeItem('performance_metrics');
}

// Check if the current page is performing well
export function isPerformingWell() {
    const metrics = getPerformanceMetrics();
    let totalMetrics = 0;
    let goodMetrics = 0;

    Object.entries(metrics).forEach(([name, data]) => {
        totalMetrics++;
        if (data.status === 'good') {
            goodMetrics++;
        }
    });

    // Return true if at least 70% of metrics are good
    return totalMetrics > 0 ? (goodMetrics / totalMetrics >= 0.7) : true;
}

// Export thresholds for reference
export { THRESHOLDS }; 