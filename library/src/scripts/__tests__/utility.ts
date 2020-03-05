/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import MockAdapter from "axios-mock-adapter";
import apiv2 from "@library/apiv2";

/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
export function importAll(r: any) {
    r.keys().forEach(r);
}

/**
 * Wrap an API endoint with a mock wrapper.
 */
export function mockAPI() {
    const mock = new MockAdapter(apiv2, {});
    return mock;
}

/**
 * Report better errors if an unmocked endpoint is called.
 *
 * @param adapter
 */
export function applyAnyFallbackError(adapter: MockAdapter) {
    adapter.onAny().reply(config => {
        const query = config.paramsSerializer!(config.params);
        throw new Error(
            "No matching found for " + config.method!.toUpperCase() + " " + config.url + (query ? "?" + query : ""),
        );
    });
}
