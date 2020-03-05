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

export const pageTitleClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const style = styleFactory("pageTitle");

    const root = style({
        fontSize: globalVars.fonts.size.title,
        lineHeight: vars.font.lineHeight,
        display: "block",
        ...margins({
            vertical: 0,
        }),
        $nest: lineHeightAdjustment(),
    } as NestedCSSProperties);

    return {
        root,
    };
});
