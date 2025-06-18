/**
 * Application functions for interop between Components in different packages.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import gdn from "@library/gdn";
import { globalValueRef, logError, PromiseOrNormalCallback, RecordID } from "@vanilla/utils";
import { ensureScript } from "@vanilla/dom-utils";
import { sprintf } from "sprintf-js";
import { isLayoutRoute } from "@library/features/Layout/LayoutPage.paths";

// Re-exported for backwards compatibility
export { t, translate } from "@vanilla/i18n";

// Absolute path pattern
const ABSOLUTE_PATH_REGEX = /^\s*(mailto:|((https?:)?\/\/))/i;

export function isDevSite(): boolean {
    const devHostnames = ["dev.vanilla.local", "test.vanilla.local", "vanilla.local"];

    return devHostnames.includes(window.location.hostname);
}

export function isQASite(): boolean {
    const qaHostnames = [
        "qa1.vanilla.community",
        "qa2.vanilla.community",
        "qa3.vanilla.community",
        "qa-1.vanillatesting.com",
        "qa-2.vanillatesting.com",
        "qa-3.vanillatesting.com",
    ];
    return qaHostnames.includes(window.location.hostname);
}
/**
 * Get a piece of metadata passed from the server.
 *
 * @param key - The key to lookup.
 * @param defaultValue - A fallback value in case the key cannot be found.
 *
 * @returns Returns a meta value or the default value.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function getMeta(key: string, defaultValue?: any) {
    if (!gdn.meta) {
        return defaultValue;
    }

    const parts = key.split(".");
    let haystack = gdn.meta;

    for (const part of parts) {
        if (!haystack.hasOwnProperty(part)) {
            return defaultValue;
        }
        haystack = haystack[part];
    }
    return haystack;
}

/**
 * Set a piece of metadata. This will override what was passed from the server.
 *
 * @param key - The key to store under.
 * @param value - The value to set.
 */
export function setMeta(key: string, value: any) {
    const parts = key.split(".");
    const last = parts.pop();

    if (!last) {
        throw new Error(`Unable to set meta value ${key}. ${last} is not a valid object key.`);
    }

    let haystack = gdn.meta;

    for (const part of parts) {
        if (haystack[part] === null || typeof haystack[part] !== "object") {
            haystack[part] = {};
        }
        haystack = haystack[part];
    }
    haystack[last] = value;
}

/**
 * @see https://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-a-url#answer-5717133
 * @param url
 */
export function isURL(url: string): boolean {
    let constructed;

    try {
        constructed = new URL(url);
    } catch (_) {
        return false;
    }

    return constructed.protocol === "http:" || constructed.protocol === "https:";
}

/**
 * Determine if a string is an allowed URL.
 *
 * In the future this may be extended to check if we want to whitelist/blacklist various URLs.
 *
 * @param input - The string to check.
 */
export function isAllowedUrl(input: string): boolean {
    return isURL(input);
}

/**
 * Normalize the URL with a prepended http if there isn't one.
 */
export function normalizeUrl(urlToNormalize: string) {
    const result = urlToNormalize.match(/^https?:\/\//) ? urlToNormalize : "http://" + urlToNormalize;
    return result;
}

export interface ISiteSection {
    basePath: string;
    contentLocale: string;
    sectionGroup: string;
    sectionID: string;
    name: string;
    apps: Record<string, boolean>;
    attributes: Record<string, any>;
}

/**
 * Get the current site section.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function getSiteSection(): ISiteSection {
    return getMeta("siteSection");
}

export function getDefaultSiteSection(): ISiteSection {
    return getMeta("defaultSiteSection", getSiteSection());
}

export function hasMultipleSiteSections(): boolean {
    return getMeta("siteSectionSlugs", []).length > 0;
}

/**
 * Get the enabled post types
 */
export function getEnabledPostTypes(): string[] {
    return getMeta("postTypes", ["discussion"]);
}

/**
 * Format a URL in the format passed from the controller.
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used in the APP.
 */
export function formatUrl(path: string, withDomain: boolean = false): string {
    // Test if this is an absolute path
    if (ABSOLUTE_PATH_REGEX.test(path)) {
        // Attempt to inject a site section into the url if we can.
        const absoluteUrl = tryInjectSiteSectionIntoAbsoluteUrl(path);
        return absoluteUrl;
    }

    // Subcommunity slug OR subcommunity
    let siteRoot = getMeta("context.basePath", "");

    // Urls starting with a tilde, indicate that we should NOT be attaching a site section automatically.
    if (path.startsWith("~")) {
        path = path.replace(/^~/, "");
        siteRoot = getMeta("context.host", "");
    }

    // The context paths that come down are expect to have no / at the end of them.
    // Normally a domain like so: https://someforum.com
    // When we don't have that we want to fallback to "" so that our path with a / can get passed.
    const urlBase = withDomain ? window.location.origin + siteRoot : siteRoot;
    return urlBase + path;
}

/**
 * Try to inject a site section slug into an absolute URL meeting the following criteria
 *
 * - The URL is on the same Vanilla site as us (not just domain, in a hub/node it must be our node).
 * - The URL path does not already start with a site section already. (All site sections paths are injected from the backend).
 * - The URL path has content (eg. not empty or just /).
 *
 * @param url A fully qualified URL.
 * @returns Another fully qualified URL. This could be an original or a modification.
 */
function tryInjectSiteSectionIntoAbsoluteUrl(url: string): string {
    // First see that it's one of our own urls.
    const expectedHost = window.location.origin + getMeta("context.host", "");
    if (!url.startsWith(expectedHost)) {
        // Nothing to do. It's not our url.
        return url;
    }

    // No trim off the host from the url.
    const path = url.replace(expectedHost, "");
    if (path.length <= 1) {
        // Don't muck around with no path or just `/`.
        return url;
    }
    const siteSectionSlugs = getMeta("siteSectionSlugs", []);

    for (const siteSectionSlug of siteSectionSlugs) {
        if (path.match(new RegExp(`^${siteSectionSlug}(/.*)?$`, "gi"))) {
            // We have a site section slug.
            return url;
        }
    }

    // KB links already have a sitesection slug, or shouldn't have one at all
    if (path.startsWith("/kb/")) {
        return url;
    }

    // We don't have a site section slug.
    // Take our stripped path, and make a url out of it.
    return formatUrl(path, true);
}

/**
 * Generate a URL from the site's web root.
 *
 * No site section will be included.
 */
export function siteUrl(path: string): string {
    // Test if this is an absolute path
    if (ABSOLUTE_PATH_REGEX.test(path)) {
        return path;
    }

    // The context paths that come down are expect to have no / at the end of them.
    // Normally a domain like so: https://someforum.com
    // When we don't have that we want to fallback to "" so that our path with a / can get passed.
    let urlBase = window.location.origin;

    const host = getMeta("context.host", "");
    if (!path.startsWith(host)) {
        urlBase += host;
    }
    return urlBase + path;
}

/**
 * Extract relative URL part from absolute full URL.
 *
 * @param fullUrl - The absolute url to transform.
 *
 * @returns Returns a URL that can be used in the APP.
 */
export function getRelativeUrl(fullUrl: string): string {
    const urlBase = window.location.origin + getMeta("context.basePath", "");
    return fullUrl.replace(urlBase, "").split("#")[0];
}

/**
 * Create the URL of an asset of the site.
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used for a static asset.
 */
export function assetUrl(path: string): string {
    // Test if this is an absolute path
    if (ABSOLUTE_PATH_REGEX.test(path)) {
        return path;
    }
    // The context paths that come down are expect to have no / at the end of them.
    // Normally a domain like so: https://someforum.com
    // When we don't have that we want to fallback to "" so that our path with a / can get passed.
    const staticPathFolder = getMeta("context.staticPathFolder", "");
    const urlFormat = getMeta("context.assetPath", "");
    return staticPathFolder + urlFormat + path;
}

/**
 * Create the URL to the theme's asset folder
 *
 * @param path - The path to format.
 *
 * @returns Returns a URL that can be used for a static asset.
 */
export function themeAsset(path: string): string {
    const themeKey = getMeta("ui.themeKey");
    return assetUrl(`/themes/${themeKey}/${path}`);
}

const _readyHandlersRef = globalValueRef<PromiseOrNormalCallback[]>("readyHandlers", []);

/**
 * Register a callback that executes when the document and the core libraries are ready to use.
 *
 * @param callback - The function to call. This can return a Promise but doesn't have to.
 */
export function onReady(callback: PromiseOrNormalCallback) {
    _readyHandlersRef.current().push(callback);
}

/**
 * Execute all of the registered events in order.
 *
 * @returns A Promise when the events have all fired.
 */
export function _executeReady(before?: () => void | Promise<void>): Promise<any[]> {
    return new Promise((resolve) => {
        const handlerPromises = _readyHandlersRef.current().map((handler) => {
            let result = handler();
            if (result instanceof Promise) {
                result.catch((err) => logError(err));
            }
            return result;
        });
        const exec = () => {
            void before?.();
            return Promise.all(handlerPromises)
                .then(resolve)
                .finally(() => {
                    const contentEvent = new CustomEvent("X-VanillaReady", { bubbles: true, cancelable: false });
                    document.dispatchEvent(contentEvent);

                    // we'll dispatch this event only on legacy pages, as for layout/knowledge pages we dispatch it from <AnalyticsData/>
                    if (getMeta("trackLegacyPageViews", false)) {
                        document.dispatchEvent(new CustomEvent("X-PageView", { bubbles: true, cancelable: false }));
                    }
                });
        };

        if (document.readyState !== "loading") {
            return exec();
        } else {
            document.addEventListener("DOMContentLoaded", exec);
        }
    });
}

/**
 * Execute a callback when a piece of DOM content is ready to be operated on.
 *
 * This is similar to onReady() but also includes content that is added dynamically (ex. AJAX).
 * Note that this function is meant to bridge the non-react parts of the application with react.
 *
 * @param callback - The callback to execute.
 */
export function onContent(callback: (event: CustomEvent) => void) {
    document.addEventListener("X-DOMContentReady", callback);
}

/**
 * Remove a listener registered with `onContent`.
 */
export function removeOnContent(callback: (event: CustomEvent) => void) {
    document.removeEventListener("X-DOMContentReady", callback);
}

/**
 * Encode a username the same way our backend does.
 *
 * Notably there is a long-standing issue where names certain characters that our dispatcher matches on need to be double encoded.
 * @param username
 */
function encodeUserName(username?: string): string {
    // Matching existing backend logic.
    const specialEncoded = username?.replace("/", "%2f").replace("&", "%26") ?? "";
    return encodeURIComponent(specialEncoded);
}

/**
 * Make a URL to a user's profile.
 */
export function makeProfileUrl(userID?: RecordID, username?: string) {
    let userPath = "/profile/";
    if (userID) userPath += `${userID}/`;
    if (username) userPath += `${encodeUserName(username)}`;

    return formatUrl(userPath, true);
}

/**
 * Make a URL to a user's discussions.
 */
export function makeProfileDiscussionsUrl(userID?: RecordID, username?: string) {
    let discussionsPath = "/profile/discussions/";
    if (userID) discussionsPath += `${userID}/`;
    if (username) discussionsPath += `${encodeUserName(username)}`;

    return formatUrl(discussionsPath, true);
}

/**
 * Make a URL to a user's comments.
 */
export function makeProfileCommentsUrl(userID?: RecordID, username?: string) {
    let commentsPath = "/profile/comments/";
    if (userID) commentsPath += `${userID}/`;
    if (username) commentsPath += `${encodeUserName(username)}`;

    return formatUrl(commentsPath, true);
}

interface IRecaptcha {
    execute: (string) => string;
}

/**
 * Ensure that we have loaded the rec
 */
export async function ensureReCaptcha(): Promise<IRecaptcha | null> {
    const siteKey = getMeta("reCaptchaKey");
    if (!siteKey) {
        return null;
    }
    await ensureScript(`https://www.google.com/recaptcha/api.js?render=${siteKey}`);

    return { execute: (siteKey) => window.grecaptcha.execute(siteKey) };
}

declare global {
    interface Window {
        grecaptcha: IRecaptcha;
    }
}

/**
 * Translation helper for accessible labels, because <Translate/> doesn't return as string
 * @param template - the template for the string (must be translated ahead of time)
 * @param variable - the variable to insert in the template
 */
export function accessibleLabel(template: string, variable: string[]) {
    return sprintf(template, variable);
}

export type ImageSourceSet = Record<RecordID, string>;

/**
 * This function creates a source set value from an object where the key indicates the
 * the width and the corresponding values are the image URL.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export function createSourceSetValue(sourceSet: ImageSourceSet): string {
    return (
        Object.entries(sourceSet ?? {})
            // Filter out any values which are empty
            .filter(([_, value]) => value)
            .map((source) => `${source.reverse().join(" ")}w`)
            .join(",")
    );
}

//https://stackoverflow.com/questions/15690706/recursively-looping-through-an-object-to-build-a-property-list/53620876#53620876
export function getDeepPropertyListInDotNotation(obj: object): string[] {
    const isObject = (val) => val && typeof val === "object" && !Array.isArray(val);

    const addDelimiter = (a, b) => (a ? `${a}.${b}` : b);

    const paths = (obj: any = {}, head = "") => {
        return Object.entries(obj).reduce((product, [key, value]) => {
            let fullPath = addDelimiter(head, key);
            return isObject(value) ? product.concat(paths(value, fullPath)) : product.concat(fullPath);
        }, []);
    };

    return paths(obj);
}

/**
 * Wrapped JSON.stringify method so that is
 * does not explode if JSON is malformed
 */
export function safelySerializeJSON(json: unknown) {
    try {
        return JSON.stringify(json);
    } catch (error) {
        logError(error);
    }
}

/**
 * Wrapped JSON.parse method so that is
 * does not explode if JSON is malformed
 */
export function safelyParseJSON(string: string) {
    try {
        return JSON.parse(string);
    } catch (error) {
        logError(error);
    }
}
