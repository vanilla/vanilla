/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import loadPolyfills from "@core/bootstrap/polyfills";
import events, { onContent } from "@core/events";
import * as utility from "@core/utility";
import { _mountComponents } from "@core/internal";

onContent((e) => {
    _mountComponents(e.target);
});

loadPolyfills().then(() => {
    utility.log("Bootstrapping");
    events.execute().then(() => {
        utility.log("Bootstrapping complete.");

        const event = document.createEvent('CustomEvent');
        event.initCustomEvent('X-DOMContentReady', true, false);
        document.dispatchEvent(event);
    });
});
