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

    //if its NewCategoryDropdown, we don't need to wrap, styles already come from react component
    if (!window.gdn.meta.themeFeatures.NewCategoryDropdown) {
        $("select").wrap('<div class="SelectWrapper"></div>');
    } else {
        $("select").each(function() {
            if ($(this).attr("name") && $(this).attr("name") !== "CategoryID") {
                $(this).wrap('<div class="SelectWrapper"></div>');
            }
        });
    }
});
