/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { onContent, getMeta, _executeReady } from "@core/application";
import { log, logError, debug } from "@core/utility";
import { _mountComponents } from "@core/internal";
import gdn from "@core/gdn";
import apiv2 from "@core/apiv2";

// Right now this is imported here instead of being its own bundle.
// Once we have some part of vanilla that can function without the legacy js,
// this should be pulled out into its own javascript bundle.
import "@core/legacy";

// Inject the debug flag into the utility.
debug(getMeta('debug', false));

// Export the API to the global object.
gdn.apiv2 = apiv2;

// Mount all data-react Components.
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
