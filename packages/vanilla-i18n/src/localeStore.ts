/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface ILocale {
    localeID: string;
    localeKey: string;
    regionalKey: string;
    displayNames: {
        [localeKey: string]: string;
    };
    translationService: string | null;
}

let currentLocale = "en";
let localeStore: ILocale[] = [];
let callbacks: Array<() => void> = [];

/**
 * Get the available locales.
 */
export function getLocales(): ILocale[] {
    return localeStore;
}

/**
 * Register a handler for if the locales change.
 * @param callback
 */
export function onLocaleChange(callback: () => void) {
    callbacks.push(callback);
}

/**
 * Set the current locale.
 */
export function setCurrentLocale(localeKey: string) {
    currentLocale = localeKey;
    callbacks.forEach((callback) => callback());
}

/**
 * Get the current locale.
 */
export function getCurrentLocale() {
    return currentLocale;
}

/**
 * Get the current locale in format accepted by Javascript localization functions.
 *
 * PHP canonicalized locales use `_` for the regional separator.
 * Javascript uses `-`.
 */
export function getJSLocaleKey() {
    return currentLocale.replace("_", "-");
}

/**
 * Load a group of locales.
 */
export function loadLocales(locales: ILocale[]) {
    localeStore = [...localeStore, ...locales];
    callbacks.forEach((callback) => callback());
}

/**
 * Clear the loaded locales.
 */
export function clearLocales() {
    localeStore = [];
    callbacks.forEach((callback) => callback());
}
