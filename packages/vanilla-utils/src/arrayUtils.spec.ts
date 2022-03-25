/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { insertAt, removeAt } from "./arrayUtils";

describe("insertAt()", () => {
    it("insert at the begining", () => {
        expect(insertAt([0, 1, 2, 3, 4], "a", 0)).toStrictEqual(["a", 0, 1, 2, 3, 4]);
    });

    it("insert at the middle", () => {
        expect(insertAt([0, 1, 2, 3, 4], "a", 2)).toStrictEqual([0, 1, "a", 2, 3, 4]);
    });

    it("index bigger than length", () => {
        expect(insertAt([0, 1, 2, 3, 4], "a", 15)).toStrictEqual([0, 1, 2, 3, 4, "a"]);
    });

    it("empty array and index === 0", () => {
        expect(insertAt([], "a", 0)).toStrictEqual(["a"]);
    });

    it("empty array and index === n", () => {
        expect(insertAt([], "a", 12)).toStrictEqual(["a"]);
    });
});

describe("removeAt()", () => {
    it("insert at the begining", () => {
        expect(removeAt([0, 1, 2, 3, 4], 0)).toStrictEqual([1, 2, 3, 4]);
    });

    it("insert at the middle", () => {
        expect(removeAt([0, 1, 2, 3, 4], 2)).toStrictEqual([0, 1, 3, 4]);
    });

    it("index bigger than length", () => {
        expect(removeAt([0, 1, 2, 3, 4], 15)).toStrictEqual([0, 1, 2, 3, 4]);
    });

    it("empty array and index === 0", () => {
        expect(removeAt([], 0)).toStrictEqual([]);
    });

    it("empty array and index === n", () => {
        expect(removeAt([], 12)).toStrictEqual([]);
    });
});
