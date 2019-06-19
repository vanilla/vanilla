/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";
import { BorderRadiusProperty } from "csstype";
import { ColorHelper } from "csx";
import { TLength } from "typestyle/lib/types";
import { ColorValues } from "@library/styles/styleHelpersColors";

export const shadowHelper = useThemeCache(() => {
    const globalVars = globalVariables();
    const shadowBaseColor = globalVars.mainColors.bg;
    const makeShadow = (opacity: number = 0.3) => `0 1px 3px 0 ${shadowBaseColor.fade(opacity)}`;
    const embed = (baseColor: ColorHelper = shadowBaseColor) => {
        return {
            boxShadow: `0 1px 3px 0 ${baseColor.fade(0.3)}`,
        };
    };

    const embedHover = (baseColor: ColorHelper = shadowBaseColor) => {
        return {
            boxShadow: `0 1px 3px 0 ${baseColor.fade(0.3)}, 0 2px 4px 0 ${baseColor.darken(0.5).fade(0.22)}`,
        };
    };

    const dropDown = (baseColor: ColorHelper = shadowBaseColor) => {
        return {
            boxShadow: `0 5px 10px 0 ${baseColor.fade(0.3)}`,
        };
    };

    const modal = (baseColor: ColorHelper = shadowBaseColor) => {
        return {
            boxShadow: `0 5px 20px ${baseColor.fade(0.5)}`,
        };
    };

    const contrast = (
        baseColor: ColorHelper = globalVars.mainColors.fg,
        hasBorder: boolean = false,
        borderRadius: BorderRadiusProperty<TLength> = 0,
    ) => {
        const shadowColor = baseColor.fade(0.2);
        let border = {};

        if (hasBorder) {
            border = {
                outline: `solid 1px ${shadowColor.toString()}`,
                radius: borderRadius,
            };
        }

        return {
            boxShadow: `0 0 3px 0 ${baseColor.fade(0.3)}`,
            ...border,
        };
    };

    return { embed, embedHover, dropDown, modal, contrast, makeShadow };
});

export const shadowOrBorderBasedOnLightness = (
    referenceColor: ColorValues,
    borderStyles: object,
    shadowStyles: object,
    flip?: boolean,
) => {
    if (referenceColor instanceof ColorHelper && referenceColor.lightness() >= 0.5 && !flip) {
        // Shadow for light colors
        return shadowStyles;
    } else {
        // Border for dark colors
        return borderStyles;
    }
};
