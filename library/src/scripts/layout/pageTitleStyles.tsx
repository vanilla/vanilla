/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { em, percent, px } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, margins, unit } from "@library/styles/styleHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { NestedCSSProperties, NestedCSSSelectors } from "typestyle/lib/types";
import { pageHeadingVariables } from "./pageHeadingStyles";
import { iconVariables } from "@library/icons/iconClasses";

export const pageTitleClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const chevronDimensions = iconVariables().chevronLeftCompact;
    const style = styleFactory("pageTitle");

    const chevronToFontRatio = 0.95;

    const root = style({
        fontSize: globalVars.fonts.size.title,
        lineHeight: vars.font.lineHeight,
        display: "block",
        ...margins({
            vertical: 0,
        }),
        $nest: lineHeightAdjustment(),
    } as NestedCSSProperties);

    const subTitleChevron = style("subTitleChevron", {
        $nest: {
            "&&": {
                width: `${chevronToFontRatio}ex`,
                fontSize: unit(globalVars.fonts.size.subTitle),
                height: `${(chevronToFontRatio * chevronDimensions.height) / chevronDimensions.width}ex`,
                marginBottom: "16px",
            },
        },
    });

    const subTitleBackLink = style("subTitleBackLink", {
        marginTop: "0.9ex",
    });

    return {
        root,
        subTitleChevron,
        subTitleBackLink,
    };
});
