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
    describe.only("can transform shorthand into into the final form", () => {
        it("short hand declaration", () => {
            const input: IBorderRadiusValue = 24;
            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "24px",
                bottomRightRadius: "24px",
                topLeftRadius: "24px",
                topRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("Data already correct, just display:", () => {
            const input: IBorderRadiusOutput = {
                bottomLeftRadius: "24px",
                bottomRightRadius: "24px",
                topLeftRadius: "24px",
                topRightRadius: "24px",
            };

            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "24px",
                bottomRightRadius: "24px",
                topLeftRadius: "24px",
                topRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("spreads the 'all' property over all of the radii", () => {
            const input: IRadiusShorthand = { all: 24 };
            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "24px",
                bottomRightRadius: "24px",
                topLeftRadius: "24px",
                topRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("takes more specific properties along with the all", () => {
            const input: IRadiusShorthand = {
                all: 24,
                left: 50,
            };
            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "50px",
                bottomRightRadius: "24px",
                topLeftRadius: "50px",
                topRightRadius: "24px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("real example", () => {
            const input: IRadiusShorthand = {
                left: 0,
                right: 49,
            };
            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "0px",
                bottomRightRadius: "29px",
                topLeftRadius: "0px",
                topRightRadius: "29px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });

        it("real example", () => {
            const input: IRadiusShorthand = {
                top: 0,
                bottom: 12,
            };
            const expected: IBorderRadiusOutput = {
                bottomLeftRadius: "12px",
                bottomRightRadius: "12px",
                topLeftRadius: "0px",
                topRightRadius: "0px",
            };

            expect(standardizeBorderRadius(input)).deep.eq(expected);
        });
    });
});
