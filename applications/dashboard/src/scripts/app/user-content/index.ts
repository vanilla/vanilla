/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { initCodePenEmbeds } from "@dashboard/app/user-content/embeds/codepen";
import { initGettyEmbeds, convertGettyEmbeds } from "@dashboard/app/user-content/embeds/getty";
import { initGiphyEmbeds } from "@dashboard/app/user-content/embeds/giphy";
import { initImageEmbeds } from "@dashboard/app/user-content/embeds/image";
import { initImgurEmbeds, convertImgurEmbeds } from "@dashboard/app/user-content/embeds/imgur";
import { initInstagramEmbeds, convertInstagramEmbeds } from "@dashboard/app/user-content/embeds/instagram";
import { initLinkEmbeds } from "@dashboard/app/user-content/embeds/link";
import { initSoundcloudEmbeds } from "@dashboard/app/user-content/embeds/soundcloud";
import { initTwitterEmbeds, convertTwitterEmbeds } from "@dashboard/app/user-content/embeds/twitter";
import { initVideoEmbeds } from "@dashboard/app/user-content/embeds/video";
import { initEmojiSupport } from "@dashboard/app/user-content/emoji";
import { initSpoilers } from "@dashboard/app/user-content/spoilers";
import { initQuoteEmbeds, mountQuoteEmbeds } from "@dashboard/app/user-content/embeds/quote";

export function initAllUserContent() {
    // User content
    initEmojiSupport();
    initSpoilers();
    initCodePenEmbeds();
    initGettyEmbeds();
    initGiphyEmbeds();
    initImageEmbeds();
    initImgurEmbeds();
    initInstagramEmbeds();
    initLinkEmbeds();
    initSoundcloudEmbeds();
    initTwitterEmbeds();
    initVideoEmbeds();
    initQuoteEmbeds();
}

/**
 * Runs method for all embeds that need to be rendered everytime content changes.
 * This is ideal for something like react's `componentDidMount`.
 */
export function convertAllUserContent() {
    void convertGettyEmbeds();
    void convertImgurEmbeds();
    void convertInstagramEmbeds();
    void convertTwitterEmbeds();
    void mountQuoteEmbeds();
}
