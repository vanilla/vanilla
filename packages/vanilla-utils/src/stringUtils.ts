/**
 * Utilities related to strings.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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

/**
 * Capitalize the first character of a string.
 *
 * @param str The string to modify.
 */
export function capitalizeFirstLetter(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Simple utility function for waiting some duration in promise.
 *
 * @param duration The amount of time to wait.
 */
export function promiseTimeout(duration: number): Promise<void> {
    return new Promise(resolve => {
        setTimeout(resolve, duration);
    });
}
