/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "typestyle";
import { colorOut } from "@library/styles/styleHelpersColors";
import { absolutePosition, margins, paddings, unit } from "@library/styles/styleHelpers";
import { percent } from "csx";

export const accessibilityCSS = useThemeCache(() => {
    const globalVars = globalVariables();

    cssRule(".accessibility-jumpTo", {
        border: unit(0),
        clip: `rect(0 0 0 0)`,
        height: unit(1),
        margin: unit(-1),
        overflow: "hidden",
        padding: unit(0),
        position: "absolute",
        width: unit(1),
        $nest: {
            "&:focus": {
                position: "absolute",
                top: unit(50),
                left: unit(0),
                textAlign: "left",
                backgroundColor: colorOut(globalVars.elementaryColors.white),
                color: colorOut(globalVars.elementaryColors.black),
                display: "block",
                fontSize: unit(globalVars.fonts.size.medium),
                clip: "auto",
                margin: unit(0),
                height: "auto",
                ...paddings({
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
