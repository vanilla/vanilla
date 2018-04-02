/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { onContent, getMeta, _executeReady } from "@core/application";
import { log, logError, debug } from "@core/utility";
import { _mountComponents } from "@core/internal";

debug(getMeta('debug', false));

onContent((e) => {
    _mountComponents(e.target);
});

log("Bootstrapping");
_executeReady().then(() => {
    log("Bootstrapping complete.");

    const contentEvent = new CustomEvent('X-DOMContentReady', { bubbles: true, cancelable: false });
    document.dispatchEvent(contentEvent);
}).catch(error => {
    logError(error);
});
