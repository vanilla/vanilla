import { logError } from "@vanilla/utils";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

interface ITranslations {
    [key: string]: string;
}

let translationStore: ITranslations | null = null;

let internalTranslationDebugValue = false;

/**
 * Get or set the debug flag.
 *
 * @param newValue - The new value of debug.
 * @returns the current debug setting.
 */
export function translationDebug(newValue?: boolean): boolean {
    if (newValue !== undefined) {
        internalTranslationDebugValue = newValue;
    }

    return internalTranslationDebugValue;
}

/**
 * Load a set of key value pairs as translation resources.
 */
export function loadTranslations(translations: ITranslations) {
    translationStore = { ...translations };
}

/**
 * Clear all translation resources.
 */
export function clearTranslations() {
    translationStore = {};
}

/**
 * Translate a string into the current locale.
 *
 * @param str - The string to translate.
 * @param defaultTranslation - The default translation to use.
 *
 * @returns Returns the translation or the default.
 */
export function translate(str: string, defaultTranslation?: string): string {
    // Codes that begin with @ are considered literals.
    if (str.substr(0, 1) === "@") {
        return str.substr(1);
    }

    const fallback = defaultTranslation !== undefined ? defaultTranslation : str;

    if (!translationStore) {
        if (process.env.NODE_ENV !== "test") {
            logError("Attempted to translate a value before the translation store was initialized");
            return "☢️☢️☢️" + fallback + "☢️☢️☢️";
        } else {
            return fallback;
        }
    }

    if (translationStore[str] !== undefined) {
        return translationStore[str];
    }

    if (translationDebug()) {
        return "☢️☢️☢️" + fallback + "☢️☢️☢️";
    } else {
        return fallback;
    }
}

/**
 * The t function is an alias for translate.
 */
export const t = translate;
