/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { BorderRadiusProperty } from "csstype";
import { ColorHelper } from "csx";
import { TLength, NestedCSSProperties } from "typestyle/lib/types";
import { ColorValues, isLightColor } from "@library/styles/styleHelpersColors";
import { borders } from "@library/styles/styleHelpersBorders";
import { unit } from "@library/styles/styleHelpers";

interface IShadowSizing {
    horizontalOffset: number;
    verticalOffset: number;
    blur: number;
    spread: number;
    opacity: number;
}

export const shadowVariables = useThemeCache(() => {
    const makeVariables = variableFactory("shadow");

    const widget: IShadowSizing = makeVariables("widget", {
        horizontalOffset: 0,
        verticalOffset: 1,
        blur: 3,
        spread: 0,
        opacity: 0.22,
    });

    const widgetHover: IShadowSizing = makeVariables("widgetHover", {
        horizontalOffset: 0,
        verticalOffset: 2,
        blur: 4,
        spread: 0,
        opacity: 0.22,
    });

    const dropDown: IShadowSizing = makeVariables("dropDown", {
        horizontalOffset: 0,
        verticalOffset: 5,
        blur: 10,
        spread: 0,
        opacity: 0.3,
    });

    const modal: IShadowSizing = makeVariables("modal", {
        horizontalOffset: 0,
        verticalOffset: 5,
        blur: 20,
        spread: 0,
        opacity: 0.5,
    });

    return { widget, widgetHover, dropDown, modal };
});

export const shadowHelper = useThemeCache(() => {
    const vars = shadowVariables();
    const globalVars = globalVariables();
    const shadowBaseColor = globalVars.mainColors.fg;
    const makeShadow = (opacity: number = 0.3) => `0 1px 3px 0 ${shadowBaseColor.fade(opacity)}`;
    const embed = (baseColor: ColorHelper = shadowBaseColor) => {
        const { verticalOffset, horizontalOffset, blur, spread, opacity } = vars.widget;
        return {
            boxShadow: `${horizontalOffset} ${unit(verticalOffset)} ${unit(blur)} ${unit(spread)} ${baseColor.fade(
                opacity,
            )}`,
        };
    };

    const embedHover = (baseColor: ColorHelper = shadowBaseColor) => {
        const { verticalOffset, horizontalOffset, blur, spread, opacity } = vars.widgetHover;
        return {
            boxShadow: `${horizontalOffset} ${unit(verticalOffset)} ${unit(blur)} ${unit(spread)} ${baseColor
                .darken(0.5)
                .fade(opacity)}`,
        };
    };

    const dropDown = (baseColor: ColorHelper = shadowBaseColor) => {
        const { verticalOffset, horizontalOffset, blur, spread, opacity } = vars.dropDown;
        return {
            boxShadow: `${horizontalOffset} ${unit(verticalOffset)} ${unit(blur)} ${unit(spread)} ${baseColor.fade(
                opacity,
            )}`,
        };
    };

    const modal = (baseColor: ColorHelper = shadowBaseColor) => {
        const { verticalOffset, horizontalOffset, blur, spread, opacity } = vars.modal;
        return {
            boxShadow: `${horizontalOffset} ${unit(verticalOffset)} ${unit(blur)} ${unit(spread)} ${baseColor.fade(
                opacity,
            )}`,
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
    referenceColor?: ColorValues,
    borderStyles?: object,
    shadowStyles?: object,
    flip?: boolean,
): NestedCSSProperties => {
    const globalVars = globalVariables();
    if (!referenceColor) {
        referenceColor = globalVars.mainColors.bg;
    }

    if (!borderStyles) {
        borderStyles = borders();
    }

    if (!shadowStyles) {
        shadowStyles = shadowHelper().dropDown();
    }

    if (referenceColor instanceof ColorHelper && isLightColor(referenceColor) && !flip) {
        // Shadow for light colors
        return shadowStyles;
    } else {
        // Border for dark colors
        return borderStyles;
    }
};
