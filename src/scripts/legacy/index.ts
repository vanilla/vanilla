/**
 * Entrypoint for gradually replacing global.js
 *
 * At some point everything in this folder should be able to be removed (new base theme).
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { onContent, onReady } from "@core/application";
import { initializeAtComplete } from "@core/legacy/atwho";

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
