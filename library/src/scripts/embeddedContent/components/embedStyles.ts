/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent, px } from "csx";
import { CSSObject } from "@emotion/css";

export const embedContainerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("embedContainer");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    });

    const border = makeThemeVars("border", {
        style: "none",
        width: 0,
        radius: px(4),
    });

    const title = makeThemeVars("title", {
        size: globalVars.fonts.size.medium,
        weight: globalVars.fonts.weights.bold,
    });

    const dimensions = makeThemeVars("dimensions", {
        maxEmbedWidth: 640,
    });

    const spacing = makeThemeVars("padding", {
        padding: 18,
    });

    return { border, spacing, colors, title, dimensions };
});

export const embedContainerClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = embedContainerVariables();
    const style = styleFactory("embed");

    const hoverFocusStates = {
        "&:hover": {
            boxShadow: `0 0 0 ${px(globalVars.embed.select.borderWidth)} ${globalVars.embed.focus.color.fade(
                0.5,
            )} inset`,
        },
        ".embed-isSelected &": {
            boxShadow: `0 0 0 ${px(
                globalVars.embed.select.borderWidth,
            )} ${globalVars.embed.focus.color.toString()} inset`,
        },
    };

    const sizes: { [x in EmbedContainerSize]: CSSObject } = {
        [EmbedContainerSize.SMALL]: {
            width: px(500),
            maxWidth: percent(100),
        },
        [EmbedContainerSize.MEDIUM]: {
            width: px(globalVars.embed.sizing.width),
            maxWidth: percent(100),
        },
        [EmbedContainerSize.FULL_WIDTH]: {
            maxWidth: percent(100),
            width: percent(100),
        },
    };

    const makeRootClass = (size: EmbedContainerSize, inEditor: boolean, withPadding: boolean = true) =>
        style(size, {
            fontSize: styleUnit(globalVars.fonts.size.medium),
            background: ColorsUtils.colorOut(vars.colors.bg),
            display: "block",
            position: "relative",
            textDecoration: "none",
            color: ColorsUtils.colorOut(vars.colors.fg),
            margin: "auto",
            padding: withPadding ? vars.spacing.padding : 0,
            ...(inEditor ? userSelect() : {}),
            ...sizes[size],
            ...Mixins.border(vars.border),
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                Mixins.border(),
                shadowHelper().embed(),
            ),
            ...{
                // These 2 can't be joined together or their pseudselectors don't get created properly.
                "&.isLoading": {
                    cursor: "pointer",
                    ...hoverFocusStates,
                },
                "&.hasError": {
                    cursor: "pointer",
                    background: ColorsUtils.colorOut(globalVars.messageColors.warning.bg),
                    color: ColorsUtils.colorOut(globalVars.messageColors.warning.fg),
                    ...hoverFocusStates,
                },
                "&.embedImage": {
                    border: 0,
                    boxShadow: "none",
                },
                "&.embedImage .embedExternal-content": {
                    ...shadowOrBorderBasedOnLightness(
                        globalVars.body.backgroundImage.color,
                        Mixins.border(),
                        shadowHelper().embed(),
                    ),
                },
            },
        });

    const title = style("title", {
        ...{
            "&&&": {
                // Nested for compatibility.
                fontSize: styleUnit(vars.title.size),
                fontWeight: vars.title.weight,
                marginTop: 0,
                marginBottom: 4,
                display: "block",
                width: percent(100),
                padding: 0,
                lineHeight: globalVars.lineHeights.condensed,
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                whiteSpace: "nowrap",
                overflow: "hidden",
                textOverflow: "ellipsis",
            },
        },
    });

    return { makeRootClass, title };
});

export const embedContentClasses = useThemeCache(() => {
    const style = styleFactory("embedContent");

    const small = style("small", {
        display: "inline-flex",
        width: "auto",
    });

    const root = style("root", {
        position: "relative",
    });

    return { small, root };
});
