/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const panelWidgetVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("panelWidget");

    const spacing = makeThemeVars("spacing", {
        padding: 8,
    });

    return { spacing };
});

export const panelWidgetClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("panelWidget");
    const vars = panelWidgetVariables();

    const root = style(
        {
            display: "flex",
            flexDirection: "column",
            position: "relative",
            width: percent(100),
            ...paddings({
                all: globalVars.gutter.half,
            }),
            $nest: {
                "&.hasNoVerticalPadding": {
                    ...paddings({ vertical: 0 }),
                },
                "&.hasNoHorizontalPadding": {
                    ...paddings({ horizontal: 0 }),
                },
                "&.isSelfPadded": {
                    ...paddings({ all: 0 }),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                all: vars.spacing.padding,
            }),
        }),
    );

    return { root };
});
