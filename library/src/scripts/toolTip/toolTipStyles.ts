/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
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
import { css } from "@emotion/css";
import { ColorVar } from "@library/styles/CssVar";

export const tooltipVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("toolTips");
    const globalVars = globalVariables();

    // Main colors
    const sizes = makeThemeVars("sizes", {
        min: 150,
        max: 320,
    });

    const nub = makeThemeVars("nub", {
        width: 12,
    });

    return {
        sizes,
        nub,
    };
});

export const toolTipClasses = useThemeCache((customWidth?: number) => {
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
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
            lineHeight: globalVars.lineHeights.base,
        }),
        minWidth: customWidth ?? styleUnit(vars.sizes.min),
        maxWidth: styleUnit(vars.sizes.max),
        backgroundColor: ColorsUtils.var(ColorVar.Background),
        color: ColorsUtils.var(ColorVar.Foreground),
        ...Mixins.border(),
        ...Mixins.padding({
            all: globalVars.fonts.size.medium,
        }),
        "&.noPadding": {
            padding: 0,
        },
        ...shadow.toolbar(),
    });

    const boxStackingLevel = (zIndex: number = 1) =>
        css({
            zIndex,
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
        ...userSelect(),
    });

    const nubStackingLevel = (zIndex: number = 1) =>
        css({
            zIndex: zIndex + 1, // Nubs always appear above the box
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
        boxShadow: `4px -4px 4px rgba(0, 0, 0, 0.05)`,
        background: ColorsUtils.var(ColorVar.Background),
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
        nubStackingLevel,
        boxStackingLevel,
    };
});
