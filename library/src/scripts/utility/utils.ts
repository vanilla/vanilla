/**
 * General utility functions.
 * This file should have NO external dependencies other than javascript.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * @type {boolean} The current debug setting.
 * @private
 */
let _debug = false;

/**
 * Get or set the debug flag.
 *
 * @param newValue - The new value of debug.
 * @returns the current debug setting.
 */
export function debug(newValue?: boolean): boolean {
    if (newValue !== undefined) {
        _debug = newValue;
    }

    return _debug;
}

type NormalCallback = (...args: any[]) => any;
type PromiseCallback = (...args: any[]) => Promise<any>;

export type PromiseOrNormalCallback = NormalCallback | PromiseCallback;

/**
 * Resolve an array of functions that return promises sequentially.
 *
 * @param promiseFunctions - The functions to execute.
 *
 * @returns An array of all results in sequential order.
 *
 * @example
 * const urls = ['/url1', '/url2', '/url3']
 * const functions = urls.map(url => () => fetch(url))
 * resolvePromisesSequentially(funcs)
 *   .then(console.log)
 *   .catch(console.error)
 */
export function resolvePromisesSequentially(promiseFunctions: PromiseOrNormalCallback[]): Promise<any[]> {
    if (!Array.isArray(promiseFunctions)) {
        throw new Error("First argument needs to be an array of Promises");
    }

    return new Promise((resolve, reject) => {
        let count = 0;
        let results = [];

        function iterationFunction(previousPromise, currentPromise) {
            return previousPromise
                .then(result => {
                    if (count++ !== 0) {
                        results = results.concat(result);
                    }

                    return currentPromise(result, results, count);
                })
                .catch(err => reject(err));
        }

        promiseFunctions = promiseFunctions.concat(() => Promise.resolve());

        promiseFunctions.reduce(iterationFunction, Promise.resolve(false)).then(() => {
            resolve(results);
        });
    });
}

/**
 * Log something to console.
 *
 * This only prints in debug mode.
 *
 * @param value - The value to log.
 */
export function log(...value: any[]) {
    if (_debug) {
        // tslint:disable-next-line:no-console
        console.log(...value);
    }
}

/**
 * Log an error to console.
 *
 * @param value - The value to log.
 */
export function logError(...value: any[]) {
    if (!_debug && process.env.NODE_ENV === "test") {
        return;
    }
    // tslint:disable-next-line:no-console
    console.error(...value);
}

/**
 * Log a warning to console.
 *
 * @param value - The value to log.
 */
export function logWarning(...value: any[]) {
    if (!_debug && process.env.NODE_ENV === "test") {
        return;
    }
    // tslint:disable-next-line:no-console
    console.warn(...value);
}

/**
 * A simple, fast method of hashing a string. Similar to Java's hash function.
 * https://stackoverflow.com/a/7616484/1486603
 *
 * @param str - The string to hash.
 *
 * @returns The hash code returned.
 */
export function hashString(str: string): number {
    function hashReduce(prevHash, currVal) {
        // tslint:disable-next-line:no-bitwise
        return (prevHash << 5) - prevHash + currVal.charCodeAt(0);
    }
    return str.split("").reduce(hashReduce, 0);
}

type CompareReturn = -1 | 0 | 1;

/**
 * Utility for sorting values. Similar to the <=> operator in PHP.
 *
 * @param val1 The first value to compare.
 * @param val2 The second value to compare.
 *
 * @returns -1, 0, or 1
 */
export function compare<T extends string | number>(val1: T, val2: T): CompareReturn {
    if (typeof val1 === "string" && typeof val2 === "string") {
        return val1.localeCompare(val2) as CompareReturn;
    } else {
        if (val1 > val2) {
            return 1;
        } else if (val1 < val2) {
            return -1;
        }
        return 0;
    }
}

/**
 * Parse a string into a URL friendly format.
 *
 * Eg. Why Uber isn’t spelled Über -> why-uber-isnt-spelled-uber
 *
 * @param str The string to parse.
 */
export function slugify(
    str: string,
    options?: {
        allowMultipleDashes?: boolean;
    },
): string {
    const whiteSpaceNormalizeRegexp = options && options.allowMultipleDashes ? /[\s]+/g : /[-\s]+/g;
    return str
        .normalize("NFD") // Normalize accented characters into ASCII equivalents
        .replace(/[^\w\s$*_+~.()'"\-!:@]/g, "") // REmove characters that don't URL encode well
        .trim() // Trim whitespace
        .replace(whiteSpaceNormalizeRegexp, "-") // Normalize whitespace
        .toLocaleLowerCase(); // Convert to locale aware lowercase.
}

/**
 * Split a string in multiple pieces similar to String.prototype.split but ignore most acccent characters.
 *
 * This will still return pieces with accents.
 *
 * @param toSplit The string to split.
 * @param splitWith The string to split with.
 */
export function splitStringLoosely(toSplit: string, splitWith: string): string[] {
    const normalizedName = toSplit.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const normalizedSplitTerm = splitWith.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const normalizedPieces = normalizedName.split(new RegExp(`(${normalizedSplitTerm})`, "i"));

    let charactersUsed = 0;
    return normalizedPieces.map(piece => {
        const start = charactersUsed;
        charactersUsed += piece.length;
        return toSplit.substring(start, charactersUsed);
    });
}

interface IClass {
    new (): any;
}

export function isInstanceOfOneOf(needle: any, haystack: IClass[]) {
    for (const classItem of haystack) {
        if (needle instanceof classItem) {
            return true;
        }
    }

    return false;
}

export function simplifyFraction(numerator: number, denominator: number) {
    const findGCD = (a, b) => {
        return b ? findGCD(b, a % b) : a;
    };
    const gcd = findGCD(numerator, denominator);

    numerator = numerator / gcd;
    denominator = denominator / gcd;

    return {
        numerator,
        denominator,
        shorthand: denominator + ":" + numerator,
    };
}

interface IMentionMatch {
    match: string;
    rawMatch: string;
}

/**
 * Custom matching to allow quotation marks in the matching string as well as spaces.
 * Spaces make things more complicated.
 *
 * @param subtext - The string to be tested.
 * @param shouldStartWithSpace - Should the pattern include a test for a whitespace prefix?
 * @returns Matching string if successful.  Null on failure to match.
 */
export function matchAtMention(
    subtext: string,
    shouldStartWithSpace: boolean = false,
    requireQuotesForWhitespace: boolean = true,
): IMentionMatch | null {
    // Split the string at the lines to allow for a simpler regex.
    const lines = subtext.split("\n");
    const lastLine = lines[lines.length - 1];

    // If you change this you MUST change the regex in src/scripts/__tests__/legacy.test.js !!!
    /**
     * Put together the non-excluded characters.
     *
     * @param {boolean} excludeWhiteSpace - Whether or not to exclude whitespace characters.
     *
     * @returns {string} A Regex string.
     */
    function nonExcludedCharacters(excludeWhiteSpace) {
        let excluded =
            "[^" +
            '"' + // Quote character
            "\\u0000-\\u001f\\u007f-\\u009f" + // Control characters
            "\\u2028"; // Line terminator

        if (excludeWhiteSpace) {
            excluded += "\\s";
        }

        excluded += "]";
        return excluded;
    }

    let regexStr =
        "@" + // @ Symbol triggers the match
        "(" +
        // One or more non-greedy characters that aren't excluded. Whitespace is allowed, but a starting quote is required.
        '"(' +
        nonExcludedCharacters(false) +
        '+?)"?' +
        "|" + // Or
        // One or more non-greedy characters that aren't exluded. Whitespace may be excluded.
        "(" +
        nonExcludedCharacters(requireQuotesForWhitespace) +
        '+?)"?' +
        ")" +
        "(?:\\n|$)"; // Newline terminates.

    // Determined by at.who library
    if (shouldStartWithSpace) {
        regexStr = "(?:^|\\s)" + regexStr;
    }
    const regex = new RegExp(regexStr, "gi");
    const match = regex.exec(lastLine);
    if (match) {
        return {
            rawMatch: match[0],
            match: match[2] || match[1], // Return either of the matching groups (quoted or unquoted).
        };
    }

    // No match
    return null;
}

/** This should mirror extensions allowed in Vanilla\ImageResizer.php */
const IMAGE_REGEX = /^image\/(gif|jpe?g|png)/i;

/**
 * A filter for use with [].filter
 *
 * Matches only image image type files.
 * @private
 *
 * @param file - A File object.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/File
 *
 * @returns Whether or not the file is an acceptable image
 */
export function isFileImage(file: File): boolean {
    if (IMAGE_REGEX.test(file.type)) {
        return true;
    }

    log("Filtered out non-image file: ", file.name);
    return false;
}

export function capitalizeFirstLetter(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Transform an array of objects and an map of objets with a given key.
 *
 * Objects that do not contain the given key are dropped.
 *
 * @param array The array to go through.
 * @param key The key to lookup.
 */
export function indexArrayByKey<T extends object>(
    array: T[],
    key: string,
): {
    [key: string]: T;
} {
    const object = {};
    for (const item of array) {
        if (key in item) {
            if (!(item[key] in object)) {
                object[item[key]] = [];
            }
            object[item[key]].push(item);
        }
    }
    return object;
}

const SAFE_PROTOCOL_REGEX = /^(http:\/\/|https:\/\/|tel:|mailto:\/\/|\/)/;

/**
 * Sanitize a URL to ensure that it matches a whitelist of approved url schemes. If the url does not match one of these schemes, prepend `unsafe:` before it.
 *
 * Allowed protocols
 * - "http://",
 * - "https://",
 * - "tel:",
 * - "mailto://",
 *
 * @param url The url to sanitize.
 */
export function sanitizeUrl(url: string) {
    if (url.match(SAFE_PROTOCOL_REGEX)) {
        return url;
    } else {
        return "unsafe:" + url;
    }
}

export enum OS {
    IOS = "ios",
    ANDROID = "android",
    UNKNOWN = "unkwown",
}

/**
 * Provide relatively rough detection of mobile OS.
 *
 * This is not even close to perfect but can be used to try and offer,
 * OS specific input elements for things like datetimes.
 */
export function guessOperatingSystem(): OS {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;

    if (/android/i.test(userAgent)) {
        return OS.ANDROID;
    }

    // iOS detection from: http://stackoverflow.com/a/9039885/177710
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        return OS.IOS;
    }

    return OS.UNKNOWN;
}
