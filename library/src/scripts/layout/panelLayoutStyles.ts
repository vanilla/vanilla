/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, color, percent, px, translateY, viewHeight } from "csx";
import { cssRule, media } from "typestyle";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, paddings, sticky, unit } from "@library/styles/styleHelpers";
import { important } from "csx/lib/strings";
import { panelListClasses } from "@library/layout/panelListStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";

export const layoutVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("globalVariables");

    const colors = makeThemeVars("colors", {
        leftColumnBg: globalVars.mainColors.bg,
    });

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: 48,
        panelWidth: 216,
        middleColumnWidth: 700,
        minimalMiddleColumnWidth: 550, // Will break if middle column width is smaller than this value.
        narrowContentWidth: 900, // For home page widgets, narrower than full width
        breakPoints: {
            // Other break points are calculated
            twoColumns: 1200,
            xs: 500,
        },
    });

    const gutter = makeThemeVars("gutter", {
        full: foundationalWidths.fullGutter, // 48
        size: foundationalWidths.fullGutter / 2, // 24
        halfSize: foundationalWidths.fullGutter / 4, // 12
        quarterSize: foundationalWidths.fullGutter / 8, // 6
    });

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
        paddedWidth: foundationalWidths.panelWidth + gutter.full,
    });

    const middleColumn = makeThemeVars("middleColumn", {
        width: foundationalWidths.middleColumnWidth,
        paddedWidth: foundationalWidths.middleColumnWidth + gutter.full,
    });

    const globalContentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2 + gutter.size;

    const contentSizes = makeThemeVars("content", {
        full: globalContentWidth,
        narrow:
            foundationalWidths.narrowContentWidth < globalContentWidth
                ? foundationalWidths.narrowContentWidth
                : globalContentWidth,
    });

    const panelLayoutBreakPoints = makeThemeVars("panelLayoutBreakPoints", {
        noBleed: globalContentWidth,
        twoColumn: foundationalWidths.breakPoints.twoColumns,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
        xs: foundationalWidths.breakPoints.xs,
    });

    const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", {
        margin: {
            top: 0,
            bottom: 50,
        },
        padding: {
            top: gutter.halfSize * 1.5,
        },
        extraPadding: {
            top: 32,
            noBreadcrumbs: {},
            mobile: {
                noBreadcrumbs: {
                    top: 16,
                },
            },
        },
        largePadding: {
            top: 64,
        },
        offset: {
            left: -44,
            right: -36,
        },
        withPanelBackground: {
            gutter: 70,
        },
    });

    const mediaQueries = () => {
        const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.noBleed),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.twoColumn + 1) : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.noBleed),
                },
                styles,
            );
        };

        const twoColumnsDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.twoColumn),
                },
                styles,
            );
        };

        const twoColumns = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.twoColumn),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.oneColumn + 1) : undefined,
                },
                styles,
            );
        };

        const oneColumn = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.oneColumn),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.xs + 1) : undefined,
                },
                styles,
            );
        };

        const oneColumnDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.oneColumn),
                },
                styles,
            );
        };

        const xs = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.xs),
                },
                styles,
            );
        };

        return {
            noBleed,
            noBleedDown,
            twoColumns,
            twoColumnsDown,
            oneColumn,
            oneColumnDown,
            xs,
        };
    };

    return {
        colors,
        foundationalWidths,
        gutter,
        panel,
        middleColumn,
        contentSizes,
        mediaQueries,
        panelLayoutSpacing,
        panelLayoutBreakPoints,
    };
});

export const panelLayoutClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = layoutVariables();
    const mediaQueries = vars.mediaQueries();
    const style = styleFactory("panelLayout");
    const classesPanelArea = panelAreaClasses();
    const classesPanelList = panelListClasses();

    const main = style("main", {
        minHeight: viewHeight(20),
        width: percent(100),
    });

    const root = style(
        {
            ...margins(vars.panelLayoutSpacing.margin),
            width: percent(100),
            $nest: {
                [`&.noBreadcrumbs > .${main}`]: {
                    paddingTop: unit(globalVars.gutter.size),
                    ...mediaQueries.oneColumnDown({
                        paddingTop: 0,
                    }),
                },
                "&.isOneCol": {
                    width: unit(vars.middleColumn.paddedWidth),
                    maxWidth: percent(100),
                    margin: "auto",
                    ...mediaQueries.oneColumnDown({
                        width: percent(100),
                    }),
                },
                "&.hasTopPadding": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.top),
                },
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                },
                "&.hasLargePadding": {
                    ...paddings(vars.panelLayoutSpacing.largePadding),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                },
            },
        }),
    );

    const content = style("content", {
        display: "flex",
        flexGrow: 1,
        width: percent(100),
        justifyContent: "space-between",
    });

    const panel = style("panel", {
        width: percent(100),
        $nest: {
            [`& > .${classesPanelArea.root}:first-child .${classesPanelList.root}`]: {
                marginTop: unit(
                    (globalVars.fonts.size.title * globalVars.lineHeights.condensed) / 2 -
                        globalVariables().fonts.size.medium / 2,
                ),
            },
        },
    });

    const top = style("top", {
        width: percent(100),
        marginBottom: unit(globalVars.gutter.half),
    });

    const container = style("container", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
    });

    const fullWidth = style("fullWidth", {
        position: "relative",
        padding: 0,
    });

    const offset = panelBackgroundVariables().config.render
        ? layoutVariables().panelLayoutSpacing.withPanelBackground.gutter - panelWidgetVariables().spacing.padding * 2
        : 0;

    const leftColumn = style("leftColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        transform: translateY(`${unit(vars.panelLayoutSpacing.offset.left)}`),
        paddingRight: unit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        overflow: "initial",
        transform: translateY(`${unit(vars.panelLayoutSpacing.offset.right)}`),
        paddingLeft: unit(offset),
    });

    const middleColumn = style("middleColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        ...mediaQueries.oneColumnDown(paddings({ left: important(0), right: important(0) })),
    });

    const middleColumnMaxWidth = style("middleColumnMaxWidth", {
        $nest: {
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
            "&.hasTwoAdjacentPanels": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
        },
    });

    const breadcrumbs = style("breadcrumbs", {});

    const isSticky = style(
        "isSticky",
        {
            ...sticky(),
            height: percent(100),
            $unique: true,
        },
        mediaQueries.oneColumnDown({
            position: "relative",
            top: "auto",
            left: "auto",
            bottom: "auto",
        }),
    );

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: unit(10),
    });

    return {
        root,
        content,
        top,
        main,
        container,
        fullWidth,
        leftColumn,
        rightColumn,
        middleColumn,
        middleColumnMaxWidth,
        panel,
        isSticky,
        breadcrumbs,
        breadcrumbsContainer,
    };
});
