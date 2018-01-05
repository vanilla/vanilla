/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import loadPolyfills from "@core/polyfills";
import events from "@core/events";
import * as utility from "@core/utility";

loadPolyfills().then(() => {
    utility.log("Bootstrapping");
    events.execute().then(() => {
        utility.log("Bootstrapping complete.");
    });
});
