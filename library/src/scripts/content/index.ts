/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { initCodeHighlighting } from "@library/content/code";
import { initEmojiSupport } from "@library/content/emoji";
import { initSpoilers } from "@library/content/spoilers";
import { mountAllEmbeds } from "@library/embeddedContent/embedService.mounting";
import { autoWrapCollapsableContent } from "@library/content/CollapsableContent";
import { initTables } from "@library/content/table";

export async function initAllUserContent() {
    await autoWrapCollapsableContent();
    initEmojiSupport();
    initSpoilers();
    initCodeHighlighting();
}

/**
 * Runs method for all embeds that need to be rendered everytime content changes.
 * This is ideal for something like react's `componentDidMount`.
 */
export function convertAllUserContent() {
    mountAllEmbeds();
    initTables();
}
