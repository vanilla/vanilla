/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { initCodePenEmbeds } from "@library/content/embeds/codepen";
import { initGettyEmbeds, convertGettyEmbeds } from "@library/content/embeds/getty";
import { initGiphyEmbeds } from "@library/content/embeds/giphy";
import { initImageEmbeds } from "@library/content/embeds/image";
import { initImgurEmbeds, convertImgurEmbeds } from "@library/content/embeds/imgur";
import { initInstagramEmbeds, convertInstagramEmbeds } from "@library/content/embeds/instagram";
import { initLinkEmbeds } from "@library/content/embeds/link";
import { initSoundcloudEmbeds } from "@library/content/embeds/soundcloud";
import { initTwitterEmbeds, convertTwitterEmbeds } from "@library/content/embeds/twitter";
import { initVideoEmbeds } from "@library/content/embeds/video";
import { initEmojiSupport } from "@library/content/emoji";
import { initSpoilers } from "@library/content/spoilers";
import { initQuoteEmbeds, mountQuoteEmbeds } from "@library/content/embeds/quote";
import { initFileEmbeds, mountFileEmbeds } from "@library/content/embeds/file";
import { initCodeHighlighting } from "@library/content/code";
import { mountAllEmbeds } from "@library/embeddedContent/embedService";

export function initAllUserContent() {
    // User content
    initEmojiSupport();
    initSpoilers();
    mountAllEmbeds();
    // initCodePenEmbeds();
    // initGettyEmbeds();
    // initGiphyEmbeds();
    // initImageEmbeds();
    // initImgurEmbeds();
    // initInstagramEmbeds();
    // initLinkEmbeds();
    // initSoundcloudEmbeds();
    // initTwitterEmbeds();
    // initVideoEmbeds();
    // initQuoteEmbeds();
    // initFileEmbeds();
    initCodeHighlighting();
}

/**
 * Runs method for all embeds that need to be rendered everytime content changes.
 * This is ideal for something like react's `componentDidMount`.
 */
export function convertAllUserContent() {
    mountAllEmbeds();
    // void convertGettyEmbeds();
    // void convertImgurEmbeds();
    // void convertInstagramEmbeds();
    // void convertTwitterEmbeds();
    // void mountQuoteEmbeds();
    // void mountFileEmbeds();
}
