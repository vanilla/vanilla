/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, unit } from "@library/styles/styleHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { NestedCSSProperties } from "typestyle/lib/types";
import { pageHeadingVariables } from "./pageHeadingStyles";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { iconVariables } from "@library/icons/iconStyles";

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

    const iconSizing = iconVariables().chevronLeftCompact(true);

    const smallBackLink = style("smallBackLink", {
        $nest: {
            [`& .${backLinkClasses().root}`]: {
                height: unit(iconSizing.height),
            },
            [`& .${backLinkClasses().link}`]: {
                height: unit(iconSizing.height),
            },
        },
    });

    return {
        root,
        smallBackLink,
    };
});
