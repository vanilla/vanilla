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

    const nub = makeThemeVars("nub", {
        width: 12,
    });

    return {
        sizes,
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
        ...borders(),
        ...paddings({
            all: globalVars.fonts.size.medium,
        }),
        ...shadow.dropDown(),
    });

    const nubPosition = style("nubPosition", {
        position: "absolute",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        overflow: "hidden",
        width: unit(vars.nub.width * 2),
        height: unit(vars.nub.width * 2),
        transform: translateX("-50%"),
        marginLeft: unit(vars.nub.width),
        pointerEvents: "none",
        zIndex: 1,
        ...userSelect(),
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
        zIndex: 1,
        $nest: {
            [`&.isUp`]: {
                // marginTop: unit(vars.nub.width - globalVars.border.width),
                // transform: `rotate(-45deg) translateY(-50%)`,
                transform: `rotate(-45deg)`,
            },
            [`&.isDown`]: {
                // transform: `rotate(45deg)  translateY(unit(vars.nub.width) )`,
                transform: `rotate(135deg)`,
            },
        },
    });

    return {
        box,
        nub,
        nubPosition,
    };
});
