/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { initCodeHighlighting } from "@library/content/code";
import { initEmojiSupport } from "@library/content/emoji";
import { initSpoilers } from "@library/content/spoilers";
import { mountAllEmbeds } from "@library/embeddedContent/embedService";

export async function initAllUserContent() {
    initEmojiSupport();
    initSpoilers();
    await mountAllEmbeds();
    initCodeHighlighting();
}

/**
 * Runs method for all embeds that need to be rendered everytime content changes.
 * This is ideal for something like react's `componentDidMount`.
 */
export function convertAllUserContent() {
    mountAllEmbeds();
}
