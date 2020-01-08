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
import { CSSPercentage } from "csx/lib/types";
import { NestedCSSProperties } from "typestyle/lib/types";

export const subcommunityListVariables = useThemeCache(() => {
    const themeVars = variableFactory("subcommunityList");
    const spacing = themeVars("spacing", {
        paddingTwoColumns: 25,
        paddingThreeColumns: 17,
        paddingFourColumns: 17,
    });

    const sizing = themeVars("sizing", {
        containerWidthTwoColumns: spacing.paddingTwoColumns * 10 + 384 * 2,
        containerWidthThreeColumns: spacing.paddingThreeColumns * 14 + 260 * 3,
        containerWidthFourColumns: spacing.paddingThreeColumns * 18 + 260 * 4,
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

    const root = (columns?: number) => {
        let columnCount = columns ?? vars.options.columns;
        let maxWidth = vars.sizing.containerWidthTwoColumns;
        let itemPadding = vars.spacing.paddingTwoColumns;
        switch (columnCount) {
            case 3:
                maxWidth = vars.sizing.containerWidthThreeColumns;
                itemPadding = vars.spacing.paddingThreeColumns;
                break;
            case 4:
                maxWidth = vars.sizing.containerWidthFourColumns;
                itemPadding = vars.spacing.paddingFourColumns;
                break;
        }

        return style(
            {
                maxWidth: unit(maxWidth),
                padding: unit(itemPadding),
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
        const isCentered = (alignment ?? vars.options.alignment) === SubcommunityListAlignment.CENTER;
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

    const item = (columns?: number) => {
        const globalVars = globalVariables();
        let columnCount = columns ?? vars.options.columns;
        let width: CSSPercentage = "50%";
        let additionnalMediaQueries = [] as NestedCSSProperties[];
        let padding = vars.spacing.paddingTwoColumns;
        switch (columnCount) {
            case 3:
                width = globalVars.utility["percentage.third"];
                additionnalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
                padding = vars.spacing.paddingThreeColumns;
                break;
            case 4:
                width = "25%";
                additionnalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
                padding = vars.spacing.paddingFourColumns;
                break;
        }

        return style(
            "item",
            {
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "stretch",
                width,
                padding: unit(padding),
            },
            ...additionnalMediaQueries,
            mediaQueries.oneColumnDown({
                display: "block",
                width: percent(100),
                padding: 0,
            }),
        );
    };

    return { root, items, item };
});
