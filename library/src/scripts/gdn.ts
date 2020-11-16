/**
 * A module to isolate meta data passed from the server into a single dependency.
 * This should always be used instead of accessing window.gdn directly.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { TabHandler } from "@vanilla/dom-utils/src";
import { translate } from "@vanilla/i18n/src";

interface IGdn {
    meta: AnyObject;
    permissions: AnyObject;
    translations: AnyObject;
    [key: string]: any;
}

/** The gdn object may be set in an inline script in the head of the document. */
const gdn = window.gdn || {};

if (!("meta" in gdn)) {
    gdn.meta = {};
}

if (!("permissions" in gdn)) {
    gdn.permissions = {};
}

if (!("translations" in gdn)) {
    gdn.translations = {};
}

if (!("translate" in gdn)) {
    gdn.translate = translate;
}

if (!("makeAccessiblePopup" in gdn)) {
    gdn.makeAccessiblePopup = ($popupEl, settings, sender) => {
        if (sender) {
            let id = sender.id;
            if (!id) {
                id = uniqueIDFromPrefix("popup");
                sender.setAttribute("id", id);
            }
            $popupEl.attr("aria-labelledby", id);
            $popupEl.attr("aria-describedby", id);
        }

        $.each($popupEl.find("a, input"), function (i, link) {
            if (link.tagName && link.tagName.toLowerCase() === "a") {
                link.setAttribute("tabindex", "0");
            }
        });

        const tabHandler = new TabHandler($popupEl[0]);

        tabHandler.getInitial()?.focus();
        if (!tabHandler) {
            return;
        }
        const elements = tabHandler.getAll() ?? [];
        elements.map((element, i) => {
            if (element.tagName.toLowerCase() === "a") {
                element.setAttribute("tabindex", "0");
            }
            element.addEventListener("keydown", (e) => {
                const tabKey = 9;
                if (e.keyCode === tabKey) {
                    if (!e.shiftKey) {
                        const nextElement = tabHandler.getNext(document.activeElement, false, true);
                        if (nextElement) {
                            e.preventDefault();
                            e.stopPropagation();
                            nextElement.focus();
                        }
                    } else {
                        const nextElement = tabHandler.getNext(document.activeElement, true, true);
                        if (nextElement) {
                            e.preventDefault();
                            e.stopPropagation();
                            nextElement.focus();
                        }
                    }
                }
            });
        });
    };
}

export default gdn as IGdn;
