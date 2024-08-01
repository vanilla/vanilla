/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalValueRef } from "@vanilla/utils";

export interface ILocale {
    localeID: string;
    localeKey: string;
    regionalKey: string;
    displayNames: {
        [localeKey: string]: string;
    };
    translationService: string | null;
}

let currentLocaleRef = globalValueRef("currentLocale", "en");
let localeStoreRef = globalValueRef<ILocale[]>("localStore", []);
let callbacksRef = globalValueRef<Array<() => void>>("localStoreCallbacks", []);

/**
 * Get the available locales.
 */
export function getLocales(): ILocale[] {
    return localeStoreRef.current();
}

/**
 * Register a handler for if the locales change.
 * @param callback
 */
export function onLocaleChange(callback: () => void) {
    callbacksRef.current().push(callback);
}

/**
 * Set the current locale.
 */
export function setCurrentLocale(localeKey: string) {
    currentLocaleRef.set(localeKey);
    callbacksRef.current().forEach((callback) => callback());
}

/**
 * Get the current locale.
 */
export function getCurrentLocale() {
    return currentLocaleRef.current();
}

/**
 * Get the current locale in format accepted by Javascript localization functions.
 *
 * PHP canonicalized locales use `_` for the regional separator.
 * Javascript uses `-`.
 */
export function getJSLocaleKey() {
    return currentLocaleRef.current().replace("_", "-");
}

/**
 * Load a group of locales.
 */
export function loadLocales(locales: ILocale[]) {
    localeStoreRef.set([...localeStoreRef.current(), ...locales]);
    callbacksRef.current().forEach((callback) => callback());
}

/**
 * Clear the loaded locales.
 */
export function clearLocales() {
    localeStoreRef.set([]);
    callbacksRef.current().forEach((callback) => callback());
}
