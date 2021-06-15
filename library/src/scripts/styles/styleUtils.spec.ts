/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { normalizeVariables } from "@library/styles/styleUtils";

const testData = {
    testFontFamilies: {
        array: [
            "Open Sans",
            "-apple-system",
            "BlinkMacSystemFont",
            "HelveticaNeue-Light",
            "Segoe UI",
            "Helvetica Neue",
            "Helvetica",
            "Raleway",
            "Arial",
            "sans-serif",
            "Apple Color Emoji",
            "Segoe UI Emoji",
            "Segoe UI Symbol",
        ],
        object: {
            0: "Open Sans",
            1: "-apple-system",
            2: "BlinkMacSystemFont",
            3: "HelveticaNeue-Light",
            4: "Segoe UI",
            5: "Helvetica Neue",
            6: "Helvetica",
            7: "Raleway",
            8: "Arial",
            9: "sans-serif",
            10: "Apple Color Emoji",
            11: "Segoe UI Emoji",
            12: "Segoe UI Symbol",
        },
    },
    fontWeights: {
        custom: {
            normal: 400,
            semiBold: "600",
            bold: null,
        },
        default: {
            normal: 0,
            semiBold: 0,
            bold: 0,
        },
    },
    color: {
        hex: "#COFFEE",
        rgb: "rgb(255,)",
        gradient: "linear-gradient(#1CE7EA, #COFFEE)",
        invalid: "😅",
    },
};

describe("normalizeVariables", () => {
    it("returns custom string value", () => {
        expect.assertions(4);
        const hexResult = normalizeVariables(testData.color.hex, "#0ff1CE");
        expect(hexResult).toStrictEqual(testData.color.hex);
        const rgbResult = normalizeVariables(testData.color.rgb, "#0ff1CE");
        expect(rgbResult).toStrictEqual(testData.color.rgb);
        const gradientResult = normalizeVariables(testData.color.gradient, "#0ff1CE");
        expect(gradientResult).toStrictEqual(testData.color.gradient);
        const invalidResult = normalizeVariables(testData.color.invalid, "#0ff1CE");
        expect(invalidResult).toStrictEqual(testData.color.invalid);
    });
    it("returns new object of defaults shape", () => {
        const result = normalizeVariables(testData.fontWeights.custom, {
            bold: 0,
        });
        expect(result).toStrictEqual({ bold: testData.fontWeights.custom.bold });
    });
    it("returns custom variables if both arguments are arrays", () => {
        const result = normalizeVariables(testData.testFontFamilies.array, []);
        expect(result).toStrictEqual(testData.testFontFamilies.array);
    });
    it("returns new object if both arguments are object", () => {
        const result = normalizeVariables(testData.fontWeights.custom, testData.fontWeights.default);
        expect(result).toStrictEqual(testData.fontWeights.custom);
    });
    it("returns custom variable if default is null", () => {
        const result = normalizeVariables(testData.fontWeights.custom, null);
        expect(result).toStrictEqual(testData.fontWeights.custom);
    });
    it("returns custom variable if arguments are array and object", () => {
        expect.assertions(2);
        const objectArray = normalizeVariables(testData.testFontFamilies.object, testData.testFontFamilies.array);
        expect(objectArray).toStrictEqual(testData.testFontFamilies.array);
        const arrayObject = normalizeVariables(testData.testFontFamilies.array, testData.testFontFamilies.object);
        expect(arrayObject).toStrictEqual(testData.testFontFamilies.object);
    });
});
