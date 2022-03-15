/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ArrayUtils } from "./ArrayUtils";

describe("ArrayUtils", () => {
    describe("insertAt()", () => {
        it("insert at the begining", () => {
            expect(ArrayUtils.insertAt([0, 1, 2, 3, 4], "a", 0)).toStrictEqual(["a", 0, 1, 2, 3, 4]);
        });

        it("insert at the middle", () => {
            expect(ArrayUtils.insertAt([0, 1, 2, 3, 4], "a", 2)).toStrictEqual([0, 1, "a", 2, 3, 4]);
        });

        it("index bigger than length", () => {
            expect(ArrayUtils.insertAt([0, 1, 2, 3, 4], "a", 15)).toStrictEqual([0, 1, 2, 3, 4, "a"]);
        });

        it("empty array and index === 0", () => {
            expect(ArrayUtils.insertAt([], "a", 0)).toStrictEqual(["a"]);
        });

        it("empty array and index === n", () => {
            expect(ArrayUtils.insertAt([], "a", 12)).toStrictEqual(["a"]);
        });
    });

    describe("removeAt()", () => {
        it("insert at the begining", () => {
            expect(ArrayUtils.removeAt([0, 1, 2, 3, 4], 0)).toStrictEqual([1, 2, 3, 4]);
        });

        it("insert at the middle", () => {
            expect(ArrayUtils.removeAt([0, 1, 2, 3, 4], 2)).toStrictEqual([0, 1, 3, 4]);
        });

        it("index bigger than length", () => {
            expect(ArrayUtils.removeAt([0, 1, 2, 3, 4], 15)).toStrictEqual([0, 1, 2, 3, 4]);
        });

        it("empty array and index === 0", () => {
            expect(ArrayUtils.removeAt([], 0)).toStrictEqual([]);
        });

        it("empty array and index === n", () => {
            expect(ArrayUtils.removeAt([], 12)).toStrictEqual([]);
        });
    });

    describe("swap()", () => {
        it("swap an item with itself", () => {
            expect(ArrayUtils.swap([0, 1, 2, 3, 4], 1, 1)).toStrictEqual([0, 1, 2, 3, 4]);
        });

        it("swap items next to each other", () => {
            expect(ArrayUtils.swap([0, 1, 2, 3, 4], 1, 2)).toStrictEqual([0, 2, 1, 3, 4]);
        });

        it("swap the ends", () => {
            expect(ArrayUtils.swap([0, 1, 2, 3, 4], 0, 4)).toStrictEqual([4, 1, 2, 3, 0]);
        });

        it("swap out of right bounds", () => {
            expect(() => {
                ArrayUtils.swap([0, 1, 2, 3, 4], 10, 3);
            }).toThrowError(/unsupported/);
            expect(() => {
                ArrayUtils.swap([0, 1, 2, 3, 4], 3, 10);
            }).toThrowError(/unsupported/);
        });

        it("refuses negative indexes", () => {
            expect(() => {
                ArrayUtils.swap([0, 1, 2, 3, 4], 4, -4);
            }).toThrowError(/unsupported/);
            expect(() => {
                ArrayUtils.swap([0, 1, 2, 3, 4], -4, 4);
            }).toThrowError(/unsupported/);
        });
    });

    describe("move", () => {
        it("can move an item left", () => {
            expect(ArrayUtils.move([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 6, 3)).toStrictEqual([0, 1, 2, 6, 3, 4, 5, 7, 8, 9]);
        });
        it("can move an item right", () => {
            expect(ArrayUtils.move([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 3, 6)).toStrictEqual([0, 1, 2, 4, 5, 6, 3, 7, 8, 9]);
        });
        it("can move an item to its own position", () => {
            expect(ArrayUtils.move([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 5, 5)).toStrictEqual([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
        });

        it("refuses negative indexes", () => {
            // A little awkward. Not sure what the expected behaviour would be here.
            expect(() => {
                ArrayUtils.move([0, 1, 2, 3, 4], 4, -4);
            }).toThrowError(/unsupported/);
            expect(() => {
                ArrayUtils.move([0, 1, 2, 3, 4], -4, 4);
            }).toThrowError(/unsupported/);
        });
    });
});
