/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { absolutePosition } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { percent } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const accessibilityCSS = useThemeCache(() => {
    const globalVars = globalVariables();

    const fallbackContent = document.getElementById("fallbackPageContent");
    if (fallbackContent) {
        fallbackContent.remove(); // for accessibility, we can't have 2 <h1>s in the page.
    }

    cssRule(".accessibility-jumpTo", {
        border: styleUnit(0),
        clip: `rect(0 0 0 0)`,
        height: styleUnit(1),
        margin: styleUnit(-1),
        overflow: "hidden",
        padding: styleUnit(0),
        position: "absolute",
        width: styleUnit(1),
        ...{
            "&:focus": {
                position: "absolute",
                top: styleUnit(50),
                left: styleUnit(0),
                textAlign: "left",
                backgroundColor: ColorsUtils.colorOut(globalVars.elementaryColors.white),
                color: ColorsUtils.colorOut(globalVars.elementaryColors.black),
                display: "block",
                fontSize: styleUnit(globalVars.fonts.size.medium),
                clip: "auto",
                margin: styleUnit(0),
                height: "auto",
                ...Mixins.padding({
                    vertical: 0,
                    horizontal: 12,
                }),
                width: percent(100),
                zIndex: 2,
                transform: `translateY(-100%)`,
                opacity: 1,
            },
            "&:hover": {
                opacity: 1,
            },
        },
    });
});
