/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { important, percent } from "csx";
import { userContentVariables } from "@library/content/UserContent.variables";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const spoilerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("spoiler");

    const font = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("large"),
        }),
    );

    const colors = makeThemeVars("colors", {
        bg: globalVars.mixBgAndFg(0.95),
    });

    const borders = makeThemeVars("border", {
        color: globalVars.border.color,
        width: 0,
        radius: 2,
    });

    const button = makeThemeVars(
        "spoilerButton",
        Variables.button({
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
        }),
    );

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
    const spoilerStyles = Mixins.button(vars.button);
    const userContentVars = userContentVariables();
    cssRule(".spoiler-icon", {
        position: "relative",
        display: "block",
        margin: "auto",
        width: styleUnit(globalVars.icon.sizes.default),
        height: styleUnit(globalVars.icon.sizes.default),
        color: "currentColor",
    });
    cssRule(".spoiler", {
        backgroundColor: important(ColorsUtils.colorOut(userContentVars.blocks.bg) as string),
        color: important(ColorsUtils.colorOut(userContentVars.blocks.fg) as string),
        border: 0,
        ...{
            ".spoiler-content": {
                display: "none",
                ...Mixins.padding({
                    all: globalVars.embed.text.padding,
                }),
                "& .spoiler-line": {
                    ...Mixins.margin({ vertical: 8 }),
                },
            },
            ".spoiler-content *:first-child": {
                marginTop: 0,
            },
            ".spoiler-content *:last-child": {
                marginBottom: 0,
            },
            ".spoiler-chevronUp": {
                display: "none",
                width: styleUnit(globalVars.icon.sizes.default),
                height: styleUnit(globalVars.icon.sizes.default),
                verticalAlign: ".2em",
            },
            ".spoiler-chevronDown": {
                display: "none",
            },
            "&:not(.isShowingSpoiler) .spoiler-chevronDown": {
                display: "inline-block",
                width: styleUnit(globalVars.icon.sizes.default),
                height: styleUnit(globalVars.icon.sizes.default),
                verticalAlign: ".2em",
            },
            "&.isShowingSpoiler": {
                ...{
                    ".spoiler-content": {
                        display: "block",
                    },
                    ".spoiler-chevronUp": {
                        display: "inline-block",
                    },
                },
            },
            ".button-spoiler": {
                ...spoilerStyles,
                ...userSelect(),
                background: ColorsUtils.colorOut(userContentVars.blocks.bg),
                color: ColorsUtils.colorOut(userContentVars.blocks.fg),
                width: percent(100),
                maxWidth: percent(100),
                fontSize: styleUnit(vars.font.size),
                minHeight: styleUnit(44),
                textTransform: "uppercase",
                ...Mixins.padding({
                    vertical: 0,
                    horizontal: globalVars.embed.text.padding,
                }),
                cursor: "pointer",
                textAlign: "center",
            },
            ".button-spoiler:active .spoiler-chevron": {
                opacity: 1,
            },
            ".button-spoiler:not(.focus-visible):focus .spoiler-chevron": {
                opacity: 1,
                outline: 0,
            },
            ".button-spoiler.focus-visible .spoiler-chevron": {
                opacity: 1,
            },
            ".button-spoiler:hover .spoiler-chevron": {
                opacity: 1,
                cursor: "pointer",
            },
            ".spoiler-warningMain": {
                position: "relative",
                display: "flex",
                boxSizing: "border-box",
                justifyContent: "center",
                alignItems: "center",
                ...Mixins.padding({
                    vertical: 0,
                    horizontal: globalVars.icon.sizes.default,
                }),
                width: percent(100),
                lineHeight: 1,
            },
            ".spoiler-chevron": {
                ...Mixins.absolute.middleRightOfParent(globalVars.embed.text.padding),
                width: styleUnit(globalVars.icon.sizes.default),
                height: styleUnit(globalVars.icon.sizes.default),
                display: "flex",
                alignItems: "center",
                opacity: globalVars.states.icon.opacity,
            },
            ".spoiler-warningLabel": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
                }),
                marginLeft: styleUnit(6),
            },
            ".spoiler-buttonContainer": {
                whiteSpace: "normal",
                userSelect: "none",
            },
        },
    });
});
