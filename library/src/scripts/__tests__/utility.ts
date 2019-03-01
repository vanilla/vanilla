/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import MockAdapter from "axios-mock-adapter";
import apiv2 from "@library/apiv2";
import rewiremock from "rewiremock";
import { createMockStore } from "redux-test-utils";

/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
export function importAll(r: any) {
    r.keys().forEach(r);
}

export function mockApi() {
    const api = new MockAdapter(apiv2);
    rewiremock(() => import("@library/apiv2"))
        .withDefault(api as any)
        .dynamic();
    return api;
}

export function mockStore(storeState: any) {
    const store = createMockStore(storeState);
    rewiremock(() => import("@library/state/getStore"))
        .withDefault(() => store)
        .dynamic();
    return store;
}
