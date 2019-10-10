/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { colorOut } from "@library/styles/styleHelpersColors";
import { borders, paddings, unit } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const tooltipVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("toolTips");
    const globalVars = globalVariables();

    // Main colors
    const sizes = makeThemeVars("sizes", {
        default: 205,
    });

    const border = makeThemeVars("radius", {
        radius: 6,
    });

    return {
        sizes,
        border,
    };
});

export const toolTipClasses = useThemeCache(() => {
    const style = styleFactory("toolTip");
    const globalVars = globalVariables();
    const vars = tooltipVariables();
    const shadow = shadowHelper();

    const box = style({
        fontSize: unit(globalVars.fonts.size.medium),
        width: unit(vars.sizes.default),
        color: colorOut(globalVars.mainColors.fg),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        lineHeight: globalVars.lineHeights.base,
        ...borders(vars.border),
        ...paddings({
            all: globalVars.fonts.size.medium,
        }),
        ...shadow.dropDown(),
    });

    return {
        box,
    };
});
