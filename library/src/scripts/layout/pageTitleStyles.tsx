/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { CSSObject } from "@emotion/css";
import { pageHeadingVariables } from "./pageHeadingStyles";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { iconVariables } from "@library/icons/iconStyles";
import { Mixins } from "@library/styles/Mixins";

export const pageTitleClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const style = styleFactory("pageTitle");

    const root = style({
        fontSize: globalVars.fonts.size.title,
        lineHeight: vars.font.lineHeight,
        display: "block",
        ...Mixins.margin({
            vertical: 0,
        }),
        ...lineHeightAdjustment(),
    });

    const iconSizing = iconVariables().chevronLeftCompact(true);

    const smallBackLink = style("smallBackLink", {
        ...{
            [`.${backLinkClasses().root}`]: {
                height: styleUnit(iconSizing.height),
            },
            [`.${backLinkClasses().link}`]: {
                height: styleUnit(iconSizing.height),
            },
        },
    });

    return {
        root,
        smallBackLink,
    };
});
