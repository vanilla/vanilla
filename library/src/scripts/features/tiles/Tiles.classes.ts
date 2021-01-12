/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { extendItemContainer } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSPercentage } from "csx/lib/types";
import { CSSObject } from "@emotion/css";
import { TileAlignment } from "@library/features/tiles/TileAlignment";
import { Mixins } from "@library/styles/Mixins";
import { ITilesOptions, tilesVariables } from "./Tiles.variables";

export const tilesClasses = useThemeCache((optionOverrides?: ITilesOptions) => {
    const globalVars = globalVariables();
    const vars = tilesVariables(optionOverrides);
    const style = styleFactory("tiles");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            maxWidth: styleUnit(vars.calculatedMaxWidth),
            margin: "auto",
            width: percent(100),
        },
        Mixins.padding(vars.containerSpacing.padding),
        mediaQueries.oneColumnDown({
            padding: 0,
        }),
    );

    const isCentered = vars.options.alignment === TileAlignment.CENTER;

    let columnCount = vars.options.columns;
    let width: CSSPercentage = "50%";
    let additionalMediaQueries = [] as CSSObject[];
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
            padding: styleUnit(itemPadding),
        },
        ...additionalMediaQueries,
        mediaQueries.oneColumnDown({
            display: "block",
            width: percent(100),
            padding: styleUnit(vars.itemSpacing.paddingOneColumn),
        }),
    );

    const title = style("title", {
        marginTop: globalVars.gutter.size,
        marginBottom: 0,
        // fontSize: globalVars.fonts.size.title,
        fontWeight: globalVars.fonts.weights.bold,
        lineHeight: globalVars.lineHeights.condensed,
    });

    return { root, items, item, title };
});
