/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

///
/// This file should only be included in development builds.
/// The idea is to try and detect if the devtools are open are not
/// (React DevTools injects a nice global for us to look at)
/// This might change in the future and we'll have to update this.
///
/// Enabling speedy on emotion loses sourcemap info, but speeds up local environments significantly.
///

import { sheet } from "@emotion/css";
import { logDebug } from "@vanilla/utils";

const hasDevTools = window.__REACT_DEVTOOLS_COMPONENT_FILTERS__;

if (process.env.NODE_ENV === "development") {
    if (hasDevTools) {
        logDebug(`DevTools were detected, so emotion IS NOT running in speedy mode. Redux:`);
        sheet.speedy(false);
    } else {
        logDebug("DevTools were not detected, so emotion IS running in speedy mode.");
        sheet.speedy(true);
    }
}
