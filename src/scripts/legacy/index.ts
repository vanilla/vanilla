/**
 * Bundled entrypoint for gradually replacing global.js
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { onContent, onReady } from "@core/application";
import { initializeAtComplete } from "./atwho";

// Initialize legacy @mentions for all BodyBox elements.
//
// Also assign this window function to the global scope.
// The advanced editor calls this function directly when in wysiwyg format, as it needs to
// handle an iframe, and the editor instance needs to be referenced.
if ($.fn.atwho) {
    onReady(() => initializeAtComplete(".BodyBox,.js-bodybox"));
    onContent(() => initializeAtComplete(".BodyBox,.js-bodybox"));
    window.gdn.atCompleteInit = initializeAtComplete;
}
