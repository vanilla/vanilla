import loadPolyfills from "./polyfills";
import events from "@core/events";
import * as utility from "@core/utility";

loadPolyfills().then(() => {
    utility.log("Bootstrapping");
    events.execute().then(() => {
        utility.log("Bootstrapping complete.");
    });
});
