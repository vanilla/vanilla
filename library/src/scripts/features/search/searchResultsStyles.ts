/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";
import { negativeUnit, objectFitWithFallback, singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent } from "csx";
import { BorderBottomProperty } from "csstype";
import { CSSObject } from "@emotion/css";
import { TLength } from "@library/styles/styleShim";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

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
            color: colors.fg,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
            lineHeight: globalVars.lineHeights.condensed,
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

export const searchResultsClasses = useThemeCache((mediaQueries) => {
    const vars = searchResultsVariables();
    const globalVars = globalVariables();
    const style = styleFactory("searchResults");

    const root = style({
        display: "block",
        position: "relative",
        borderTop: singleBorder({
            color: vars.separator.fg,
            width: vars.separator.width,
        }),
        marginTop: negativeUnit(globalVars.gutter.half),
        ...mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    borderTop: 0,
                },
            },
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    borderTop: 0,
                },
            },
        }),
    });

    const noResults = style("noResults", {
        fontSize: globalVars.userContent.font.sizes.default,
    });

    const item = style("item", {
        position: "relative",
        display: "block",
        userSelect: "none",
        borderBottom: singleBorder({
            color: vars.separator.fg,
            width: vars.separator.width,
        }) as BorderBottomProperty<TLength>,
    });

    const result = style("result", {
        position: "relative",
        display: "flex",
        alignItems: "flex-start",
        width: percent(100),
    });

    return {
        root,
        noResults,
        item,
        result,
    };
});

export const searchResultClasses = useThemeCache((mediaQueries, hasIcon = false) => {
    const vars = searchResultsVariables();
    const globalVars = globalVariables();
    const style = styleFactory("searchResult");
    const metaVars = metasVariables();

    const linkColors = Mixins.clickable.itemState({ skipDefault: true });

    const title = style("title", {
        display: "block",
        ...Mixins.font(vars.title.font),
        overflow: "hidden",
        flexGrow: 1,
        margin: 0,
        paddingRight: styleUnit(24),
        ...linkColors,
    });

    // This is so 100% is the space within the padding of the root element
    const content = style("contents", {
        display: "flex",
        alignItems: "stretch",
        justifyContent: "space-between",
        width: percent(100),
        color: ColorsUtils.colorOut(vars.title.font.color),
        ...mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    flexWrap: "wrap",
                },
            },
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    flexWrap: "wrap",
                },
            },
        }),
    });

    const root = style({
        display: "block",
        width: percent(100),
        ...Mixins.padding(vars.spacing.padding),
    });

    const mediaWidth = vars.mediaElement.width + vars.mediaElement.margin;
    const iconWidth = hasIcon ? vars.icon.size + (vars.spacing.padding.left as number) : 0;

    const mainCompactStyles = {
        ...{
            "&.hasMedia": {
                width: percent(100),
            },
            "&.hasIcon": {
                width: calc(`100% - ${styleUnit(iconWidth)}`),
            },
            "&.hasMedia.hasIcon": {
                width: calc(`100% - ${styleUnit(iconWidth)}`),
            },
        },
    };

    const main = style("main", {
        display: "block",
        width: percent(100),
        ...{
            "&.hasMedia": {
                width: calc(`100% - ${styleUnit(mediaWidth)}`),
            },
            "&.hasIcon": {
                width: calc(`100% - ${styleUnit(iconWidth)}`),
            },
            "&.hasMedia.hasIcon": {
                width: calc(`100% - ${styleUnit(mediaWidth + iconWidth)}`),
            },
            ...mediaQueries({
                [LayoutTypes.TWO_COLUMNS]: {
                    oneColumnDown: mainCompactStyles,
                },
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: mainCompactStyles,
                },
            }),
        },
    });

    const image = style("image", {
        ...objectFitWithFallback(),
    });

    const compactMediaElement = style("compactMediaElement", {
        ...{
            [`.${image}`]: {
                position: important("absolute"),
            },
        },
    });

    const mediaElement = style("mediaElement", {
        position: "relative",
        width: styleUnit(vars.mediaElement.width),
        height: styleUnit(vars.mediaElement.height),
        overflow: "hidden",
        ...{
            [`&.${compactMediaElement}`]: {
                overflow: "hidden",
                position: "relative",
                marginTop: styleUnit(globalVars.gutter.size),
                paddingTop: percent(vars.mediaElement.compact.ratio),
                width: percent(100),
            },
        },
    });

    const attachmentCompactStyles: CSSObject = {
        flexWrap: "wrap",
        width: percent(100),
        marginTop: styleUnit(12),
    };

    const attachments = style("attachments", {
        display: "flex",
        flexWrap: "nowrap",
        ...mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: attachmentCompactStyles,
            },
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: attachmentCompactStyles,
            },
        }),
    });

    const metas = style("metas", {
        marginTop: styleUnit(2),
        ...Mixins.margin({
            left: -metaVars.spacing.default,
        }),
        width: calc(`100% + ${styleUnit(metaVars.spacing.default * 2)}`),
    });

    const compactExcerpt = style("compactExcerpt", {});

    const excerpt = style("excerpt", {
        marginTop: styleUnit(vars.excerpt.margin),
        color: ColorsUtils.colorOut(vars.excerpt.fg),
        lineHeight: globalVars.lineHeights.excerpt,
        ...{
            [`&.${compactExcerpt}`]: {
                ...Mixins.margin({
                    top: globalVars.gutter.size,
                    left: iconWidth,
                }),
            },
        },
    });

    const link = style("link", {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ...linkColors,
    });

    const afterExcerptLink = style("afterExcerptLink", {
        ...Mixins.font(globalVars.meta.text),
        ...linkColors,
    });

    const iconWrap = style("iconWrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: ColorsUtils.colorOut(vars.icon.bg),
        borderRadius: "50%",
        width: styleUnit(vars.icon.size),
        height: styleUnit(vars.icon.size),
        cursor: "pointer",
    });

    const commentWrap = style("commentWrap", {
        display: "flex",
        marginTop: styleUnit(globalVars.gutter.size),
    });

    return {
        root,
        main,
        mediaElement,
        compactMediaElement,
        image,
        title,
        attachments,
        metas,
        excerpt,
        compactExcerpt,
        afterExcerptLink,
        attachmentCompactStyles,
        link,
        iconWrap,
        commentWrap,
        content,
    };
});
