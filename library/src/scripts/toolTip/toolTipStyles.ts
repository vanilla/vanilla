/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { singleBorder, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { translateX, percent, px, important } from "csx";
import { modalVariables } from "@library/modal/modalStyles";

export const tooltipVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("toolTips");
    const globalVars = globalVariables();

    // Main colors
    const sizes = makeThemeVars("sizes", {
        min: 150,
        max: 250,
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

    const noPointerContent = style("content", {
        position: "relative",
        display: "inline-flex",
        ...{
            "& *": {
                pointerEvents: "none",
            },
        },
    });

    const noPointerTrigger = style("noPointerTrigger", {
        pointerEvents: important("initial"),
        position: "absolute",
        top: percent(50),
        left: percent(50),
        minWidth: px(45),
        minHeight: px(45),
        transform: "translate(-50%, -50%)",
        zIndex: 1,
    });

    const box = style("box", {
        position: "absolute",
        fontSize: styleUnit(globalVars.fonts.size.medium),
        minWidth: styleUnit(vars.sizes.min),
        maxWidth: styleUnit(vars.sizes.max),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        lineHeight: globalVars.lineHeights.base,
        ...Mixins.border(),
        ...Mixins.padding({
            all: globalVars.fonts.size.medium,
        }),
        ...shadow.dropDown(),
        zIndex: modalVariables().sizing.zIndex + 1,
    });

    const nubPosition = style("nubPosition", {
        position: "absolute",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        overflow: "hidden",
        width: styleUnit(vars.nub.width * 2),
        height: styleUnit(vars.nub.width * 2),
        transform: translateX("-50%"),
        marginLeft: styleUnit(vars.nub.width),
        pointerEvents: "none",
        zIndex: modalVariables().sizing.zIndex + 2,
        ...userSelect(),
    });

    const nub = style("nub", {
        position: "relative",
        display: "block",
        width: styleUnit(vars.nub.width),
        height: styleUnit(vars.nub.width),
        borderTop: singleBorder({
            width: globalVars.border.width,
        }),
        borderRight: singleBorder({
            width: globalVars.border.width,
        }),
        boxShadow: globalVars.overlay.dropShadow,
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        zIndex: 1,
        ...{
            [`&.isUp`]: {
                transform: `rotate(-45deg)`,
            },
            [`&.isDown`]: {
                transform: `rotate(135deg)`,
            },
        },
    });

    return {
        box,
        nub,
        nubPosition,
        noPointerContent,
        noPointerTrigger,
    };
});
