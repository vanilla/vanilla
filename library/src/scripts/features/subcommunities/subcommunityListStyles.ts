/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const subcommunityListVariables = useThemeCache(() => {
    const themeVars = variableFactory("subcommunityList");
    const spacing = themeVars("spacing", {
        padding: 24,
    });

    const sizing = themeVars("sizing", {
        columnWidth: "50%",
        columnsWidth: 912,
    });

    return {
        spacing,
        sizing,
    };
});

export const subcommunityListClasses = useThemeCache(() => {
    const vars = subcommunityListVariables();
    const style = styleFactory("subcommunityList");
    const mediaQueries = layoutVariables().mediaQueries();
    const layoutVars = layoutVariables();

    const root = style(
        {
            maxWidth: unit(vars.sizing.columnsWidth),
            padding: unit(vars.spacing.padding),
            margin: "auto",
        },
        mediaQueries.oneColumnDown({
            padding: 0,
        }),
    );

    const items = style(
        "items",
        {
            position: "relative",
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: "space-between",
        },
        mediaQueries.oneColumnDown({
            display: "block",
        }),
    );

    const item = style(
        "item",
        {
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "stretch",
            width: unit(vars.sizing.columnWidth),
        },
        mediaQueries.oneColumnDown({
            display: "block",
            width: percent(100),
        }),
    );

    return { root, items, item };
});
