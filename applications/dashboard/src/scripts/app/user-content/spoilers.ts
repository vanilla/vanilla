/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { delegateEvent } from "@dashboard/dom";

// Setup
delegateEvent("click", ".js-toggleSpoiler", handleToggleSpoiler);

/**
 * Toggle a spoiler open and closed.
 */
function handleToggleSpoiler() {
    const toggleButton: HTMLElement = this;

    const spoilerContainer = toggleButton.closest(".spoiler");
    if (spoilerContainer) {
        spoilerContainer.classList.toggle("isShowingSpoiler");
    }
}
