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

export const containerVariables = useThemeCache(() => {
    const vars = layoutVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("containerVariables");

    const spacing = makeThemeVars("spacing", {
        padding: {
            horizontal: vars.gutter.size,
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

export const containerClasses = useThemeCache(() => {
    const style = styleFactory("container");
    const globalVars = globalVariables();
    const vars = containerVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            display: "flex",
            flexDirection: "column",
            position: "relative",
            boxSizing: "border-box",
            width: percent(100),
            maxWidth: globalVars.content.width,
            marginLeft: "auto",
            marginRight: "auto",
            ...paddings(vars.spacing.padding),
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                horizontal: 8,
            }),
        }),
    );

    return { root };
});
