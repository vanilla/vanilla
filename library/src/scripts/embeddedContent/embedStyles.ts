/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { borders, colorOut, unit, userSelect } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const embedContainerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("embedContainer");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
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
        ".embed-isFocused &": {
            boxShadow: `0 0 0 ${px(
                globalVars.embed.select.borderWidth,
            )} ${globalVars.embed.focus.color.toString()} inset`,
        },
    };

    const sizes: { [x in EmbedContainerSize]: NestedCSSProperties } = {
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
        },
    };

    const makeRootClass = (size: EmbedContainerSize, inEditor: boolean, withPadding: boolean = true) =>
        style(size, {
            fontSize: unit(globalVars.fonts.size.medium),
            background: colorOut(vars.colors.bg),
            display: "block",
            position: "relative",
            textDecoration: "none",
            color: "inherit",
            margin: "auto",
            overflow: "hidden",
            padding: withPadding ? vars.spacing.padding : 0,
            ...(inEditor ? userSelect() : {}),
            ...sizes[size],
            ...borders(vars.border),
            ...shadowOrBorderBasedOnLightness(globalVars.body.backgroundImage.color, borders(), shadowHelper().embed()),
            $nest: {
                // These 2 can't be joined together or their pseudselectors don't get created properly.
                "&.isLoading": {
                    cursor: "pointer",
                    $nest: hoverFocusStates,
                },
                "&.hasError": {
                    cursor: "pointer",
                    background: colorOut(globalVars.messageColors.warning.bg),
                    color: colorOut(globalVars.messageColors.warning.fg),
                    $nest: hoverFocusStates,
                },
            },
        });

    const title = style("title", {
        $nest: {
            "&&": {
                // Nested for compatibility.
                fontSize: unit(vars.title.size),
                fontWeight: vars.title.weight,
                marginTop: 0,
                marginBottom: 4,
                display: "block",
                width: percent(100),
                padding: 0,
                lineHeight: globalVars.lineHeights.condensed,
                color: colorOut(globalVars.mainColors.fg),
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
