/**
 * Primary bootstrapping of the frontend JS. This entrypoint should be the last once executed.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent, getMeta, _executeReady, _mountComponents } from "@library/utility/appUtils";
import { logDebug, logError, debug } from "@vanilla/utils";
import gdn from "@library/gdn";
import apiv2 from "@library/apiv2";
import { mountInputs } from "@library/forms/mountInputs";
import { onPageView } from "@library/pageViews/pageViewTracking";
import { History } from "history";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";

// Inject the debug flag into the utility.
const debugValue = getMeta("context.debug", getMeta("debug", false));
debug(debugValue);

// Export the API to the global object.
gdn.apiv2 = apiv2;

// Record the page view.
onPageView((params: { history: History }) => {
    void apiv2.post("/tick").then(() => {
        window.dispatchEvent(new CustomEvent("analyticsTick"));
    });
});

logDebug("Bootstrapping");
_executeReady()
    .then(() => {
        logDebug("Bootstrapping complete.");
        // Mount all data-react components.
        onContent(e => {
            _mountComponents(e.target as HTMLElement);
            blotCSS(); // Load shared "cssRule" styles for blots
            mountInputs();
        });

        const contentEvent = new CustomEvent("X-DOMContentReady", { bubbles: true, cancelable: false });
        document.dispatchEvent(contentEvent);
    })
    .catch(error => {
        logError(error);
    });
