/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { css } from "@emotion/css";

/**
 * @varGroup searchResults
 * @commonTitle Search Results
 * @description Controls search results
 */
export const searchResultsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("searchResults");

    const colors = makeThemeVars("colors", {
        /**
         * @var searchResults.colors.fg
         * @title Foreground Color
         * @description Defaults to the globally set main foreground color.
         * @type string
         * @format hex-color
         */
        fg: globalVars.mainColors.fg,
    });

    const title = makeThemeVars("title", {
        /**
         * @varGroup searchResults.title.font
         * @commonTitle Search Results Font
         * @expand font
         */
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
            color: colors.fg,
            lineHeight: globalVars.lineHeights.condensed,
            textDecoration: "none",
        }),
    });

    const excerpt = makeThemeVars("excerpt", {
        /**
         * @var searchResults.excerpt.fg
         * @title Excerpt Color
         * @description Excerpt foreground color. Defaults to the globally set foreground color.
         * @type string
         * @format hex-color
         */
        fg: globalVars.mainColors.fg,
        /**
         * @var searchResults.excerpt.margin
         * @title Excerpt Margin
         * @description Sets the margin of the excerpt
         * @type string
         */
        margin: "0.7em",
    });

    const image = makeThemeVars("image", {
        border: {
            /**
             * @var searchResults.image.border.color
             * @title Excerpt Image Border Color
             * @description Sets the border color of the excerpt image
             * @type string
             * @format hex-color
             */
            color: globalVars.mixBgAndFg(0.1),
        },
    });

    /**
     * @varGroup searchResults.icon
     * @commonTitle  Icon
     */
    const icon = makeThemeVars("icon", {
        /**
         * @var searchResults.icon.size
         * @title Size
         * @description Sets the size of the icon
         * @type number
         */
        size: 26,

        /**
         * @var searchResults.icon.bg
         * @title Background Color
         * @description Sets the background color of the icon
         * @type string
         * @format hex-color
         */
        bg: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
    });

    /**
     * @varGroup searchResults.separator
     * @commonTitle  Separator
     * @commonDescription Refers to the border of the result
     */
    const separator = makeThemeVars("separator", {
        /**
         * @var searchResults.separator.fg
         * @title Foreground color
         * @description Sets the foreground color of the separator
         * @type string
         * @format hex-color
         */
        fg: globalVars.separator.color,
        /**
         * @var searchResults.separator.width
         * @title Width
         * @description Sets the width of the separator
         * @type number
         */
        width: globalVars.separator.size,
    });

    const spacing = makeThemeVars("spacing", {
        /**
         * @varGroup searchResults.spacing.padding
         * @commonTitle Spacing: Padding
         * @expand spacing
         */
        padding: globalVars.itemList.padding,
    });

    const mediaElement = makeThemeVars("mediaElement", {
        width: 190,
        height: 106.875,
        margin: 15,
        compact: {
            ratio: (9 / 16) * 100,
        },
    });

    return {
        colors,
        title,
        excerpt,
        image,
        separator,
        spacing,
        icon,
        mediaElement,
    };
});

export const searchResultClasses = useThemeCache(() => {
    const vars = searchResultsVariables();

    const iconWrap = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: ColorsUtils.colorOut(vars.icon.bg),
        borderRadius: "50%",
        width: styleUnit(vars.icon.size),
        height: styleUnit(vars.icon.size),
        flexShrink: 0,
        cursor: "pointer",
    });

    const highlight = css({
        "b, strong, em": {
            fontStyle: "normal",
            fontWeight: 700,
        },
    });

    return {
        iconWrap,
        highlight,
    };
});
