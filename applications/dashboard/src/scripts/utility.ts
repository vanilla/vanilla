/**
 * General utility functions.
 * This file should have NO external dependencies other than javascript.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

/**
 * @type {boolean} The current debug setting.
 * @private
 */
let _debug = false;

/**
 * Encode CSS special characters as a hex escape sequence.
 *
 * @param {string} str Dirty CSS value.
 * @returns {string} Sanitized CSS value.
 */
export function cssSpecialChars(str: string) {
    return str.replace(/[\\!"#\$%&'\(\)\*\+,-.\/:;<=>\?@\[\]\^`{\|}~]/g, (char: string) => {
        const hexCode = char.charCodeAt(0).toString(16);
        const padded = "000000" + hexCode;
        return "\\" + padded.substr(-6);
    });
}

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
    // tslint:disable-next-line:no-console
    console.error(...value);
}

/**
 * Log a warning to console.
 *
 * @param value - The value to log.
 */
export function logWarning(...value: any[]) {
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
