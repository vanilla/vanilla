/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { createMemoryHistory } from "history";
import { onPageView, initPageViewTracking } from "@library/pageViews/pageViewTracking";

describe("pageViewTracking", () => {
    it("can handle page views", () => {
        const history = createMemoryHistory();
        const spy = vitest.fn();
        onPageView(spy);
        initPageViewTracking(history);

        expect(spy.mock.calls.length).eq(1, "the initalization tracks a page view.");
        expect(spy.mock.calls[0][0]).eq(history, "the history object is passed to handlers");
        history.push("/test1");
        history.push("/test3");
        history.push("/test4");
        history.push("/test1");
        expect(spy.mock.calls.length).eq(5, "Further page views are tracked.");
    });

    it("can ignores changes in the hash", () => {
        const history = createMemoryHistory();
        const spy = vitest.fn();
        onPageView(spy);
        initPageViewTracking(history);

        expect(spy.mock.calls.length).eq(1, "the initalization tracks a page view.");
        history.push("/path2");
        history.push("/path2#testasd");
        history.push("/path2#90148");
        expect(spy.mock.calls.length).eq(2, "the hash is ignored");
    });
});
