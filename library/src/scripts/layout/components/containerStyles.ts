/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent } from "csx";
import { paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { NestedCSSProperties } from "typestyle/lib/types";

export const containerVariables = useThemeCache(() => {
    const vars = layoutVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("containerVariables");

    let spacing = makeThemeVars("spacing", {
        padding: globalVars.constants.fullGutter / 2,
        mobile: {
            padding: globalVars.widget.padding,
        },
    });

    const sizing = makeThemeVars("sizes", {
        full: vars.contentWidth,
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
    const vars = containerVariables();
    return {
        display: "flex",
        flexDirection: "column",
        position: "relative",
        boxSizing: "border-box",
        width: percent(100),
        maxWidth: unit(vars.sizing.full + vars.spacing.padding * 2),
        marginLeft: "auto",
        marginRight: "auto",
        ...paddings({
            horizontal: vars.spacing.padding,
        }),
        $nest: {
            "&.isNarrow": {
                maxWidth: vars.sizing.narrowContentSize,
            },
        },
    };
};

export function containerMainMediaQueries() {
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = containerVariables();
    return mediaQueries.oneColumnDown({
        ...paddings({
            horizontal: vars.spacing.mobile.padding,
        }),
    });
}

export const containerClasses = useThemeCache(() => {
    const style = styleFactory("container");
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = containerVariables();
    const root = style(containerMainStyles(), containerMainMediaQueries());

    const fullGutter = style(
        "fullGutter",
        {
            ...containerMainStyles(),
            ...paddings({
                horizontal: vars.spacing.padding * 2,
            }),
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                horizontal: vars.spacing.mobile.padding * 2,
            }),
        }),
    );

    return { root, fullGutter };
});
