/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, componentThemeVariables, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { px, percent } from "csx";
import { userSelect, borders, IBordersSameAllSidesStyles, unit, colorOut } from "@library/styles/styleHelpers";
import { shadowOrBorderBasedOnLightness, shadowHelper } from "@library/styles/shadowHelpers";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { NestedCSSProperties } from "typestyle/lib/types";

export const embedContainerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("embedContainer");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    const border: IBordersSameAllSidesStyles = makeThemeVars("border", {
        style: "none",
        width: 0,
        radius: px(4),
    });

    const title = makeThemeVars("title", {
        size: globalVars.fonts.size.medium,
        weight: globalVars.fonts.weights.bold,
    });

    const spacing = makeThemeVars("padding", {
        padding: 12,
    });

    return { border, spacing, colors, title };
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
        "&:focus": {
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
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                borders({
                    color: vars.border.color,
                }),
                shadowHelper().embed(),
            ),
            $nest: {
                // These 2 can't be joined together or their pseudselectors don't get created properly.
                "&.isLoading": {
                    cursor: "pointer",
                    $nest: hoverFocusStates,
                },
                "&.hasError": {
                    cursor: "pointer",
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
