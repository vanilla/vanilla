/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { onReady, onContent } from "@dashboard/application";
import { convertToSafeEmojiCharacters } from "@dashboard/dom";

// Emoji
onReady(initEmojiFallback);
onContent(initEmojiFallback);

function initEmojiFallback() {
    convertToSafeEmojiCharacters(document.body);
}
