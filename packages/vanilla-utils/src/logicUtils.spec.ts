/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color } from "csx";
import { flattenObject, spaceshipCompare, unflattenObject } from "./logicUtils";
import { describe, it, expect } from "vitest";

describe("spaceshipCompare()", () => {
    it("compares two numbers", () => {
        expect(spaceshipCompare(1, 2)).lessThan(0);
        expect(spaceshipCompare(2, 1)).greaterThan(0);
        expect(spaceshipCompare(1, 1)).equals(0);
    });

    it("compares null to a number", () => {
        expect(spaceshipCompare(null, 1)).lessThan(0);
        expect(spaceshipCompare(1, null)).greaterThan(0);
        expect(spaceshipCompare(null, null)).equals(0);
    });
});

describe("flattenObject()", () => {
    const nested = {
        key1: "val1",
        isNull: null,
        nested: {
            nestedKey: "val2",
            array1: ["one", "two", "three"],
        },
        color: color("#000"),
        colorNoClass: { r: 0, g: 0, b: 0, a: 1, f: "rgb", o: false },
        multipleColors: {
            color1: "#000",
            color2: "#0F0",
        },
        errMultipleColors: {
            color1: "rgb(0,0,0)",
            color2: "rgb(0,255,0)",
            r: 0,
            g: 0,
            b: 0,
            a: 1,
            f: "rgb",
            o: false,
        },
        nestedColorObjects: {
            color1: color("#000"),
            color2: { r: 0, g: 0, b: 0, a: 1, f: "rgb", o: false },
        },
    };

    const flattened = {
        key1: "val1",
        isNull: null,
        "nested.nestedKey": "val2",
        "nested.array1": ["one", "two", "three"],
        color: "rgb(0,0,0)",
        colorNoClass: "rgb(0,0,0)",
        "multipleColors.color1": "#000",
        "multipleColors.color2": "#0F0",
        "errMultipleColors.color1": "rgb(0,0,0)",
        "errMultipleColors.color2": "rgb(0,255,0)",
        "nestedColorObjects.color1": "rgb(0,0,0)",
        "nestedColorObjects.color2": "rgb(0,0,0)",
    };

    const unflattened = {
        ...nested,
        color: "rgb(0,0,0)",
        colorNoClass: "rgb(0,0,0)",
        multipleColors: {
            color1: "#000",
            color2: "#0F0",
        },
        errMultipleColors: {
            color1: "rgb(0,0,0)",
            color2: "rgb(0,255,0)",
        },
        nestedColorObjects: {
            color1: "rgb(0,0,0)",
            color2: "rgb(0,0,0)",
        },
    };

    const things = ["one", "two"];

    it("flattens objects", () => {
        expect(flattenObject(nested)).deep.equal(flattened);
    });

    it("unflattens objects", () => {
        expect(unflattenObject(flattened)).deep.equal(unflattened);
    });
});
