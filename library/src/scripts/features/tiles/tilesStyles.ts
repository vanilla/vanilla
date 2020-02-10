/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, EMPTY_SPACING, paddings } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSPercentage } from "csx/lib/types";
import { NestedCSSProperties } from "typestyle/lib/types";
import { TileAlignment } from "@library/features/tiles/Tiles";

export const tilesVariables = useThemeCache(() => {
    const themeVars = variableFactory("tiles");

    const itemSpacing = themeVars("itemSpacing", {
        paddingOneColumn: 14,
        paddingTwoColumns: 24,
        paddingThreeColumns: 14,
        paddingFourColumns: 14,
    });

    const containerSpacing = themeVars("containerSpacing", {
        padding: {
            ...EMPTY_SPACING,
            vertical: 24,
        },
    });

    const sizing = themeVars("sizing", {
        containerWidthTwoColumns: itemSpacing.paddingTwoColumns * 4 + 384 * 2,
        containerWidthThreeColumns: itemSpacing.paddingThreeColumns * 6 + 260 * 3,
        containerWidthFourColumns: itemSpacing.paddingFourColumns * 8 + 260 * 4,
    });

    const options = themeVars("options", {
        columns: 2,
        alignment: TileAlignment.CENTER,
    });

    return {
        itemSpacing,
        containerSpacing,
        sizing,
        options,
    };
});

export const tilesClasses = useThemeCache(() => {
    const vars = tilesVariables();
    const style = styleFactory("tiles");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = useThemeCache((columns?: number) => {
        let columnCount = columns ?? vars.options.columns;
        let maxWidth = vars.sizing.containerWidthTwoColumns;
        switch (columnCount) {
            case 3:
                maxWidth = vars.sizing.containerWidthThreeColumns;
                break;
            case 4:
                maxWidth = vars.sizing.containerWidthFourColumns;
                break;
        }

        return style(
            {
                maxWidth: unit(maxWidth),
                margin: "auto",
                width: percent(100),
            },
            paddings(vars.containerSpacing.padding),
            mediaQueries.oneColumnDown({
                padding: 0,
            }),
        );
    });

    const items = useThemeCache((alignment: TileAlignment) => {
        const vars = tilesVariables();
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
        let padding = vars.itemSpacing.paddingTwoColumns;
        switch (columnCount) {
            case 3:
                width = globalVars.utility["percentage.third"];
                additionnalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
                padding = vars.itemSpacing.paddingThreeColumns;
                break;
            case 4:
                width = "25%";
                additionnalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
                padding = vars.itemSpacing.paddingFourColumns;
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
                padding: unit(vars.itemSpacing.paddingOneColumn),
            }),
        );
    });

    return { root, items, item };
});
