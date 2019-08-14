/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import RouteHandler from "@library/routing/RouteHandler";
import { expect } from "chai";

function TestComponent() {
    return <React.Fragment />;
}

describe("RouteHandler", () => {
    afterEach(() => {
        RouteHandler.clearRouteCache();
    });

    it("caches route instances", () => {
        const key = "my-key";
        const route1 = new RouteHandler(() => Promise.resolve(TestComponent), "/route1", () => "/test", undefined, key);
        const route2 = new RouteHandler(
            () => Promise.resolve(TestComponent),
            "/route2/3/4/5/6",
            () => "/asdfasfd",
            undefined,
            key,
        );

        expect(route1.route).equals(route2.route, "Routes with the same must keep the same route instance");
    });
});
