/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { standardizeBorderRadius } from "@library/styles/styleHelpersBorders";
import { expect } from "chai";
import { px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { IBorderRadiusOutput, IBorderRadiusValue, IRadiusShorthand } from "@library/styles/cssUtilsTypes";
import { Mixins } from "@library/styles/Mixins";

describe("styleHelperBorders", () => {
    describe("standardizeBorderRadius", () => {
        describe("can transform shorthand into into the final form", () => {
            it("short hand declaration", () => {
                const input: IBorderRadiusValue = 24;
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "24px",
                    borderBottomRightRadius: "24px",
                    borderTopLeftRadius: "24px",
                    borderTopRightRadius: "24px",
                };

                expect(standardizeBorderRadius(input)).deep.eq(expected);
            });

            it("short hand declaration - important", () => {
                const input: IBorderRadiusValue = 24;
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "24px !important",
                    borderBottomRightRadius: "24px !important",
                    borderTopLeftRadius: "24px !important",
                    borderTopRightRadius: "24px !important",
                };
                expect(
                    standardizeBorderRadius(input, {
                        isImportant: true,
                    }),
                ).deep.eq(expected);
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

            it("Data already correct, just display - important:", () => {
                const input: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "24px !important",
                    borderBottomRightRadius: "24px !important",
                    borderTopLeftRadius: "24px !important",
                    borderTopRightRadius: "24px !important",
                };

                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "24px !important",
                    borderBottomRightRadius: "24px !important",
                    borderTopLeftRadius: "24px !important",
                    borderTopRightRadius: "24px !important",
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

            it("spreads the 'all' property over all of the radii - important", () => {
                const input: IRadiusShorthand = { all: 24 };
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "24px !important",
                    borderBottomRightRadius: "24px !important",
                    borderTopLeftRadius: "24px !important",
                    borderTopRightRadius: "24px !important",
                };

                expect(
                    standardizeBorderRadius(input, {
                        isImportant: true,
                    }),
                ).deep.eq(expected);
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

            it("takes more specific properties along with the all - important", () => {
                const input: IRadiusShorthand = {
                    all: 24,
                    left: 50,
                };
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "50px !important",
                    borderBottomRightRadius: "24px !important",
                    borderTopLeftRadius: "50px !important",
                    borderTopRightRadius: "24px !important",
                };

                expect(
                    standardizeBorderRadius(input, {
                        isImportant: true,
                    }),
                ).deep.eq(expected);
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

            it("real example - important", () => {
                const input: IRadiusShorthand = {
                    left: 0,
                    right: 29,
                };
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "0px !important",
                    borderBottomRightRadius: "29px !important",
                    borderTopLeftRadius: "0px !important",
                    borderTopRightRadius: "29px !important",
                };

                expect(
                    standardizeBorderRadius(input, {
                        isImportant: true,
                    }),
                ).deep.eq(expected);
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

            it("Test top bottom - important", () => {
                const input: IRadiusShorthand = {
                    top: 0,
                    bottom: 12,
                };
                const expected: IBorderRadiusOutput = {
                    borderBottomLeftRadius: "12px !important",
                    borderBottomRightRadius: "12px !important",
                    borderTopLeftRadius: "0px !important",
                    borderTopRightRadius: "0px !important",
                };

                expect(
                    standardizeBorderRadius(input, {
                        isImportant: true,
                    }),
                ).deep.eq(expected);
            });
        });
    });

    describe("borders", () => {
        const defaultColor = ColorsUtils.colorOut(globalVariables().border.color);
        it("Returns default values when there are no arguments", () => {
            const input = {};

            const expected = {
                borderBottomColor: defaultColor,
                borderBottomLeftRadius: "6px",
                borderBottomRightRadius: "6px",
                borderBottomStyle: "solid",
                borderBottomWidth: "1px",
                borderLeftColor: defaultColor,
                borderLeftStyle: "solid",
                borderLeftWidth: "1px",
                borderRightColor: defaultColor,
                borderRightStyle: "solid",
                borderRightWidth: "1px",
                borderTopColor: defaultColor,
                borderTopLeftRadius: "6px",
                borderTopRightRadius: "6px",
                borderTopStyle: "solid",
                borderTopWidth: "1px",
            };

            expect(Mixins.border(input)).deep.eq(expected);
        });

        it("Works with some minimal values", () => {
            const input = {
                style: "none",
                width: px(0),
                radius: px(4),
            };

            const expected = {
                borderBottomColor: defaultColor,
                borderBottomLeftRadius: input.radius,
                borderBottomRightRadius: input.radius,
                borderBottomStyle: input.style,
                borderBottomWidth: input.width,
                borderLeftColor: defaultColor,
                borderLeftStyle: input.style,
                borderLeftWidth: input.width,
                borderRightColor: defaultColor,
                borderRightStyle: input.style,
                borderRightWidth: input.width,
                borderTopColor: defaultColor,
                borderTopLeftRadius: input.radius,
                borderTopRightRadius: input.radius,
                borderTopStyle: input.style,
                borderTopWidth: input.width,
            };

            expect(Mixins.border(input)).deep.eq(expected);
        });
    });
});
