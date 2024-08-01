/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dateRangeToString, dateStringInUrlToDateRange } from "@library/search/SearchUtils";

describe("SearchUtils", () => {
    const mockDateRange = { start: "2024-04-24", end: "2024-04-26" };
    const mockOnlyOneDateInRange = { start: "2024-04-24", end: undefined };
    const mockDateRangeString = "[2024-04-24,2024-04-26]";
    const mockSmallerThanDateString = "<=2024-04-26";
    const mockSameDateString = "2024-04-26";

    it("Test dateRangeToString() convertor.", () => {
        const convertedResult = dateRangeToString(mockDateRange);

        expect(convertedResult).toBeDefined();
        expect(typeof convertedResult).toBe("string");
        expect(convertedResult?.includes(mockDateRange.start)).toBe(true);
        expect(convertedResult?.includes(mockDateRange.end)).toBe(true);

        // only start date, end is undefined
        const convertedResult2 = dateRangeToString(mockOnlyOneDateInRange);

        expect(convertedResult2).toBeDefined();
        expect(typeof convertedResult2).toBe("string");
        expect(convertedResult2?.includes(mockOnlyOneDateInRange.start)).toBe(true);
        expect(convertedResult2?.includes(">=")).toBe(true);
    });

    it("Test dateStringInUrlToDateRange() convertor.", () => {
        const convertedResult = dateStringInUrlToDateRange(mockDateRangeString);

        expect(convertedResult).toBeDefined();
        expect(typeof convertedResult).toBe("object");
        expect(convertedResult["start"]).toBe(mockDateRange.start);
        expect(convertedResult["end"]).toBe(mockDateRange.end);

        // only end, start was not in the string
        const convertedResult2 = dateStringInUrlToDateRange(mockSmallerThanDateString);
        expect(convertedResult2).toBeDefined();
        expect(typeof convertedResult2).toBe("object");
        expect(convertedResult2["start"]).toBeUndefined();
        expect(convertedResult2["end"]).toBe(mockDateRange.end);

        // one date, start and end are the same
        const convertedResult3 = dateStringInUrlToDateRange(mockSameDateString);
        expect(convertedResult3).toBeDefined();
        expect(typeof convertedResult3).toBe("object");
        expect(convertedResult3["start"]).toBe(mockSameDateString);
        expect(convertedResult2["end"]).toBe(mockSameDateString);
    });
});
