/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getFilteredValue } from "@library/routing/QueryString";

describe("QueryString - getFilteredValue()", () => {
    const value = { one: 1, two: 2, three: undefined, four: 4, five: undefined };
    const defaults = { two: 2, four: 4, six: 6 };
    const paramsFromUrl = { one: 1, two: 2, six: 6, seven: 7 };
    it("Should return desired query string object", () => {
        const resultNoValues = getFilteredValue({ one: undefined }, defaults, {});
        const resultNoDefaults = getFilteredValue(value, {}, {});
        const resultNoOtherParamsInUrl = getFilteredValue(value, defaults, {});
        const resultWithOtherParamsInUrl = getFilteredValue(value, defaults, paramsFromUrl);

        expect(resultNoValues).toBeNull();
        expect(resultNoDefaults).toMatchObject({ one: 1, two: 2, four: 4 });
        expect(resultNoOtherParamsInUrl).toMatchObject({ one: 1 });
        expect(resultWithOtherParamsInUrl).toMatchObject({ one: 1, seven: 7 });
    });
});
