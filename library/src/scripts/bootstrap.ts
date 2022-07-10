/**
 * Primary bootstrapping of the frontend JS. This entrypoint should be the last once executed.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

window.onerror = (error) => {
    console.error(error);
    document.body.style.visibility = "visible";
};

import { onContent, getMeta, _executeReady } from "@library/utility/appUtils";
import { logDebug, logError, debug } from "@vanilla/utils";
import { translationDebug, translate } from "@vanilla/i18n";
import apiv2 from "@library/apiv2";
import { mountInputs } from "@library/forms/mountInputs";
import { onPageView } from "@library/pageViews/pageViewTracking";
import { History } from "history";
import { addComponent, _mountComponents } from "@library/utility/componentRegistry";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";
import { bootstrapLocales } from "@library/locales/localeBootstrap";
import { isLegacyAnalyticsTickEnabled } from "@library/analytics/AnalyticsData";
import getStore from "@library/redux/getStore";
import { hasPermission } from "@library/features/users/Permission";

// Has some side effects of creating globals.
import "@library/gdn";
import { loadedCSS } from "@rich-editor/quill/components/loadedStyles";
import { loadThemeShadowDom } from "@library/theming/loadThemeShadowDom";
import TabWidget from "@library/tabWidget/TabWidget";

if (!getMeta("featureFlags.useFocusVisible.Enabled", true)) {
    document.body.classList.add("hasNativeFocus");
}

addComponent("TabWidget", TabWidget, { overwrite: true });

// Inject the debug flag into the utility.
const debugValue = getMeta("context.debug", getMeta("debug", false));
debug(debugValue);

const translationDebugValue = getMeta("context.translationDebug", false);
translationDebug(translationDebugValue);

bootstrapLocales();

// Export some globals to the window.

// Exposed under other namespaces for legacy reasons.
window.gdn.apiv2 = apiv2;
window.onPageView = onPageView;

// Named this way to discourage direct usage.
window.__VANILLA_INTERNAL_IS_READY__ = false;
window.__VANILLA_GLOBALS_DO_NOT_USE_DIRECTLY__ = {
    apiv2,
    translate,
    getCurrentUser: () => {
        return getStore().getState().users.current.data;
    },
    getCurrentUserPermissions: () => {
        return getStore().getState().users.permissions.data;
    },
    currentUserHasPermission: hasPermission,
};

// Record the page view.
onPageView((params: { history: History }) => {
    if (isLegacyAnalyticsTickEnabled()) {
        // Don't use the new tick if we're still using the old one.
        return;
    }
    // Low priority so put a slight delay so other network requests run first.
    setTimeout(() => {
        void apiv2.post("/tick").then(() => {
            window.dispatchEvent(new CustomEvent("analyticsTick"));
        });
    }, 50);
});

logDebug("Bootstrapping");

/**
 * Newer (react) pages do not load JQuery which is used to reveal content
 * when the forum is loaded using advanced embed. This tests if the show
 * function is defined, indicating that easyXDM is being used and calls it
 * to prevent users seeing a blank page.
 *
 * https://github.com/vanilla/vanilla-cloud/blob/master/applications/dashboard/views/staticcontent/container.twig#L61-L63
 */
try {
    const embedEnabled = getMeta("embed.enabled", false);
    const advancedEmbed = getMeta("embed.isAdvancedEmbed", false);
    if (embedEnabled && advancedEmbed && window.parent.show) {
        window.parent.show();
    }
} catch (error) {
    console.error(error);
}

// Make sure we mount our header/footer shadow doms before anything else happens.
_executeReady(loadThemeShadowDom)
    .then(() => {
        logDebug("Bootstrapping complete.");
        // Mount all data-react components.
        onContent((e) => {
            _mountComponents(e.target as HTMLElement).finally(() => {
                setTimeout(() => {
                    // Without setImmediate there is a FOUC
                    loadedCSS();
                }, 0);
            });
            blotCSS();
            mountInputs();
        });

        window.__VANILLA_INTERNAL_IS_READY__ = true;
        const contentEvent = new CustomEvent("X-DOMContentReady", { bubbles: true, cancelable: false });
        document.dispatchEvent(contentEvent);
    })
    .catch((error) => {
        logError(error);
    });
