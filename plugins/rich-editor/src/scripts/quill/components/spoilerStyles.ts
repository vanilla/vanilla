/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { ButtonTypes } from "@library/forms/buttonStyles";
import generateButtonClass, { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { cssRule } from "typestyle";
import { colorOut } from "@library/styles/styleHelpersColors";
import { fonts } from "@library/styles/styleHelpersTypography";
import { NestedCSSProperties } from "typestyle/lib/types";
import { borders } from "@library/styles/styleHelpersBorders";
import { absolutePosition, margins, paddings, unit, userSelect } from "@library/styles/styleHelpers";
import { percent } from "csx";

export const spoilerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("spoiler");

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.large,
    });

    const colors = makeThemeVars("colors", {
        bg: globalVars.mixBgAndFg(0.95),
    });

    const border = makeThemeVars("border", {
        color: globalVars.border.color,
        radius: globalVars.border.radius,
    });

    const button: IButtonType = makeThemeVars("spoilerButton", {
        name: "spoiler",
        colors: {
            bg: globalVars.elementaryColors.transparent,
        },
        fonts: {
            color: globalVars.mainColors.fg,
        },
        border: {
            width: 0,
            radius: 0,
        },
    });

    return {
        font,
        colors,
        border,
        button,
    };
});

export const spoilerClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = spoilerVariables();
    const spoilerStyles = generateButtonStyleProperties(vars.button, false);
    cssRule(".spoiler", {
        ...borders({
            radius: vars.border.radius,
            color: vars.border.color,
        }),
        backgroundColor: colorOut(vars.colors.bg),
        $nest: {
            "& .spoiler-content": {
                display: "none",
                ...paddings({
                    all: globalVars.embed.text.padding,
                }),
            },
            "& .spoiler-content *:first-child": {
                marginTop: 0,
            },
            "& .spoiler-content *:last-child": {
                marginBottom: 0,
            },
            "& .spoiler-chevronUp": {
                display: "none",
                width: unit(globalVars.icon.sizes.default),
                height: unit(globalVars.icon.sizes.default),
                verticalAlign: ".2em",
            },
            "& .spoiler-chevronDown": {
                display: "none",
            },
            "&:not(.isShowingSpoiler) .spoiler-chevronDown": {
                display: "inline-block",
                width: unit(globalVars.icon.sizes.default),
                height: unit(globalVars.icon.sizes.default),
                verticalAlign: ".2em",
            },
            "&.isShowingSpoiler": {
                $nest: {
                    "& .spoiler-content": {
                        display: "block",
                    },
                    "& .spoiler-chevronUp": {
                        display: "inline-block",
                    },
                },
            },
            "& .button-spoiler": {
                ...spoilerStyles,
                ...userSelect(),
                width: percent(100),
                maxWidth: percent(100),
                fontSize: unit(vars.font.size),
                minHeight: unit(globalVars.icon.sizes.default * 2),
                ...paddings({
                    vertical: 0,
                    horizontal: globalVars.embed.text.padding,
                }),
                cursor: "pointer",
                textAlign: "center",
            },
            "& .button-spoiler:active .spoiler-chevron": {
                opacity: 1,
            },
            "& .button-spoiler:not(.focus-visible):focus .spoiler-chevron": {
                opacity: 1,
                outline: 0,
            },
            "& .button-spoiler.focus-visible .spoiler-chevron": {
                opacity: 1,
            },
            "& .button-spoiler:hover .spoiler-chevron": {
                opacity: 1,
                cursor: "pointer",
            },
            "& .spoiler-warningMain": {
                position: "relative",
                display: "flex",
                boxSizing: "border-box",
                justifyContent: "center",
                ...paddings({
                    vertical: 0,
                    horizontal: globalVars.icon.sizes.default,
                }),
                width: percent(100),
                lineHeight: 1,
            },
            "& .spoiler-icon": {
                position: "relative",
                width: unit(globalVars.icon.sizes.default),
                height: unit(globalVars.icon.sizes.default),
                marginRight: unit(12),
            },
            "& .spoiler-chevron": {
                ...absolutePosition.middleRightOfParent(globalVars.embed.text.padding),
                width: unit(globalVars.icon.sizes.default),
                height: unit(globalVars.icon.sizes.default),
                display: "flex",
                alignItems: "center",
                opacity: globalVars.states.icon.opacity,
            },
            "& .spoiler-warningAfter": {
                lineHeight: unit(globalVars.icon.sizes.default),
                margin: 0,
                padding: 0,
            },
            "& .spoiler-warningBefore": {
                lineHeight: unit(globalVars.icon.sizes.default),
                padding: 0,
                ...margins({
                    all: 0,
                    right: 6,
                }),
            },
            "& .spoiler-buttonContainer": {
                whiteSpace: "normal",
                userSelect: "none",
            },
        },
    });
});
