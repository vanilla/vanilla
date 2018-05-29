/**
 * Primary bootstrapping of the frontend JS. This entrypoint should be the last once executed.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { onContent, getMeta, _executeReady, _mountComponents } from "@dashboard/application";
import { log, logError, debug } from "@dashboard/utility";
import gdn from "@dashboard/gdn";
import apiv2 from "@dashboard/apiv2";

// Right now this is imported here instead of being its own bundle.
// Once we have some part of vanilla that can function without the legacy js,
// this should be pulled out into its own javascript bundle.
import "../legacy";

// Inject the debug flag into the utility.
debug(getMeta("debug", false));

// Export the API to the global object.
gdn.apiv2 = apiv2;

// Mount all data-react components.
onContent(e => {
    _mountComponents(e.target);
});

log("Bootstrapping");
_executeReady()
    .then(() => {
        log("Bootstrapping complete.");

        const contentEvent = new CustomEvent("X-DOMContentReady", { bubbles: true, cancelable: false });
        document.dispatchEvent(contentEvent);
    })
    .catch(error => {
        logError(error);
    });
