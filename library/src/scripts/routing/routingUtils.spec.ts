/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { findMatchingPath } from "@library/routing/routingUtils";

const paths = [
    "/",
    "/discussions",
    "/discussions/unanswered",
    "/discussions/5-some-specific-discussion",
    "/discussions/5-some-specific-discussion/p2",
];

describe("findMatchingPath", () => {
    it("matches the home path correctly", () => {
        const path = "/";
        expect(findMatchingPath(paths, path)).toEqual("/");
    });

    it("matches a nested path correctly", () => {
        const path = "/discussions/unanswered";
        expect(findMatchingPath(paths, path)).toEqual("/discussions/unanswered");
    });

    it("accepts paths with trailing commas", () => {
        const path = "/discussions/unanswered/";
        expect(findMatchingPath(paths, path)).toEqual("/discussions/unanswered");
    });

    it("accepts paths with query parameters", () => {
        const path = "/discussions?sort=hot";
        expect(findMatchingPath(paths, path)).toEqual("/discussions");
    });

    it("matches the closest path", () => {
        const path = "/discussions/unanswered/p2";
        expect(findMatchingPath(paths, path)).toEqual("/discussions/unanswered");
    });

    it("returns null if there is no match", () => {
        const path = "/hello/1234";
        expect(findMatchingPath(paths, path)).toBeUndefined;
    });
});
