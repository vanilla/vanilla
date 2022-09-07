/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { trackPageView, trackEvent, ViewType } from "@library/analytics/tracking";
import MockAdapter from "axios-mock-adapter/types";

describe("Anayltics Tracking", () => {
    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    it("trackPageView uses correct type in body", () => {
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackPageView("http://some-url.tld");
        expect(mockAdapter.history.post.length).toBe(1);
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("type");
        expect(JSON.parse(mockAdapter.history.post[0].data).type).toBe(ViewType.DEFAULT);
    });

    it("trackEvent makes a call to the tick API", () => {
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackEvent(ViewType.DEFAULT, {});
        expect(mockAdapter.history.post.length).toBe(1);
    });

    it("trackEvent passes context as request body", () => {
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackEvent(ViewType.DISCUSSION, { someKey: "someValue" });
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("someKey");
        expect(JSON.parse(mockAdapter.history.post[0].data).someKey).toBe("someValue");
    });
});
