/**
 * Simple logging utility.
 * Prefixes messages for easier identification.
 * 
 * @param {string} message - The message to log.
 * @param {'info'|'warn'|'error'|'debug'} [level='info'] - The log level.
 * @param {...any} optionalParams - Additional parameters to log.
 * @created 07.27.24 | 04:00 PM PDT 
 * @file-marker log.js
 */
export function log(message, level = 'info', ...optionalParams) {
    const prefix = '[ASAP Digest]'; // Consistent prefix
    const timestamp = new Date().toISOString();
    const logMessage = `${prefix} ${timestamp} [${level.toUpperCase()}] ${message}`;

    switch (level) {
        case 'error':
            console.error(logMessage, ...optionalParams);
            break;
        case 'warn':
            console.warn(logMessage, ...optionalParams);
            break;
        case 'debug':
            // Only log debug messages in development
            if (import.meta.env.DEV) { 
                console.debug(logMessage, ...optionalParams);
            }
            break;
        case 'info':
        default:
            console.log(logMessage, ...optionalParams);
            break;
    }
} 