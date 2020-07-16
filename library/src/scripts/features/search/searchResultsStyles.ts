/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";
import {
    absolutePosition,
    colorOut,
    EMPTY_FONTS,
    fonts,
    margins,
    negativeUnit,
    objectFitWithFallback,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, important, percent, viewHeight } from "csx";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { BorderBottomProperty } from "csstype";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export const searchResultsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("searchResults");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
    });

    const title = makeThemeVars("title", {
        font: {
            ...EMPTY_FONTS,
            color: colors.fg,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
            lineHeight: globalVars.lineHeights.condensed,
        },
    });

    const excerpt = makeThemeVars("excerpt", {
        fg: globalVars.mainColors.fg,
        margin: "0.7em",
    });

    const image = makeThemeVars("image", {
        border: {
            color: globalVars.mixBgAndFg(0.1),
        },
    });

    const icon = makeThemeVars("icon", {
        size: 26,
        bg: colorOut(globalVars.mixBgAndFg(0.1)),
    });

    const separator = makeThemeVars("separatort", {
        fg: globalVars.separator.color,
        width: globalVars.separator.size,
    });

    const spacing = makeThemeVars("spacing", {
        padding: {
            top: 15,
            right: globalVars.widget.padding,
            bottom: 16,
            left: globalVars.widget.padding,
        },
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

export const searchResultsClasses = useThemeCache(mediaQueries => {
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
    } as NestedCSSProperties);
    const noResults = style("noResults", {
        fontSize: globalVars.userContent.font.sizes.default,
        ...paddings({
            top: globalVars.spacer.size,
            right: globalVars.gutter.half,
            bottom: globalVars.spacer.size,
            left: globalVars.gutter.half,
        }),
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

    const linkColors = clickableItemStates();

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.font),
        overflow: "hidden",
        flexGrow: 1,
        margin: 0,
        paddingRight: unit(24),
        $nest: linkColors.$nest,
    });

    // This is so 100% is the space within the padding of the root element
    const content = style("contents", {
        display: "flex",
        alignItems: "stretch",
        justifyContent: "space-between",
        width: percent(100),
        color: colorOut(vars.title.font.color),
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
        ...paddings(vars.spacing.padding),
    });

    const mediaWidth = vars.mediaElement.width + vars.mediaElement.margin;
    const iconWidth = hasIcon ? vars.icon.size + vars.spacing.padding.left : 0;

    const mainCompactStyles = {
        $nest: {
            "&.hasMedia": {
                width: percent(100),
            },
            "&.hasIcon": {
                width: calc(`100% - ${unit(iconWidth)}`),
            },
            "&.hasMedia.hasIcon": {
                width: calc(`100% - ${unit(iconWidth)}`),
            },
        },
    };

    const main = style("main", {
        display: "block",
        width: percent(100),
        $nest: {
            "&.hasMedia": {
                width: calc(`100% - ${unit(mediaWidth)}`),
            },
            "&.hasIcon": {
                width: calc(`100% - ${unit(iconWidth)}`),
            },
            "&.hasMedia.hasIcon": {
                width: calc(`100% - ${unit(mediaWidth + iconWidth)}`),
            },
            ...mediaQueries({
                [LayoutTypes.TWO_COLUMNS]: {
                    oneColumnDown: mainCompactStyles,
                },
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: mainCompactStyles,
                },
            }).$nest,
        },
    });

    const image = style("image", {
        ...objectFitWithFallback(),
    });

    const compactMediaElement = style("compactMediaElement", {
        $nest: {
            [`& .${image}`]: {
                position: important("absolute"),
            },
        },
    });

    const mediaElement = style("mediaElement", {
        position: "relative",
        width: unit(vars.mediaElement.width),
        height: unit(vars.mediaElement.height),
        overflow: "hidden",
        $nest: {
            [`&.${compactMediaElement}`]: {
                overflow: "hidden",
                position: "relative",
                marginTop: unit(globalVars.gutter.size),
                paddingTop: percent(vars.mediaElement.compact.ratio),
                width: percent(100),
            },
        },
    });

    const attachmentCompactStyles: NestedCSSProperties = {
        flexWrap: "wrap",
        width: percent(100),
        marginTop: unit(12),
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
        marginTop: unit(2),
        ...margins({
            left: -metaVars.spacing.default,
        }),
        width: calc(`100% + ${unit(metaVars.spacing.default * 2)}`),
    });

    const compactExcerpt = style("compactExcerpt", {});

    const excerpt = style("excerpt", {
        marginTop: unit(vars.excerpt.margin),
        color: colorOut(vars.excerpt.fg),
        lineHeight: globalVars.lineHeights.excerpt,
        $nest: {
            [`&.${compactExcerpt}`]: {
                ...margins({
                    top: globalVars.gutter.size,
                    left: iconWidth,
                }),
            },
        },
    });

    const link = style("link", {
        color: colorOut(globalVars.mainColors.fg),
        $nest: linkColors.$nest,
    });

    const afterExcerptLink = style("afterExcerptLink", {
        ...fonts(globalVars.meta.text),
        $nest: linkColors.$nest,
    });

    const iconWrap = style("iconWrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: colorOut(vars.icon.bg),
        borderRadius: "50%",
        width: unit(vars.icon.size),
        height: unit(vars.icon.size),
        cursor: "pointer",
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
        content,
    };
});
