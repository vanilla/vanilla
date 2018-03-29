/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import loadPolyfills from "@core/bootstrap/polyfills";
import { onContent, getMeta, _executeReady } from "@core/application";
import * as utility from "@core/utility";
import { _mountComponents } from "@core/internal";

utility.debug(getMeta('debug', false));

onContent((e) => {
    _mountComponents(e.target);
});

loadPolyfills().then(() => {
    utility.log("Bootstrapping");
    _executeReady().then(() => {
        utility.log("Bootstrapping complete.");

        const contentEvent = new CustomEvent('X-DOMContentReady', { bubbles: true, cancelable: false });
        document.dispatchEvent(contentEvent);
    });
});
