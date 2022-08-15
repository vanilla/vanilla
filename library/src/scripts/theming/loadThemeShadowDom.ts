/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { prepareShadowRoot } from "@vanilla/dom-utils";

export function loadThemeShadowDom() {
    performance.mark("Theme ShadowDOM - Start");
    const themeHeader = document.getElementById("themeHeader");
    const themeFooter = document.getElementById("themeFooter");
    if (themeHeader && !themeHeader.shadowRoot) {
        prepareShadowRoot(themeHeader, true);
    }

    if (themeFooter && !themeFooter.shadowRoot) {
        prepareShadowRoot(themeFooter, true);
    }
    performance.mark("Theme ShadowDOM - End");
}
