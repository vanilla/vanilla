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
import { em, percent } from "csx";
import { userContentVariables } from "@library/content/userContentStyles";
import { Mixins } from "@library/styles/Mixins";

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
        ...{
            ".code": {
                position: "relative",
                display: "inline",
                verticalAlign: "middle",
                lineHeight: "inherit",
                fontSize: vars.fonts.size,
                fontFamily: vars.fonts.families,
                maxWidth: percent(100),
                margin: 0,
                color: ColorsUtils.colorOut(vars.inline.fg),
                backgroundColor: ColorsUtils.colorOut(vars.inline.bg),
                border: 0,
                overflowX: "auto",
                flexShrink: 0,
            },
            ".codeInline": {
                display: "inline",
                whiteSpace: "normal",
                ...Mixins.padding(vars.inline.paddings),
                borderRadius: vars.inline.border.radius,
                color: ColorsUtils.colorOut(vars.inline.fg),
                backgroundColor: ColorsUtils.colorOut(vars.inline.bg),
            },
            ".codeBlock": {
                display: "block",
                wordWrap: "normal",
                lineHeight: globalVars.lineHeights.code,
                whiteSpace: "pre",
                ...Mixins.padding(vars.block.paddings),
                borderRadius: vars.block.border.radius,
                color: ColorsUtils.colorOut(vars.block.fg),
                backgroundColor: ColorsUtils.colorOut(vars.block.bg),
            },
        },
    });
});
