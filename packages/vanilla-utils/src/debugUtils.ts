/**
 * Utility function related to logging/debugging.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

let internalDebugValue = false;

/**
 * Get or set the debug flag.
 *
 * @param newValue - The new value of debug.
 * @returns the current debug setting.
 */
export function debug(newValue?: boolean): boolean {
    if (newValue !== undefined) {
        internalDebugValue = newValue;
    }

    return internalDebugValue;
}

/**
 * Log something to console.
 * This only prints in debug mode.
 *
 * @param value - The value to log.
 */
export function logDebug(...value: any[]) {
    if (internalDebugValue) {
        // eslint-disable-next-line no-console
        console.log(...value);
    }
}

/**
 * Log an error to console.
 * This will not run in test mode _unless_ debug is set to true.
 *
 * @param value - The value to log.
 */
export function logError(...value: any[]) {
    if (!internalDebugValue && process.env.NODE_ENV === "test") {
        return;
    }
    // eslint-disable-next-line no-console
    console.error(...value);
}

/**
 * Log a warning to console.
 * This will not run in test mode _unless_ debug is set to true.
 *
 * @param value - The value to log.
 */
export function logWarning(...value: any[]) {
    if (!internalDebugValue && process.env.NODE_ENV === "test") {
        return;
    }
    // eslint-disable-next-line no-console
    console.warn(...value);
}
