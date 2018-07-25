/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { onReady, onContent } from "@dashboard/application";
import { convertToSafeEmojiCharacters } from "@dashboard/dom";

export function initEmojiSupport() {
    // Emoji
    onReady(() => convertToSafeEmojiCharacters(document.body));
    onContent(() => convertToSafeEmojiCharacters(document.body));
}
