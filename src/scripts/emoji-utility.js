/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */
import twemoji from "twemoji";
import * as utility from "@core/utility";

const emojiOptions = {
    className: "fallBackEmoji",
    size: "72x72",
};

// Test Char for Emoji 5.0
const testChar = '\uD83E\uDD96'; // U+1F996 T-Rex -> update test character with new emoji version support.

function emojiSupported() {
    if (process.env.NODE_ENV !== 'test') { // Test environment
        const canvas = document.createElement('canvas');
        if (canvas.getContext && canvas.getContext('2d')) {
            const pixelRatio = window.devicePixelRatio || 1;
            const offset = 12 * pixelRatio;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#f00';
            ctx.textBaseline = 'top';
            ctx.font = '32px Arial';
            ctx.fillText(testChar, 0, 0);
            return ctx.getImageData(offset, offset, 1, 1).data[0] !== 0;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

const browserSupportsEmoji = emojiSupported();

utility.log("Emoji Supported: ", browserSupportsEmoji);

export function isEmojiSupported() {
    return browserSupportsEmoji;
}

export function parseEmoji(emojiChar) {
    if(browserSupportsEmoji) {
        return emojiChar;
    }
    return twemoji.parse(emojiChar, emojiOptions);
}

/**
 * Replace emojis in DOM element with images if unsupported
 *
 * @param {domNode} Element to search in
 * @returns {Element|null} - The emoji
 */

export function parseDomForEmoji(domNode = document.body) {
    if(browserSupportsEmoji) {
        return;
    }
    const div = document.createElement("div");
    div.innerHTML = twemoji.parse(domNode, emojiOptions);
    return div.firstChild;
}
