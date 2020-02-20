/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent, color } from "csx";
import { paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { NestedCSSProperties } from "typestyle/lib/types";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";

export const containerVariables = useThemeCache(() => {
    const vars = layoutVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("containerVariables");

    const smallPadding = panelWidgetVariables().spacing.padding;

    let spacingInit = makeThemeVars("spacing", {
        padding: {
            horizontal: vars.gutter.size,
        },
        paddingMobile: {
            horizontal: smallPadding,
        },
    });

    const spacing = makeThemeVars("spacing", {
        ...spacingInit,
        paddingFull: {
            horizontal: vars.gutter.size + smallPadding,
        },
        paddingFullMobile: {
            horizontal: smallPadding * 2,
        },
    });

    const sizing = makeThemeVars("sizes", {
        full: vars.contentSizes.full,
        narrowContentSize: vars.contentSizes.narrow,
    });

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    return {
        sizing,
        colors,
        spacing,
    };
});

export const containerMainStyles = (): NestedCSSProperties => {
    const globalVars = globalVariables();
    const vars = containerVariables();
    return {
        display: "flex",
        flexDirection: "column",
        position: "relative",
        boxSizing: "border-box",
        width: percent(100),
        maxWidth: globalVars.content.width,
        marginLeft: "auto",
        marginRight: "auto",
        ...paddings(vars.spacing.padding),

        $nest: {
            "&.isNarrow": {
                maxWidth: vars.sizing.narrowContentSize,
            },
        },
    };
};

export const containerClasses = useThemeCache(() => {
    const style = styleFactory("container");
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = containerVariables();
    const root = style(
        containerMainStyles() as NestedCSSProperties,
        mediaQueries.oneColumnDown({
            ...paddings(vars.spacing.paddingMobile),
        }),
    );

    const fullGutter = style(
        "fullGutter",
        { ...containerMainStyles(), ...paddings(vars.spacing.paddingFull) },
        mediaQueries.oneColumnDown({
            ...paddings(vars.spacing.paddingFullMobile),
        }),
    );

    return { root, fullGutter };
});
