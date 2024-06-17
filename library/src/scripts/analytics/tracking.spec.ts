/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { ViewType, trackEvent, trackExternalNavigation, trackPageView } from "@library/analytics/tracking";
import { setMeta } from "@library/utility/appUtils";
import MockAdapter from "axios-mock-adapter/types";

describe("Anayltics Tracking", () => {
    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();

        setMeta("siteSection", {
            sectionID: "example-section-id",
        });
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

    it("trackEvent passes siteSectionID if specified", () => {
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackPageView("http://some-url.tld");
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("siteSectionID");
        expect(JSON.parse(mockAdapter.history.post[0].data).siteSectionID).toBe("example-section-id");
    });

    it("trackEvent passes groupID if specified", () => {
        setMeta("groupID", 100);
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackPageView("http://some-url.tld");
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("groupID");
        expect(JSON.parse(mockAdapter.history.post[0].data).groupID).toBe(100);
    });

    it("trackEvent passes eventID if specified", () => {
        setMeta("eventID", 200);
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        trackPageView("http://some-url.tld");
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("eventID");
        expect(JSON.parse(mockAdapter.history.post[0].data).eventID).toBe(200);
    });

    it("trackExternalNavigation does not call tick API if link is same domain", () => {
        vi.stubGlobal("location", { href: "http://example.com/some-page", origin: "http://example.com" });
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        const link = document.createElement("a");
        link.href = "http://example.com/internal-page";
        trackExternalNavigation(new Event("click"), link);
        expect(mockAdapter.history.post.length).toBe(0);
    });

    it("trackExternalNavigation does not call tick API if link is relative", () => {
        vi.stubGlobal("location", { href: "http://example.com/some-page", origin: "http://example.com" });
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        const link = document.createElement("a");
        link.href = "/internal-page";
        trackExternalNavigation(new Event("click"), link);
        expect(mockAdapter.history.post.length).toBe(0);
    });

    it("trackExternalNavigation does not call tick API if on the leaving page", () => {
        vi.stubGlobal("location", {
            href: `http://example.com/home/leaving?target=${encodeURI("http://external.com")}`,
            origin: "http://example.com",
        });
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        const link = document.createElement("a");
        link.href = "http://external.com";
        trackExternalNavigation(new Event("click"), link);
        expect(mockAdapter.history.post.length).toBe(0);
    });

    it("trackExternalNavigation calls tick API if link is not same domain", () => {
        vi.stubGlobal("location", { href: "http://example.com/some-page", origin: "http://example.com" });
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        const link = document.createElement("a");
        link.href = "http://external.com";
        trackExternalNavigation(new Event("click"), link);
        expect(mockAdapter.history.post.length).toBe(1);
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("type");
        expect(JSON.parse(mockAdapter.history.post[0].data).type).toBe("externalNavigation");
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("destinationUrl");
        expect(JSON.parse(mockAdapter.history.post[0].data).destinationUrl).toBe("http://external.com");
    });

    it("trackExternalNavigation calls tick API if link is going to leaving page first", () => {
        vi.stubGlobal("location", { href: "http://example.com/some-page", origin: "http://example.com" });
        mockAdapter.onPost(/tick/).replyOnce(201, {});
        const link = document.createElement("a");
        link.href = `http://example.com/home/leaving?target=${encodeURI("http://external.com")}`;
        trackExternalNavigation(new Event("click"), link);
        expect(mockAdapter.history.post.length).toBe(1);
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("type");
        expect(JSON.parse(mockAdapter.history.post[0].data).type).toBe("externalNavigation");
        expect(JSON.parse(mockAdapter.history.post[0].data)).toHaveProperty("destinationUrl");
        expect(JSON.parse(mockAdapter.history.post[0].data).destinationUrl).toBe("http://external.com");
    });
});
