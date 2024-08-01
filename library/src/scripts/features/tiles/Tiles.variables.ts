/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { TileAlignment } from "@library/features/tiles/TileAlignment";
import { Variables } from "@library/styles/Variables";

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
        padding: Variables.spacing({
            vertical: 24,
        }),
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
