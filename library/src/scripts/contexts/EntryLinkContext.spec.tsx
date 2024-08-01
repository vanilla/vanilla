/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { makeContextualAuthLink } from "@library/contexts/EntryLinkContext";

describe("makeContextualAuthLink", () => {
    it("applies extra query params", () => {
        expect(makeContextualAuthLink("/entry/signin", { hello: "world" })).toBe(
            "http://localhost/entry/signin?hello=world&target=http%3A%2F%2Flocalhost%2F",
        );
    });

    it("preserves existing query params", () => {
        expect(makeContextualAuthLink("/entry/signin?existing=foo", { hello: "world" })).toBe(
            "http://localhost/entry/signin?existing=foo&hello=world&target=http%3A%2F%2Flocalhost%2F",
        );
    });

    it("preserves existing domains", () => {
        expect(makeContextualAuthLink("https://other.domain/oauth?existing=foo", { hello: "world" })).toBe(
            "https://other.domain/oauth?existing=foo&hello=world&target=http%3A%2F%2Flocalhost%2F",
        );
    });
});
