import { globalValueRef, logError, logWarning } from "@vanilla/utils";
import { useEffect, useState } from "react";
import { useInterval } from "@vanilla/react-utils";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

interface ITranslations {
    [key: string]: string;
}

let translationStoreRef = globalValueRef<ITranslations | null>("translationStore", null);

let internalTranslationDebugValue = false;

declare global {
    interface Window {
        VANILLA_MISSED_TRANSLATIONS: Set<string>;
        VANILLA_MISSED_TRANSLATIONS_INITIAL?: string[];
    }
}

window.VANILLA_MISSED_TRANSLATIONS =
    window.VANILLA_MISSED_TRANSLATIONS || new Set(window.VANILLA_MISSED_TRANSLATIONS_INITIAL ?? []);

export function useMissingTranslations() {
    const [missingTranslations, setMissingTranslations] = useState(getMissingTranslationStrings());

    useInterval(() => {
        const newStrings = getMissingTranslationStrings();
        if (missingTranslations.length !== newStrings.length) {
            setMissingTranslations(newStrings);
        }
    }, 5000);
    return missingTranslations;
}

export function getMissingTranslationStrings(): string[] {
    return Array.from(window.VANILLA_MISSED_TRANSLATIONS);
}

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
    translationStoreRef.set({ ...translations });
}

/**
 * Clear all translation resources.
 */
export function clearTranslations() {
    translationStoreRef.set(null);
}

/**
 * Translate a string into the current locale.
 *
 * @param str - The string to translate.
 * @param optionsOrFallback - The default translation to use or options.
 *
 * @returns Returns the translation or the default.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function translate(str: string, defaultTranslation?: string): string;
export function translate(str: string, options: { optional?: boolean; fallback?: string }): string;
export function translate(str: string, optionsOrFallback?: string | { optional?: boolean; fallback?: string }): string {
    // Codes that begin with @ are considered literals.
    if (str.substr(0, 1) === "@") {
        return str.substr(1);
    }

    const options =
        typeof optionsOrFallback === "object"
            ? optionsOrFallback
            : {
                  fallback: optionsOrFallback,
              };

    const fallback = options.fallback !== undefined ? options.fallback : str;

    const translationStore = translationStoreRef.current();
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

    if (translationDebug() && !options.optional && !fallback.includes("☢️")) {
        window.VANILLA_MISSED_TRANSLATIONS.add(str);
        return "☢️☢️☢️" + fallback + "☢️☢️☢️";
    } else {
        return fallback;
    }
}

/**
 * The t function is an alias for translate.
 */
export const t = translate;
