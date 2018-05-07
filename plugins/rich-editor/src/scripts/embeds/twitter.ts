/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@core/embeds";
import { ensureScript } from "@core/dom";
import { logError } from "@core/utility";

export default function init() {
    registerEmbed("twitter", renderer);
}

export async function renderer(element: HTMLElement, data: IEmbedData) {
    await ensureScript("//platform.twitter.com/widgets.js");

    if (!window.twttr) {
        logError("The Twitter widget failed to load.");
        return element;
    }

    if (data.attributes.statusID == null) {
        logError("Attempted to embed a tweet but the statusID could not be found");
        return element;
    }

    return await window.twttr.widgets.createTweet(data.attributes.statusID, element);
}
