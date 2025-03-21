/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, defaultTransition } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorHelper, linearGradient, percent, px, translateY } from "csx";
import { buttonResetMixin } from "@library/forms/buttonMixins";

export const collapsableContentClasses = useThemeCache((options?: { bgColor?: ColorHelper }) => {
    const globalVars = globalVariables();
    const style = styleFactory("collapsableContent");

    const paddingAdjustment = style("paddingAdjustment", {});
    const bgColor = options?.bgColor ?? globalVars.mainColors.bg;

    const root = style({
        background: ColorsUtils.colorOut(bgColor),
        position: "relative",
    });

    const heightContainer = style("heightContainer", {
        position: "relative",
        display: "block",
        overflow: "hidden",
    });

    const collapser = style("collapser", {
        ...{
            "&&": {
                ...buttonResetMixin(),
                borderRadius: 0,
                width: percent(100),
                height: styleUnit(globalVars.icon.sizes.default),
                padding: 0,
                margin: 0,
            },
        },
    });

    const footer = style("footer", {
        position: "relative",
        height: styleUnit(globalVars.icon.sizes.default),
    });

    const collapserIcon = style("collapserIcon", {
        ...defaultTransition("transform"),
        margin: "auto",
        height: styleUnit(globalVars.icon.sizes.default),
        display: "block",
    });

    const gradient = style("gradient", {
        ...absolutePosition.topLeft(),
        width: percent(100),
        height: 75,
        background: linearGradient("to bottom", ColorsUtils.colorOut(bgColor.fade(0))!, ColorsUtils.colorOut(bgColor)!),
        transform: `translateY(-100%)`,
        pointerEvents: "none",
    });

    return { heightContainer, root, collapser, collapserIcon, footer, gradient, paddingAdjustment };
});
