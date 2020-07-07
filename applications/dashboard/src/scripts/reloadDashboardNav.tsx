/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@vanilla/library/src/scripts/apiv2";
import { getCurrentLocale } from "@vanilla/i18n";
import { logError, logWarning } from "@vanilla/utils";
import { triggerContentLoaded } from "@vanilla/library/src/scripts/utility/appUtils";

/**
 * Reload the dashboard navigation.
 */
export async function reloadDashboardNav() {
    try {
        const panel = document.querySelector(".js-panel-nav");
        if (!(panel instanceof HTMLElement)) {
            logWarning("Could not find dashboard navigation to replace.");
            return;
        }
        const response = await apiv2.get("/dashboard/menu-legacy", {
            params: {
                locale: getCurrentLocale(),
                activeUrl: window.location.href,
            },
        });

        const html = response.data?.html ?? null;
        if (!html) {
            logError("No valid panel HTML to insert.");
            return;
        }

        panel.innerHTML = html;
        triggerContentLoaded();
    } catch (error) {
        logError(error);
    }
}
