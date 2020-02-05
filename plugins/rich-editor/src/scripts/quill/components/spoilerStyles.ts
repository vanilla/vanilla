/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { cssRule } from "typestyle";
import { colorOut } from "@library/styles/styleHelpersColors";
import { borders } from "@library/styles/styleHelpersBorders";
import { absolutePosition, margins, paddings, unit, userSelect, fonts } from "@library/styles/styleHelpers";
import { important, percent } from "csx";
import { userContentVariables } from "@library/content/userContentStyles";

export const spoilerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("spoiler");

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.large,
    });

    const colors = makeThemeVars("colors", {
        bg: globalVars.mixBgAndFg(0.95),
    });

    const borders = makeThemeVars("border", {
        color: globalVars.border.color,
        width: 0,
        radius: 2,
    });

    const button: IButtonType = makeThemeVars("spoilerButton", {
        name: "spoiler",
        colors: {
            bg: globalVars.elementaryColors.transparent,
        },
        fonts: {
            color: globalVars.mainColors.fg,
        },
        borders: {
            width: 0,
            radius: 0,
        },
    } as IButtonType);

    return {
        font,
        colors,
        borders,
        button,
    };
});

export const spoilerCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = spoilerVariables();
    const spoilerStyles = generateButtonStyleProperties(vars.button, false);
    const userContentVars = userContentVariables();
    cssRule(".spoiler-icon", {
        position: "relative",
        display: "block",
        margin: "auto",
        width: unit(globalVars.icon.sizes.default),
        height: unit(globalVars.icon.sizes.default),
        color: colorOut(userContentVars.blocks.fg),
    });
    cssRule(".spoiler", {
        backgroundColor: important(colorOut(userContentVars.blocks.bg) as string),
        color: important(colorOut(userContentVars.blocks.fg) as string),
        border: 0,
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
                background: colorOut(userContentVars.blocks.bg),
                color: colorOut(userContentVars.blocks.fg),
                width: percent(100),
                maxWidth: percent(100),
                fontSize: unit(vars.font.size),
                minHeight: unit(44),
                textTransform: "uppercase",
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
                alignItems: "center",
                ...paddings({
                    vertical: 0,
                    horizontal: globalVars.icon.sizes.default,
                }),
                width: percent(100),
                lineHeight: 1,
            },
            "& .spoiler-chevron": {
                ...absolutePosition.middleRightOfParent(globalVars.embed.text.padding),
                width: unit(globalVars.icon.sizes.default),
                height: unit(globalVars.icon.sizes.default),
                display: "flex",
                alignItems: "center",
                opacity: globalVars.states.icon.opacity,
            },
            "& .spoiler-warningLabel": {
                ...fonts({
                    size: globalVars.fonts.size.medium,
                    weight: globalVars.fonts.weights.semiBold,
                }),
                marginLeft: unit(6),
            },
            "& .spoiler-buttonContainer": {
                whiteSpace: "normal",
                userSelect: "none",
            },
        },
    });
});
