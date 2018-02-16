/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */
import twemoji from "twemoji";

const emojiOptions = {
    className: "fallBackEmoji",
    size: "72x72",
};

function emojiSupported() {
    const node = document.createElement('canvas');
    if (!node.getContext || !node.getContext('2d') || typeof node.getContext('2d').fillText !== 'function') {
        return false;
    }
    const ctx = node.getContext('2d');
    ctx.textBaseline = 'top';
    ctx.font = '32px Arial';
    ctx.fillText('\ud83d\ude03', 0, 0);
    return ctx.getImageData(16, 16, 1, 1).data[0] !== 0;
}

const browserSupportsEmoji = emojiSupported();

export function isEmojiSupported() {
    return browserSupportsEmoji;
}

export function parseEmoji(emojiChar) {
    if(browserSupportsEmoji) {
        return emojiChar;
    }
    return twemoji.parse(emojiChar, emojiOptions);
}

export function parseDomForEmoji(domNode = document.body) {
    if(browserSupportsEmoji) {
        return;
    }
    console.log("parsing dom for emojis");
    twemoji.parse(domNode, emojiOptions);
}
