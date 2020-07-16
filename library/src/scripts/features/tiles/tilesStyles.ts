/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, EMPTY_SPACING, paddings, extendItemContainer } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSPercentage } from "csx/lib/types";
import { NestedCSSProperties } from "typestyle/lib/types";
import { TileAlignment } from "@library/features/tiles/Tiles";

export interface ITilesOptions {
    columns?: number;
    alignment?: TileAlignment;
}

export const tilesVariables = useThemeCache((optionOverrides?: ITilesOptions) => {
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

    const options = themeVars(
        "options",
        {
            columns: 2,
            alignment: TileAlignment.CENTER,
        },
        optionOverrides,
    );

    const { columns } = options;

    const sizing = themeVars("sizing", {
        containerWidthTwoColumns: itemSpacing.paddingTwoColumns * 4 + 384 * 2,
        containerWidthThreeColumns: itemSpacing.paddingThreeColumns * 6 + 260 * 3,
        containerWidthFourColumns: itemSpacing.paddingFourColumns * 8 + 260 * 4,
    });

    let calculatedMaxWidth = sizing.containerWidthTwoColumns;
    switch (columns) {
        case 3:
            calculatedMaxWidth = sizing.containerWidthThreeColumns;
            break;
        case 4:
            calculatedMaxWidth = sizing.containerWidthFourColumns;
            break;
    }

    return {
        itemSpacing,
        containerSpacing,
        sizing,
        options,
        calculatedMaxWidth,
    };
});

export const tilesClasses = useThemeCache((optionOverrides?: ITilesOptions) => {
    const globalVars = globalVariables();
    const vars = tilesVariables(optionOverrides);
    const style = styleFactory("tiles");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            maxWidth: unit(vars.calculatedMaxWidth),
            margin: "auto",
            width: percent(100),
        },
        paddings(vars.containerSpacing.padding),
        mediaQueries.oneColumnDown({
            padding: 0,
        }),
    );

    const isCentered = vars.options.alignment === TileAlignment.CENTER;

    let columnCount = vars.options.columns;
    let width: CSSPercentage = "50%";
    let additionalMediaQueries = [] as NestedCSSProperties[];
    let itemPadding = vars.itemSpacing.paddingTwoColumns;
    switch (columnCount) {
        case 3:
            width = percent((1 / 3) * 100);
            if ("twoColumns" in mediaQueries) {
                additionalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
            }
            itemPadding = vars.itemSpacing.paddingThreeColumns;
            break;
        case 4:
            width = "25%";
            if ("twoColumns" in mediaQueries) {
                additionalMediaQueries.push(
                    mediaQueries.twoColumns({
                        width: percent(50),
                    }),
                );
            }
            itemPadding = vars.itemSpacing.paddingFourColumns;
            break;
    }

    const items = style(
        "items",
        {
            position: "relative",
            display: "flex",
            flexWrap: "wrap",
            alignItems: "stretch",
            justifyContent: isCentered ? "center" : "flex-start",
            ...extendItemContainer(itemPadding),
        },
        mediaQueries.oneColumnDown({
            display: "block",
            ...extendItemContainer(vars.itemSpacing.paddingOneColumn),
        }),
    );

    const item = style(
        "item",
        {
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            justifyContent: "stretch",
            width,
            padding: unit(itemPadding),
        },
        ...additionalMediaQueries,
        mediaQueries.oneColumnDown({
            display: "block",
            width: percent(100),
            padding: unit(vars.itemSpacing.paddingOneColumn),
        }),
    );

    const title = style("title", {
        marginTop: globalVars.gutter.size,
        marginBottom: 0,
        fontSize: globalVars.fonts.size.title,
        fontWeight: globalVars.fonts.weights.bold,
        lineHeight: globalVars.lineHeights.condensed,
    });

    return { root, items, item, title };
});
