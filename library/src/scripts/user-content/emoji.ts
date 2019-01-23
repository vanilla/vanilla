/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/application";
import { convertToSafeEmojiCharacters } from "@library/dom";

export function initEmojiSupport() {
    // Emoji
    onReady(() => convertToSafeEmojiCharacters(document.body));
    onContent(() => convertToSafeEmojiCharacters(document.body));
}
