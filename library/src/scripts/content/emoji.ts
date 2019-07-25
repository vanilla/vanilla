/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/utility/appUtils";
import { convertToSafeEmojiCharacters } from "@vanilla/dom-utils";

export function initEmojiSupport() {
    // Emoji
    onReady(() => convertToSafeEmojiCharacters(document.body));
    onContent(() => convertToSafeEmojiCharacters(document.body));
}
