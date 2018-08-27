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

// Inject the debug flag into the utility.
debug(getMeta("debug", false));

// Export the API to the global object.
gdn.apiv2 = apiv2;

log("Bootstrapping");
_executeReady()
    .then(() => {
        log("Bootstrapping complete.");
        // Mount all data-react components.
        onContent(e => {
            _mountComponents(e.target);
        });

        const contentEvent = new CustomEvent("X-DOMContentReady", { bubbles: true, cancelable: false });
        document.dispatchEvent(contentEvent);
    })
    .catch(error => {
        logError(error);
    });
