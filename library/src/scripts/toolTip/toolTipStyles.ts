/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { colorOut } from "@library/styles/styleHelpersColors";
import { borders, paddings, singleBorder, unit, userSelect } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { translateX, translateY } from "csx";

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

    const nub = makeThemeVars("nub", {
        width: 12,
    });

    return {
        sizes,
        border,
        nub,
    };
});

export const toolTipClasses = useThemeCache(() => {
    const style = styleFactory("toolTip");
    const globalVars = globalVariables();
    const vars = tooltipVariables();
    const shadow = shadowHelper();

    const box = style("box", {
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

    const nubPosition = style("nubPosition", {
        position: "absolute",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "center",
        overflow: "hidden",
        width: unit(vars.nub.width * 2),
        height: unit(vars.nub.width),
        ...userSelect(),
        transform: translateX("-50%"),
        pointerEvents: "none",
        zIndex: 1,
    });

    const nub = style("nub", {
        position: "relative",
        display: "block",
        width: unit(vars.nub.width),
        height: unit(vars.nub.width),
        borderTop: singleBorder({
            width: globalVars.border.width,
        }),
        borderRight: singleBorder({
            width: globalVars.border.width,
        }),
        boxShadow: globalVars.overlay.dropShadow,
        background: colorOut(globalVars.mainColors.bg),
        $nest: {
            [`&.isUp`]: {
                marginTop: unit(vars.nub.width - globalVars.border.width * 2 + 1),
                transform: `rotate(-45deg) translateY(-50%)`,
            },
            [`&.isDown`]: {
                transform: translateY(unit(vars.nub.width) as string),
            },
        },
    });

    return {
        box,
        nub,
        nubPosition,
    };
});
