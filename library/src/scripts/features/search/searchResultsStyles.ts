/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";
import {
    colorOut, EMPTY_FONTS,
    fonts,
    margins, negativeUnit,
    objectFitWithFallback,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent, px } from "csx";
import { media } from "typestyle";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import {BorderBottomProperty} from "csstype";
import {NestedCSSProperties, TLength} from "typestyle/lib/types";
import {buttonVariables} from "@library/forms/buttonStyles";

export const searchResultsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("searchResults");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.primary,
    });

    const title = makeThemeVars("title", {
        fonts: {
            color: undefined,
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
        bg: colorOut(globalVars.mixBgAndFg(.1)),

    });

    const separator = makeThemeVars("separatort", {
        fg: globalVars.separator.color,
        width: globalVars.separator.size,
    });

    const spacing = makeThemeVars("spacing", {
        padding: {
            top: 15,
            right: globalVars.gutter.half,
            bottom: 16,
            left: globalVars.gutter.half,
        },
    });

    const mediaElement = makeThemeVars("mediaElement", {
        width: 115,
    });

    const breakPoints = makeThemeVars("breakPoints", {
        compact: 800,
    });

    const mediaQueries = () => {
        const compact = styles => {
            return media({ maxWidth: px(breakPoints.compact) }, styles);
        };

        return { compact };
    };


    return {
        colors,
        title,
        excerpt,
        image,
        separator,
        spacing,
        icon,
        mediaElement,
        breakPoints,
        mediaQueries,
    };
});

export const searchResultsClasses = useThemeCache(() => {
    const vars = searchResultsVariables();
    const globalVars = globalVariables();
    const style = styleFactory("searchResults");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            display: "block",
            position: "relative",
            borderTop: singleBorder({
                color: vars.separator.fg,
                width: vars.separator.width,
            }),
            marginTop: negativeUnit(globalVars.gutter.half),
        } as NestedCSSProperties,
        mediaQueries.oneColumnDown({
            borderTop: 0,
        }),
    );
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

export const searchResultClasses = useThemeCache(() => {
    const vars = searchResultsVariables();
    const globalVars = globalVariables();
    const style = styleFactory("searchResult");
    const mediaQueries = vars.mediaQueries();
    const metaVars = metasVariables();

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.fonts),
        overflow: "hidden",
        flexGrow: 1,
        margin: 0,
        paddingRight: unit(24),
    });

    const root = style(
        {
            display: "flex",
            alignItems: "stretch",
            justifyContent: "space-between",
            width: percent(100),
            ...paddings(vars.spacing.padding),
            cursor: "pointer",
            color: colorOut(vars.title.fonts.color),
            borderBottom: singleBorder({
                color: vars.separator.fg,
                width: vars.separator.width,
            }) as BorderBottomProperty<TLength>,
            $nest: {
                "&.hasIcon": {
                    width: calc(`100% - ${unit(vars.icon.size + vars.spacing.padding.left)}`),
                }
            }
        },
        mediaQueries.compact({
            flexWrap: "wrap",
        }),
    );

    const main = style(
        "main",
        {
            display: "block",
            width: percent(100),
            $nest: {
                "&.hasMedia": {
                    width: calc(`100% - ${unit(vars.mediaElement.width + vars.spacing.padding.left)}`),
                },
            },
        },
        mediaQueries.compact({
            $nest: {
                "&.hasMedia": {
                    width: percent(100),
                },
            },
        }),
    );

    const mediaElement = style(
        "mediaElement",
        {
            position: "relative",
            width: unit(vars.mediaElement.width),
            overflow: "hidden",
        },
        mediaQueries.compact({
            width: percent(100),
            $nest: {
                "&.hasImage": {
                    height: unit(vars.mediaElement.width),
                },
            },
        }),
    );

    const image = style("image", {
        ...objectFitWithFallback(),
    });

    const attachments = style(
        "attachments",
        {
            display: "flex",
            flexWrap: "nowrap",
        },
        mediaQueries.compact({
            flexWrap: "wrap",
            width: percent(100),
            marginTop: unit(12),
        }),
    );

    const metas = style("metas", {
        marginTop: unit(2),
        ...margins({
            left: -metaVars.spacing.default,
        }),
        width: calc(`100% + ${unit(metaVars.spacing.default * 2)}`),
    });

    const excerpt = style("excerpt", {
        marginTop: unit(vars.excerpt.margin),
        color: colorOut(vars.excerpt.fg),
        lineHeight: globalVars.lineHeights.excerpt,
    });

    const linkColors = clickableItemStates();
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
        marginTop: unit(vars.spacing.padding.top),
        marginLeft: unit(vars.spacing.padding.left),
    });

    return {
        root,
        main,
        mediaElement,
        image,
        title,
        attachments,
        metas,
        excerpt,
        afterExcerptLink,
        link,
        iconWrap,
    };
});
