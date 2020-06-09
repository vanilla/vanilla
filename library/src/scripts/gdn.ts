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

gdn.focusedLastElement = () => {
    const lastElementClicked = document.activeElement
        ? (document.activeElement as HTMLElement)
        : (document.body as HTMLElement);
    return () => {
        if ("focus" in lastElementClicked) {
            lastElementClicked.focus();
        }
    };
};

gdn.makeAccessiblePopup = ($popupEl, settings, sender) => {
    console.log("propEl", $popupEl);
    console.log("settings", settings);
    console.log("sender", sender);
    let $popup = $popupEl.find("#" + settings.popupId);
    console.log("popup", $popup[0]);
    if (sender) {
        let id = sender.id;
        if (!id) {
            let unqiueID = uniqueIDFromPrefix("popup");
            sender.setAttribute("id", unqiueID);
            $popup.attr("id", unqiueID);
        } else {
            $popup.attr("aria-labelledby", id);
        }
    }

    // let id = settings.sender ? settings.sender.id : "nothing";

    //
    const tabHandler = new TabHandler($popup[0]);
    console.log("tabhandler", tabHandler);
    // // 2. Select first element tabHandler.getInitial()?.focus();
    const firstElement = tabHandler.getInitial()?.focus();
    console.log(firstElement);

    //useTabKeyboardHandler(popup.get(0));

    // set keyboard shortcuts from useTabKeyboardHandler.ts
    // allow you to tab through a loop down (tab) or loop up (shift + tab)
    // Handle escape (either from escape key or close, or hitting the grey area around)
    // call the gdn set last focus.
};

export default gdn as IGdn;
