/**
 * Utility for standardized logging with timestamps and severity levels
 * @module log
 */

/**
 * Log levels
 * @readonly
 * @enum {string}
 */
export const LogLevel = {
    INFO: 'info',
    WARN: 'warn',
    ERROR: 'error',
    DEBUG: 'debug'
};

/**
 * Log a message with timestamp and optional severity level
 * @param {string} message - The message to log
 * @param {string} [level='info'] - Log level: 'info', 'warn', 'error', 'debug' (case insensitive)
 */
export function log(message, level = 'info') {
    // Normalize level to lowercase for consistency
    const normalizedLevel = (level || 'info').toLowerCase();
    
    const timestamp = new Date().toISOString();
    const prefix = '[ASAP Digest]';
    
    // Create the formatted message
    const formattedMessage = `${prefix} ${timestamp} [${normalizedLevel.toUpperCase()}] ${message}`;
    
    // Log based on level
    switch (normalizedLevel) {
        case 'warn':
            console.warn(formattedMessage);
            break;
        case 'error':
            console.error(formattedMessage);
            break;
        case 'debug':
            console.debug(formattedMessage);
            break;
        default:
            console.log(formattedMessage);
            break;
    }
    
    return formattedMessage; // Return for testing purposes
}

/**
 * Special logging helper for timing operations
 * @param {string} operation - Name of the operation being timed
 * @param {Function} func - The function to time (can be async)
 * @returns {Promise<any>} The result of the function
 */
export async function timeOperation(operation, func) {
    const start = performance.now();
    try {
        const result = await func();
        const duration = (performance.now() - start).toFixed(2);
        log(`${operation} completed in ${duration}ms`, 'info');
        return result;
    } catch (error) {
        const duration = (performance.now() - start).toFixed(2);
        // Apply local-variable-type-safety-protocol with proper type guard
        const errorMessage = error instanceof Error ? error.message : String(error);
        log(`${operation} failed after ${duration}ms: ${errorMessage}`, 'error');
        throw error;
    }
}

export default log; 