/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
"use strict"

import { setupMobileNavigation } from "./mobileNavigation";

$(() => {
    if (!window.gdn.getMeta("featureFlags.DataDrivenTitleBar.Enabled", false)) {
        setupMobileNavigation();
    }
    $("select").wrap('<div class="SelectWrapper"></div>');
});
