/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/layoutStyles";
import { percent } from "csx";
import { paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import isEmpty from "lodash/isEmpty";
import { LayoutTypes } from "@library/layout/types/LayoutUtils";
import { NestedCSSProperties } from "typestyle/lib/types";

export const containerVariables = useThemeCache(() => {
    const vars = layoutVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("containerVariables");

    const smallPadding = panelWidgetVariables().spacing.padding;

    let spacingInit = makeThemeVars("spacing", {
        padding: {
            horizontal: vars.gutter.size,
        },
        mobile: {
            padding: {
                horizontal: smallPadding,
            },
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

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    return {
        colors,
        spacing,
    };
});

export const containerMainStyles = (contentWidth: number | string) => {
    return {
        display: "flex",
        flexDirection: "column",
        position: "relative",
        boxSizing: "border-box",
        width: percent(100),
        maxWidth: unit(contentWidth),
        marginLeft: "auto",
        marginRight: "auto",
        ...paddings(layoutVariables().spacing.padding),
    } as NestedCSSProperties;
};

export function containerMainMediaQueries(mediaQueries) {
    const vars = containerVariables();
    return mediaQueries.oneColumnDown({
        ...paddings(vars.spacing.mobile.padding),
    });
}

export const containerClasses = useThemeCache((currentLayoutVariables, mediaQueries) => {
    const style = styleFactory("container");
    const isLoading = isEmpty(currentLayoutVariables);
    const variables = containerVariables();
    const root = style({
        display: "flex",
        flexDirection: "column",
        position: "relative",
        boxSizing: "border-box",
        width: percent(100),
        maxWidth: isLoading ? undefined : currentLayoutVariables.contentWidth(),
        marginLeft: "auto",
        marginRight: "auto",
        ...paddings(variables.spacing.padding),
        ...mediaQueries({
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: paddings(variables.spacing.mobile.padding),
            },
        }),
    });

    return { root };
});
