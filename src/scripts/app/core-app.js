/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { parseDomForEmoji } from "@core/emoji-utility";


import events from "@core/events";

events.onVanillaReady(() => {
    parseDomForEmoji();
});
