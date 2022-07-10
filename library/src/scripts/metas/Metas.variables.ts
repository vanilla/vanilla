/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { GlobalPreset, globalVariables } from "@library/styles/globalStyleVars";
import { getPixelNumber, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { color } from "csx";
import { Variables } from "@library/styles/Variables";
import { LinkDecorationType } from "@library/styles/cssUtilsTypes";
import { forceInt } from "@vanilla/utils";

/**
 * @varGroup metas
 * @description Meta items are pieces of information about a post such as author, dates, location, etc.
 */
export const metasVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("metas");

    /**
     * @varGroup metas.font
     * @title Font
     * @description Adjust the default font values for any meta item.
     * @expand font
     */
    const font = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("small"),
            color:
                globalVars.options.preset === GlobalPreset.LIGHT ? color("#767676") : globalVars.elementaryColors.white,
            lineHeight: globalVars.lineHeights.base,
        }),
    );

    /**
     * @varGroup metas.linkFont
     * @title Link font
     * @description Adjust the font values for links in meta items.
     * @expand font
     */
    const linkFont = makeThemeVars(
        "linkFont",
        Variables.font({
            ...font,
            textDecoration: "auto",
            weight:
                globalVars.links.linkDecorationType === LinkDecorationType.ALWAYS
                    ? globalVars.fonts.weights.normal
                    : globalVars.fonts.weights.semiBold,
        }),
    );

    /**
     * @varGroup metas.linkFontState
     * @title Link font (state)
     * @description Adjust the font values for links in meta items while they are being hovered/focused/etc.
     * @expand font
     */
    const linkFontState = makeThemeVars(
        "linkFontState",
        Variables.font({
            color: globalVars.mainColors.primary,
        }),
    );

    // Left undocumented for now.
    const specialFonts = makeThemeVars("specialFonts", {
        deleted: Variables.font({
            ...font,
            color: globalVars.messageColors.deleted.fg,
        }),
    });

    /**
     * @varGroup metas.spacing
     * @description Adjust the spacing in between meta items.
     * @expand spacing
     */
    const spacing = makeThemeVars(
        "spacing",
        Variables.spacing({
            horizontal: globalVars.gutter.quarter,
            vertical: 2,
        }),
    );

    const height = Math.round(getPixelNumber(font.size) * forceInt(font.lineHeight, 1));

    return {
        font,
        linkFont,
        linkFontState,
        specialFonts,
        spacing,
        height,
    };
});
