/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "typestyle";
import { colorOut, modifyColorBasedOnLightness } from "@library/styles/styleHelpersColors";
import { em, percent } from "csx";
import { paddings } from "@library/styles/styleHelpersfPadding";
import { userContentVariables } from "@library/content/userContentStyles";
import { unit } from "@library/styles/styleHelpers";

export const codeBlockVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("blockQuote");

    const borderRadius = makeThemeVars("borderRadius", {
        default: 0,
    });

    const fonts = makeThemeVars("fonts", {
        size: em(0.85),
        families: `Menlo, Monaco, Consolas, "Courier New", monospace`,
    });

    const border = makeThemeVars("border", {
        radius: borderRadius.default,
    });

    const variablesUserContent = userContentVariables();

    const inline = makeThemeVars("inline", {
        fg: variablesUserContent.codeInline.fg,
        bg: variablesUserContent.codeInline.bg,
        border: {
            color: globalVars.mixBgAndFg(0.5),
            radius: 0,
        },
        paddings: {
            vertical: em(0.2),
            horizontal: em(0.4),
        },
    });

    const block = makeThemeVars("block", {
        fg: variablesUserContent.codeBlock.fg,
        bg: variablesUserContent.codeBlock.bg,
        border: {
            radius: borderRadius.default,
        },
        paddings: {
            all: globalVars.userContent.font.sizes.default,
        },
        maxHeight: variablesUserContent.codeBlock.maxHeight,
    });

    return {
        fonts,
        border,
        inline,
        block,
    };
});

export const codeBlockCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = codeBlockVariables();
    cssRule(".userContent", {
        $nest: {
            ".code": {
                position: "relative",
                display: "inline",
                verticalAlign: "middle",
                lineHeight: "inherit",
                fontSize: vars.fonts.size,
                fontFamily: vars.fonts.families,
                maxWidth: percent(100),
                margin: 0,
                color: colorOut(vars.inline.fg),
                backgroundColor: colorOut(vars.inline.bg),
                border: 0,
                overflowX: "auto",
                flexShrink: 0,
            },
            ".codeInline": {
                display: "inline",
                whiteSpace: "normal",
                ...paddings(vars.inline.paddings),
                borderRadius: vars.inline.border.radius,
                color: colorOut(vars.inline.fg),
                backgroundColor: colorOut(vars.inline.bg),
            },
            ".codeBlock": {
                display: "block",
                wordWrap: "normal",
                lineHeight: globalVars.lineHeights.code,
                whiteSpace: "pre",
                ...paddings(vars.block.paddings),
                borderRadius: vars.block.border.radius,
                color: colorOut(vars.block.fg),
                backgroundColor: colorOut(vars.block.bg),
                maxHeight: unit(vars.block.maxHeight),
                overflow: "auto",
            },
        },
    });
});
