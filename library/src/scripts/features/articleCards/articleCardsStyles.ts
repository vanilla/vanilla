/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSPercentage } from "csx/lib/types";
import { NestedCSSProperties } from "typestyle/lib/types";
import { TileAlignment } from "@library/features/tiles/Tiles";

export const articleCardsVariables = useThemeCache(() => {
    const themeVars = variableFactory("article-cards");
    const spacing = themeVars("spacing", {
        paddingOneColumn: 14,
        paddingTwoColumns: 24,
        paddingThreeColumns: 14,
        paddingFourColumns: 14,
    });

    const sizing = themeVars("sizing", {
        containerWidthTwoColumns: spacing.paddingTwoColumns * 4 + 384 * 2,
        containerWidthThreeColumns: spacing.paddingThreeColumns * 6 + 260 * 3,
        containerWidthFourColumns: spacing.paddingFourColumns * 8 + 260 * 4,
    });

    const options = themeVars("options", {
        columns: 2,
        alignment: TileAlignment.CENTER,
    });

    return {
        spacing,
        sizing,
        options,
    };
});

export const articleCardsClasses = useThemeCache(() => {
    const vars = articleCardsVariables();
    const style = styleFactory("article-card");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = useThemeCache((columns?: number) => {
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
    });

    const items = useThemeCache((alignment: TileAlignment) => {
        const vars = articleCardsVariables();
        const isCentered = (alignment ?? vars.options.alignment) === TileAlignment.CENTER;
        return style(
            "items",
            {
                position: "relative",
                display: "flex",
                flexWrap: "wrap",
                alignItems: "stretch",
                justifyContent: isCentered ? "center" : "flex-start",
            },
            mediaQueries.oneColumnDown({
                display: "block",
            }),
        );
    });

    const item = useThemeCache((columns?: number) => {
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
                padding: unit(vars.spacing.paddingOneColumn),
            }),
        );
    });

    return { root, items, item };
});
