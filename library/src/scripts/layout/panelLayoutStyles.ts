/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, important, percent, px, viewHeight } from "csx";
import { cssRule, media } from "typestyle";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { NestedCSSProperties } from "typestyle/lib/types";
import { IThemeVariables } from "@library/theming/themeReducer";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import { sticky, unit } from "@library/styles/styleHelpers";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import {
    fallbackLayoutVariables,
    IMediaQueryFunction,
    IPanelLayoutVariables,
} from "@library/layout/types/interface.panelLayout";
import { logError } from "@vanilla/utils";

interface IProps extends IPanelLayoutVariables {
    contentSizes: any;
    setMediaQueries: (breakPoints, Devices) => any;
    panelLayoutBreakPoints: any;
}

// Global defaults for layouts. These variables are not meant to be used extended through a layout type, like a three or two column layout
export const layoutVariables = useThemeCache(
    (forcedVars?: IThemeVariables): IProps => {
        const globalVars = globalVariables(forcedVars);
        const makeThemeVars = variableFactory("layoutVariables", forcedVars);
        const Devices = fallbackLayoutVariables;

        const colors = makeThemeVars("colors", {
            leftColumnBg: globalVars.mainColors.bg,
        });

        // Important variables that will be used to calculate other variables
        const foundationalWidths = makeThemeVars("foundationalWidths", {
            fullGutter: globalVars.constants.fullGutter,
            panelWidth: globalVars.panel.width,
            middleColumn: globalVars.middleColumn.width,
            minimalMiddleColumnWidth: 550, // Will break if middle column width is smaller than this value.
            narrowContentWidth: 900, // For home page widgets, narrower than full width
            breakPoints: {
                // Other break points are calculated
                twoColumns: 1200,
                xs: 500,
            },
        });

        const gutter = {
            full: foundationalWidths.fullGutter, // 40
            size: foundationalWidths.fullGutter / 2, // 20
            halfSize: foundationalWidths.fullGutter / 4, // 10
            quarterSize: foundationalWidths.fullGutter / 8, // 5
        };

        const panelInit = makeThemeVars("panel", {
            width: foundationalWidths.panelWidth,
        });

        const panel = makeThemeVars("panel", {
            ...panelInit,
            paddedWidth: panelInit.width + gutter.full,
        });

        const middleColumnInit = makeThemeVars("middleColumn", {
            width: foundationalWidths.middleColumn,
        });

        const middleColumn = makeThemeVars("middleColumn", {
            ...middleColumnInit,
            paddedWidth: middleColumnInit.width + gutter.full,
        });

        const contentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2;

        // @Deprecated - Needs to be split into separate layouts
        const contentSizes = makeThemeVars("content", {
            full: contentWidth,
            narrow:
                foundationalWidths.narrowContentWidth < contentWidth
                    ? foundationalWidths.narrowContentWidth
                    : contentWidth,
        });

        // @Deprecated - Use LayoutContext to get variables
        const breakPoints = makeThemeVars("breakPoints", {
            noBleed: contentWidth,
            twoColumns: foundationalWidths.breakPoints.twoColumns,
            oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
            xs: foundationalWidths.breakPoints.xs,
        });

        // @Deprecated - set to reduce refactoring changes
        const panelLayoutBreakPoints = breakPoints;

        const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", {
            margin: {
                top: 0,
                bottom: 0,
            },
            padding: {
                top: gutter.halfSize * 1.5,
            },
            extraPadding: {
                top: 32,
                bottom: 32,
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

        // Allows to be recalculated in another layout (i.e. the three column layout)
        const setMediaQueries = breakPoints => {
            const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true): NestedCSSProperties => {
                return media(
                    {
                        maxWidth: px(breakPoints.noBleed),
                        minWidth: useMinWidth ? px(breakPoints.twoColumns + 1) : undefined,
                    },
                    styles,
                );
            };

            const noBleedDown = (styles: NestedCSSProperties): NestedCSSProperties => {
                return media(
                    {
                        maxWidth: px(breakPoints.noBleed),
                    },
                    styles,
                );
            };

            const twoColumnsDown = (styles: NestedCSSProperties): NestedCSSProperties => {
                return media(
                    {
                        maxWidth: px(breakPoints.twoColumns),
                    },
                    styles,
                );
            };

            const twoColumns = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: px(breakPoints.twoColumns),
                        minWidth: useMinWidth ? px(breakPoints.oneColumn + 1) : undefined,
                    },
                    styles,
                );
            };

            const oneColumn = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: px(breakPoints.oneColumn),
                        minWidth: useMinWidth ? px(breakPoints.xs + 1) : undefined,
                    },
                    styles,
                );
            };

            const oneColumnDown = (styles: NestedCSSProperties): NestedCSSProperties => {
                return media(
                    {
                        maxWidth: px(breakPoints.oneColumn),
                    },
                    styles,
                );
            };

            const aboveOneColumn = (styles: NestedCSSProperties): NestedCSSProperties => {
                return media(
                    {
                        minWidth: px(breakPoints.oneColumn + 1),
                    },
                    styles,
                );
            };

            const xs = (styles: NestedCSSProperties): NestedCSSProperties => {
                return media(
                    {
                        maxWidth: px(breakPoints.xs),
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
                aboveOneColumn,
                xs,
            };
        };

        // @Deprecated - Use LayoutContext to get media queries of current layout.
        const mediaQueries = () => {
            return setMediaQueries(breakPoints);
        };

        // @Deprecated - Use a specific layout, like the three or two column layout and use the context
        const calculateDevice = () => {
            const width = document.body.clientWidth;
            if (width <= breakPoints.xs) {
                return Devices.XS.toString();
            } else if (width <= breakPoints.oneColumn) {
                return Devices.MOBILE.toString();
            } else if (width <= breakPoints.twoColumns) {
                return Devices.TABLET.toString();
            } else if (width <= breakPoints.noBleed) {
                return Devices.NO_BLEED.toString();
            } else {
                return Devices.DESKTOP.toString();
            }
        };

        const isFullWidth = currentDevice => {
            return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
        };

        const isCompact = currentDevice => {
            return currentDevice === Devices.XS || currentDevice === Devices.MOBILE;
        };

        const vars: IProps = {
            type: LayoutTypes.THREE_COLUMNS.toString(),
            Devices,
            colors,
            foundationalWidths,
            gutter,
            setMediaQueries,
            panel,
            middleColumn,
            contentSizes,
            calculateDevice,
            contentWidth,
            mediaQueries,
            isFullWidth,
            isCompact,
            panelLayoutSpacing,
            breakPoints,
            panelLayoutBreakPoints,
        };

        return vars;
    },
);

export interface IPanelLayoutClasses {
    root: string;
    content: string;
    top: string;
    main: string;
    container: string;
    fullWidth: string;
    leftColumn?: string;
    rightColumn: string;
    mainColumn: string;
    mainColumnMaxWidth: string;
    panel: string;
    isSticky: string;
    breadcrumbs: string;
    breadcrumbsContainer: string;
    layoutSpecificStyles?: any;
}

export const doNothingWithMediaQueries = (styles: any) => {
    logError("Media queries are undefined, unable to set the following styles: ", styles);
    return {};
};

export const generatePanelLayoutClasses = (props: {
    vars: IPanelLayoutVariables;
    name: string;
    mediaQueries: IMediaQueryFunction;
}): IPanelLayoutClasses => {
    const { vars, name, mediaQueries } = props;
    const globalVars = globalVariables();
    const style = styleFactory(name);

    if (typeof mediaQueries !== "function") {
        logError("mediaQueries needs to be a function of type IMediaQueryFunction: ", mediaQueries);
        return {} as IPanelLayoutClasses;
    }

    const main = style("main", {
        minHeight: viewHeight(20),
        width: percent(100),
    });

    const root = style({
        ...margins(vars.panelLayoutSpacing.margin),
        width: percent(100),
        $nest: {
            [`&.noBreadcrumbs > .${main}`]: {
                paddingTop: unit(globalVars.gutter.size),
                ...mediaQueries({
                    [LayoutTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            paddingTop: 0,
                        },
                    },
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
            ...mediaQueries({
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: {
                        $nest: {
                            "&.hasTopPadding.noBreadcrumbs": {
                                paddingTop: unit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                            },
                        },
                    },
                },
            }).$nest,
        },
    });

    const content = style("content", {
        display: "flex",
        flexGrow: 1,
        width: percent(100),
        justifyContent: "space-between",
    });

    const panel = style("panel", {
        width: percent(100),
        $nest: {
            [`& > .panelArea:first-child .panelList`]: {
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
        ? layoutVariables().panelLayoutSpacing.withPanelBackground.gutter - globalVars.widget.padding * 2
        : 0;

    const leftColumn = style("leftColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        paddingRight: unit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        overflow: "initial",
        paddingLeft: unit(offset),
    });

    const mainColumn = style("mainColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        paddingBottom: unit(vars.panelLayoutSpacing.extraPadding.bottom),
        ...mediaQueries({
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    ...paddings({
                        left: important(0),
                        right: important(0),
                    }),
                },
            },
        }),
    });

    const mainColumnMaxWidth = style("mainColumnMaxWidth", {
        $nest: {
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth)}`),

                ...mediaQueries({
                    [LayoutTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        },
                    },
                }),
            },
            "&.hasTwoAdjacentPanels": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                ...mediaQueries({
                    [LayoutTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        },
                    },
                }),
            },
        },
    });

    const breadcrumbs = style("breadcrumbs", {});

    const isSticky = style("isSticky", {
        ...sticky(),
        height: percent(100),
        $unique: true,
        ...mediaQueries({
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    position: "relative",
                    top: "auto",
                    left: "auto",
                    bottom: "auto",
                },
            },
        }).$nest,
    });

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: unit(14),
    });

    const layoutSpecificStyles = style => {
        const middleColumnMaxWidth = style("middleColumnMaxWidth", {
            $nest: {
                "&.hasAdjacentPanel": {
                    flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                    maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                    ...mediaQueries({
                        [LayoutTypes.THREE_COLUMNS]: {
                            oneColumnDown: {
                                flexBasis: percent(100),
                                maxWidth: percent(100),
                            },
                        },
                    }),
                },
                "&.hasTwoAdjacentPanels": {
                    flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                    maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                    ...mediaQueries({
                        [LayoutTypes.THREE_COLUMNS]: {
                            oneColumnDown: {
                                flexBasis: percent(100),
                                maxWidth: percent(100),
                            },
                        },
                    }),
                },
            },
        });

        const leftColumn = style("leftColumn", {
            position: "relative",
            width: unit(vars.panel.paddedWidth),
            flexBasis: unit(vars.panel.paddedWidth),
            minWidth: unit(vars.panel.paddedWidth),
            paddingRight: offset ? unit(offset) : undefined,
        });

        const rightColumn = style("rightColumn", {
            position: "relative",
            width: unit(vars.panel.paddedWidth),
            flexBasis: unit(vars.panel.paddedWidth),
            minWidth: unit(vars.panel.paddedWidth),
            overflow: "initial",
            paddingRight: offset ? unit(offset) : undefined,
        });

        return {
            leftColumn,
            rightColumn,
            middleColumnMaxWidth,
        };
    };

    return {
        root,
        content,
        top,
        main,
        container,
        fullWidth,
        leftColumn,
        rightColumn,
        mainColumn,
        mainColumnMaxWidth,
        panel,
        isSticky,
        breadcrumbs,
        breadcrumbsContainer,
        layoutSpecificStyles,
    };
};
