/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RESOLVE_MAP, resolveFieldParams } from "@dashboard/layout/editor/widgetSettings/resolveFieldParams";

const tests = [
    {
        label: "none returns undefined",
        spec: {
            titleType: "none",
            title: "",
        },
        expected: {
            titleType: "none",
            title: undefined,
        },
    },
    {
        label: "static returns string value",
        spec: {
            titleType: "static",
            title: "test",
        },
        expected: {
            titleType: "static",
            title: "test",
        },
    },
    {
        label: "static replaces existing with empty string value",
        spec: {
            titleType: "static",
            title: {
                someKey: "someValue",
            },
        },
        expected: {
            titleType: "static",
            title: "",
        },
    },
    {
        label: "resolves to string in RESOLVE_MAP",
        spec: {
            titleType: "siteSection/name",
            title: {
                $hydrate: "param",
                ref: "siteSection/name",
            },
        },
        expected: {
            titleType: "siteSection/name",
            title: RESOLVE_MAP["siteSection/name"],
        },
    },
];

describe("resolveFieldParams", () => {
    it("Undefined returns an empty object", () => {
        const spec = undefined;
        const actual = resolveFieldParams(spec);
        expect(actual).toStrictEqual({});
    });
    it("Not title or description values return unchanged", () => {
        const spec = {
            mockKey: "Some value",
        };
        const actual = resolveFieldParams(spec);
        expect(actual).toStrictEqual(spec);
    });

    tests.forEach((test) => {
        it(test.label, () => {
            expect(resolveFieldParams(test.spec)).toStrictEqual(test.expected);
        });
    });
});
