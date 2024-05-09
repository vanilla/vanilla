import { logError, logWarning } from "@vanilla/utils";

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
    translationStore = null;
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
        // Test environment allows top level static initialization.
        const message = `Attempted to translate a value '${str}' before the translation store was initialized.`;
        switch (process.env.NODE_ENV) {
            case "production":
                logWarning(message);
                break;
            case "development":
                throw new Error(message + " Don't use t() in the top level of a file or a static property.");
            case "test":
                // Tests (like storybook and unit testing) don't need to actually bootstrap a full translation store all the time.
                break;
        }
        return fallback;
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
