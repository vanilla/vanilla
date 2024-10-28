/**
 * Primary bootstrapping of the frontend JS. This entrypoint should be the last once executed.
 *
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

window.global ||= window;

window.onerror = (error) => {
    console.error(error);
    /**
     * Custom pockets and themes could have erroneous code that will call this early.
     * So lets wait before revealing the page
     */
    setTimeout(() => {
        if (window.getComputedStyle(document.body).visibility !== "visible") {
            document.body.style.visibility = "visible";
            logDebug("Javascript error encountered, forcing body visibility");
        }
    }, 800);
};

import { onContent, getMeta, _executeReady, assetUrl } from "@library/utility/appUtils";
import { logError, debug, logDebug } from "@vanilla/utils";
import { translationDebug } from "@vanilla/i18n";
import apiv2 from "@library/apiv2";
import { onPageView } from "@library/pageViews/pageViewTracking";
import { _mountComponents } from "@library/utility/componentRegistry";
import { blotCSS } from "@rich-editor/editor/components/blotStyles";
import { bootstrapLocales } from "@library/locales/localeBootstrap";
import "@library/VanillaGlobals";

// Has some side effects of creating globals.
import "@library/gdn";
import { initModernEmbed } from "@library/embed/modernEmbed.local";
import { iconRegistry } from "@vanilla/icons";

export async function bootstrapVanilla() {
    performance.mark("Bootstrap - Start");
    if (!getMeta("featureFlags.useFocusVisible.Enabled", true)) {
        document.body.classList.add("hasNativeFocus");
    }

    iconRegistry.baseUrl = assetUrl("");

    // Inject the debug flag into the utility.
    const debugValue = getMeta("context.debug", getMeta("debug", false));
    debug(debugValue);

    const translationDebugValue = getMeta("context.translationDebug", false);
    translationDebug(translationDebugValue);

    await bootstrapLocales();
    document.addEventListener("DOMContentLoaded", async () => {
        await bootstrapLocales();
    });

    // Export some globals to the window.

    // Exposed under other namespaces for legacy reasons.
    window.gdn.apiv2 = apiv2;
    window.onPageView = onPageView;

    // Named this way to discourage direct usage.
    window.__VANILLA_INTERNAL_IS_READY__ = false;

    initModernEmbed();

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

    // Wire up audit logs
    apiv2.interceptors.request.use((config) => {
        // Apply audit log headers as necessary.
        let auditLog = getMeta("auditLog");
        if (auditLog) {
            if (!config.headers) {
                config.headers = {};
            }
            config.headers["X-Parent-Audit-Log-Id"] = auditLog.auditLogID;
            config.headers["X-Parent-Audit-Log-Request-Path"] = auditLog.requestPath;
            config.headers["X-Parent-Audit-Log-Request-Method"] = auditLog.requestMethod;
            config.headers["X-Parent-Audit-Log-Request-Query"] = JSON.stringify(auditLog.requestQuery);
        }
        return config;
    });

    // Make sure we mount our header/footer shadow doms before anything else happens.
    _executeReady()
        .then(() => {
            // Mount all data-react components.
            onContent((e) => {
                _mountComponents(e.target as HTMLElement).finally(() => {
                    setTimeout(() => {
                        document.body.classList.remove("isLoading");
                    }, 0);
                });
                blotCSS();
            });

            window.__VANILLA_INTERNAL_IS_READY__ = true;
            const contentEvent = new CustomEvent("X-DOMContentReady", { bubbles: true, cancelable: false });
            document.dispatchEvent(contentEvent);
        })
        .catch((error) => {
            logError(error);
        })
        .finally(() => {
            performance.mark("Bootstrap - End");
        });
}
