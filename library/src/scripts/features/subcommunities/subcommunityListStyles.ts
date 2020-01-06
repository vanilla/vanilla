/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { SubcommunityListAlignment } from "@library/features/subcommunities/SubcommunityList";
import { globalVariables } from "@library/styles/globalStyleVars";

export const subcommunityListVariables = useThemeCache(() => {
    const themeVars = variableFactory("subcommunityList");
    const spacing = themeVars("spacing", {
        padding: 24,
    });

    const sizing = themeVars("sizing", {
        containerWidthTwoColumns: 912,
        containerWidthThreeColumns: 912,
    });

    const options = themeVars("options", {
        columns: 2,
        alignment: SubcommunityListAlignment.CENTER,
    });

    return {
        spacing,
        sizing,
        options,
    };
});

export const subcommunityListClasses = useThemeCache(() => {
    const vars = subcommunityListVariables();
    const style = styleFactory("subcommunityList");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = (columns: number) => {
        const isTwoColumns = columns ?? vars.options.columns === 2;
        return style(
            {
                maxWidth: unit(
                    isTwoColumns ? vars.sizing.containerWidthTwoColumns : vars.sizing.containerWidthThreeColumns,
                ),
                padding: unit(vars.spacing.padding),
                margin: "auto",
                width: percent(100),
            },
            mediaQueries.oneColumnDown({
                padding: 0,
            }),
        );
    };

    const items = (alignment: SubcommunityListAlignment) => {
        const vars = subcommunityListVariables();
        const isCentered = alignment ?? vars.options.alignment === SubcommunityListAlignment.CENTER;
        return style(
            "items",
            {
                position: "relative",
                display: "flex",
                flexWrap: "wrap",
                alignItems: "stretch",
                justifyContent: isCentered ? "center" : "space-between",
            },
            mediaQueries.oneColumnDown({
                display: "block",
            }),
        );
    };

    const item = (columns: number) => {
        const isTwoColumns = columns ?? vars.options.columns === 2;
        const globalVars = globalVariables();
        return style(
            "item",
            {
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "stretch",
                width: isTwoColumns ? unit("50%") : globalVars.utility["percentage.third"],
            },

            mediaQueries.oneColumnDown({
                display: "block",
                width: percent(100),
            }),
        );
    };

    return { root, items, item };
});
