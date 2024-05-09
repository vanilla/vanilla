/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Determine if the browser/OS combo has support for real unicode emojis.
 */
export function isEmojiSupported() {
    return true;
}

/**
 * Returns either native emoji or fallback image
 *
 * @param stringOrNode - A DOM Node or string to convert.
 */
export function convertToSafeEmojiCharacters(stringOrNode: string | Node) {
    return stringOrNode;
}
