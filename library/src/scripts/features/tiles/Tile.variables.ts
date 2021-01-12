/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Variables } from "@library/styles/Variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { TLength } from "@library/styles/styleShim";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorHelper } from "csx";
import { FontSizeProperty, MarginProperty, PaddingProperty } from "csstype";
import { TileAlignment } from "@library/features/tiles/TileAlignment";
import { tilesVariables } from "@library/features/tiles/Tiles.variables";

export const tileVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("tile");
    const tileVariables = tilesVariables();

    const options = themeVars("options", {
        alignment: TileAlignment.CENTER,
    });

    const spacing = themeVars("spacing", {
        twoColumns: 24,
        threeColumns: 9,
        fourColumns: 17,
        color: globalVars.mainColors.primary as ColorHelper,
    });

    let frameHeight = 90;
    let frameWidth = 90;

    if (tileVariables.options.columns >= 3) {
        frameHeight = 72;
        frameWidth = 72;
    }

    const frame = themeVars("frame", {
        height: frameHeight as PaddingProperty<TLength>,
        width: frameWidth as PaddingProperty<TLength>,
        marginBottom: 16 as MarginProperty<TLength>,
    });

    const title = themeVars("title", {
        font: Variables.font({
            size: globalVars.fonts.size.large as FontSizeProperty<TLength>,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        marginBottom: 6,
    });

    const description = themeVars("description", {
        fontSize: globalVars.fonts.size.medium as FontSizeProperty<TLength>,
        marginTop: 6,
        lineHeight: globalVars.lineHeights.excerpt,
    });

    const link = themeVars("link", {
        padding: {
            top: 36,
            bottom: 24,
            left: 24,
            right: 24,
        },
        borderRadius: globalVars.border.radius,
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        bgHover: globalVars.mainColors.bg,
        bgImage: undefined as string | undefined,
        bgImageHover: undefined as string | undefined,
        twoColumnsMinHeight: 0,
        threeColumnsMinHeight: 0,
        fourColumnsMinHeight: 0,
    });

    const fallBackIcon = themeVars("fallBackIcon", {
        width: frame.height,
        height: frame.width,
        fg: globalVars.mainColors.primary,
    });

    return {
        options,
        spacing,
        frame,
        title,
        description,
        link,
        fallBackIcon,
    };
});
