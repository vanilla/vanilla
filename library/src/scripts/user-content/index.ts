/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { initCodePenEmbeds } from "@library/user-content/embeds/codepen";
import { initGettyEmbeds, convertGettyEmbeds } from "@library/user-content/embeds/getty";
import { initGiphyEmbeds } from "@library/user-content/embeds/giphy";
import { initImageEmbeds } from "@library/user-content/embeds/image";
import { initImgurEmbeds, convertImgurEmbeds } from "@library/user-content/embeds/imgur";
import { initInstagramEmbeds, convertInstagramEmbeds } from "@library/user-content/embeds/instagram";
import { initLinkEmbeds } from "@library/user-content/embeds/link";
import { initSoundcloudEmbeds } from "@library/user-content/embeds/soundcloud";
import { initTwitterEmbeds, convertTwitterEmbeds } from "@library/user-content/embeds/twitter";
import { initVideoEmbeds } from "@library/user-content/embeds/video";
import { initEmojiSupport } from "@library/user-content/emoji";
import { initSpoilers } from "@library/user-content/spoilers";
import { initQuoteEmbeds, mountQuoteEmbeds } from "@library/user-content/embeds/quote";

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
