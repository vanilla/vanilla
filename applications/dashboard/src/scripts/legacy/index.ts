/**
 * Entrypoint for gradually replacing global.js
 *
 * At some point everything in this folder should be able to be removed (new base theme).
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent, onReady } from "@library/utility/appUtils";
import { initializeAtComplete } from "@dashboard/legacy/atwho";
import { escapeHTML } from "@vanilla/dom-utils";

// Expose some new module functions to our old javascript system.
window.escapeHTML = escapeHTML;
// Initialize legacy @mentions for all BodyBox elements.
if ($.fn.atwho) {
    onReady(() => initializeAtComplete(".BodyBox,.js-bodybox"));
    onContent(() => initializeAtComplete(".BodyBox,.js-bodybox"));

    // Also assign this function to the global `gdn` object.
    // The advanced editor calls this function directly when in wysiwyg format, as it needs to
    // handle an iframe, and the editor instance needs to be referenced. The advanced editor does not yet use
    // this build process so it can only communicate through here with a global.
    window.gdn.atCompleteInit = initializeAtComplete;
}
