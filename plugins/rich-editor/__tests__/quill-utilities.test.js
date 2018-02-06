/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as quillUtilities from "../src/scripts/quill-utilities";

describe("Range/Boundary conversions", () => {
    it("converts range to boundary", () => {
        const input = {
            index: 4,
            length: 10,
        };
        const output = {
            start: 4,
            end: 13,
        };

        expect(quillUtilities.convertRangeToBoundary(input)).toEqual(output);
    });

    it("converts boundary to range", () => {
        const input = {
            start: 4,
            end: 13,
        };
        const output = {
            index: 4,
            length: 10,
        };

        expect(quillUtilities.convertBoundaryToRange(input)).toEqual(output);
    });
});

describe("expandRange", () => {
    it("expands backwards", () => {
        const initialRange = {
            index: 4,
            length: 8,
        };

        const startRange = {
            index: 1,
            length: 5,
        };

        const expectedRange = {
            index: 1,
            length: 11,
        };

        expect(quillUtilities.expandRange(initialRange, startRange)).toEqual(expectedRange);
    });
});
