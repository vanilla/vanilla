/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import twemoji from "twemoji";

let emojiSupportedCache: boolean | null = null;

/**
 * Determine if the browser/OS combo has support for real unicode emojis.
 */
export function isEmojiSupported() {
    if (emojiSupportedCache !== null) {
        return emojiSupportedCache;
    }

    if (process.env.NODE_ENV !== "test") {
        // Test environment
        const canvas = document.createElement("canvas");
        if (canvas.getContext && canvas.getContext("2d")) {
            const ctx = document.createElement("canvas").getContext("2d");
            if (ctx) {
                ctx.fillText("😗", -2, 4);
                emojiSupportedCache = ctx.getImageData(0, 0, 1, 1).data[3] > 0;
            } else {
                emojiSupportedCache = false;
            }
        } else {
            emojiSupportedCache = false;
        }
    } else {
        emojiSupportedCache = true;
    }

    return emojiSupportedCache;
}

const emojiOptions = {
    className: "fallBackEmoji",
    size: "72x72",
};

/**
 * Returns either native emoji or fallback image
 *
 * @param stringOrNode - A DOM Node or string to convert.
 */
export function convertToSafeEmojiCharacters(stringOrNode: string | Node) {
    if (isEmojiSupported()) {
        return stringOrNode;
    }
    return twemoji.parse(stringOrNode, emojiOptions);
}
