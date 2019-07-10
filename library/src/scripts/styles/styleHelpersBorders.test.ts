/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    IBorderRadiusOutput,
    IBorderRadiusValue,
    IRadiusShorthand,
    IRadiusValue,
    standardizeBorderRadius,
} from "@library/styles/styleHelpersBorders";
import { expect } from "chai";

describe("styleHelperBorders", () => {
    it("will pass through final output untouched.");
    describe("can transform shorthand into into the final form", () => {
        it("short hand declaration", () => {
            const input: IBorderRadiusValue = 24;
            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "24px",
                borderBottomRightRadius: "24px",
                borderTopLeftRadius: "24px",
                borderTopRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input, true)).deep.eq(expected);
        });

        it("Data already correct, just display:", () => {
            const input: IBorderRadiusOutput = {
                borderBottomLeftRadius: "24px",
                borderBottomRightRadius: "24px",
                borderTopLeftRadius: "24px",
                borderTopRightRadius: "24px",
            };

            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "24px",
                borderBottomRightRadius: "24px",
                borderTopLeftRadius: "24px",
                borderTopRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("spreads the 'all' property over all of the radii", () => {
            const input: IRadiusShorthand = { all: 24 };
            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "24px",
                borderBottomRightRadius: "24px",
                borderTopLeftRadius: "24px",
                borderTopRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("takes more specific properties along with the all", () => {
            const input: IRadiusShorthand = {
                all: 24,
                left: 50,
            };
            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "50px",
                borderBottomRightRadius: "24px",
                borderTopLeftRadius: "50px",
                borderTopRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("real example", () => {
            const input: IRadiusShorthand = {
                left: 0,
                right: 29,
            };
            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "0px",
                borderBottomRightRadius: "29px",
                borderTopLeftRadius: "0px",
                borderTopRightRadius: "29px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("Test top bottom", () => {
            const input: IRadiusShorthand = {
                top: 0,
                bottom: 12,
            };
            const expected: IBorderRadiusOutput = {
                borderBottomLeftRadius: "12px",
                borderBottomRightRadius: "12px",
                borderTopLeftRadius: "0px",
                borderTopRightRadius: "0px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });
    });
});
