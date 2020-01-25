/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { createMemoryHistory } from "history";
import { onPageView, initPageViewTracking } from "@library/pageViews/pageViewTracking";
import { expect } from "chai";
import sinon from "sinon";

describe("pageViewTracking", () => {
    it("can handle page views", () => {
        const history = createMemoryHistory();
        const spy = sinon.spy();
        onPageView(spy);
        initPageViewTracking(history);

        expect(spy.callCount).eq(1, "the initalization tracks a page view.");
        history.push("/test1");
        history.push("/test3");
        history.push("/test4");
        history.push("/test1");
        expect(spy.callCount).eq(5, "Further page views are tracked.");
    });

    it("can ignores changes in the hash", () => {
        const history = createMemoryHistory();
        const spy = sinon.spy();
        onPageView(spy);
        initPageViewTracking(history);

        expect(spy.callCount).eq(1, "the initalization tracks a page view.");
        history.push("/path2");
        history.push("/path2#testasd");
        history.push("/path2#90148");
        expect(spy.callCount).eq(2, "the hash is ignored");
        ``;
    });
});
