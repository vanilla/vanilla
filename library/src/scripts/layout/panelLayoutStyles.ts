/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, important, percent, viewHeight } from "csx";
import { cssRule, media } from "@library/styles/styleShim";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSObject } from "@emotion/css";
import { IThemeVariables } from "@library/theming/themeReducer";
import { sticky } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { panelBackgroundVariables } from "@library/layout/PanelBackground.variables";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import {
    fallbackLayoutVariables,
    IMediaQueryFunction,
    IPanelLayoutVariables,
} from "@library/layout/types/interface.panelLayout";
import { logError } from "@vanilla/utils";
import { Mixins } from "@library/styles/Mixins";

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
        const setMediaQueries = (breakPoints) => {
            const noBleed = (styles: CSSObject, useMinWidth: boolean = true): CSSObject => {
                return media(
                    {
                        maxWidth: breakPoints.noBleed,
                        minWidth: useMinWidth ? breakPoints.twoColumns + 1 : undefined,
                    },
                    styles,
                );
            };

            const noBleedDown = (styles: CSSObject): CSSObject => {
                return media(
                    {
                        maxWidth: breakPoints.noBleed,
                    },
                    styles,
                );
            };

            const twoColumnsDown = (styles: CSSObject): CSSObject => {
                return media(
                    {
                        maxWidth: breakPoints.twoColumns,
                    },
                    styles,
                );
            };

            const twoColumns = (styles: CSSObject, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: breakPoints.twoColumns,
                        minWidth: useMinWidth ? breakPoints.oneColumn + 1 : undefined,
                    },
                    styles,
                );
            };

            const oneColumn = (styles: CSSObject, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: breakPoints.oneColumn,
                        minWidth: useMinWidth ? breakPoints.xs + 1 : undefined,
                    },
                    styles,
                );
            };

            const oneColumnDown = (styles: CSSObject): CSSObject => {
                return media(
                    {
                        maxWidth: breakPoints.oneColumn,
                    },
                    styles,
                );
            };

            const aboveOneColumn = (styles: CSSObject): CSSObject => {
                return media(
                    {
                        minWidth: breakPoints.oneColumn + 1,
                    },
                    styles,
                );
            };

            const xs = (styles: CSSObject): CSSObject => {
                return media(
                    {
                        maxWidth: breakPoints.xs,
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

        const isFullWidth = (currentDevice) => {
            return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
        };

        const isCompact = (currentDevice) => {
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
        ...Mixins.margin(vars.panelLayoutSpacing.margin),
        width: percent(100),
        ...{
            [`&.noBreadcrumbs > .${main}`]: {
                paddingTop: styleUnit(globalVars.gutter.size),
                ...mediaQueries({
                    [LayoutTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            paddingTop: 0,
                        },
                    },
                }),
            },
            "&.hasTopPadding": {
                paddingTop: styleUnit(vars.panelLayoutSpacing.extraPadding.top),
            },
            "&.hasTopPadding.noBreadcrumbs": {
                paddingTop: styleUnit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
            },
            "&.hasLargePadding": {
                ...Mixins.padding(vars.panelLayoutSpacing.largePadding),
            },
            ...mediaQueries({
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: {
                        ...{
                            "&.hasTopPadding.noBreadcrumbs": {
                                paddingTop: styleUnit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                            },
                        },
                    },
                },
            }),
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
        ...{
            [`& > .panelArea:first-child .panelList`]: {
                marginTop: styleUnit(
                    (globalVars.fonts.size.title * globalVars.lineHeights.condensed) / 2 -
                        globalVariables().fonts.size.medium / 2,
                ),
            },
        },
    });

    const top = style("top", {
        width: percent(100),
        marginBottom: styleUnit(globalVars.gutter.half),
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
        width: styleUnit(vars.panel.paddedWidth),
        flexBasis: styleUnit(vars.panel.paddedWidth),
        minWidth: styleUnit(vars.panel.paddedWidth),
        paddingRight: styleUnit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: styleUnit(vars.panel.paddedWidth),
        flexBasis: styleUnit(vars.panel.paddedWidth),
        minWidth: styleUnit(vars.panel.paddedWidth),
        overflow: "initial",
        paddingLeft: styleUnit(offset),
    });

    const mainColumn = style("mainColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        paddingBottom: styleUnit(vars.panelLayoutSpacing.extraPadding.bottom),
        ...mediaQueries({
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    ...Mixins.padding({
                        left: important(0),
                        right: important(0),
                    }),
                },
            },
        }),
    });

    const mainColumnMaxWidth = style("mainColumnMaxWidth", {
        ...{
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),

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
                flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
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
        ...mediaQueries({
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    position: "relative",
                    top: "auto",
                    left: "auto",
                    bottom: "auto",
                },
            },
        }),
    });

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: styleUnit(14),
    });

    const layoutSpecificStyles = (style) => {
        const middleColumnMaxWidth = style("middleColumnMaxWidth", {
            ...{
                "&.hasAdjacentPanel": {
                    flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
                    maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
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
                    flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                    maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
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
            width: styleUnit(vars.panel.paddedWidth),
            flexBasis: styleUnit(vars.panel.paddedWidth),
            minWidth: styleUnit(vars.panel.paddedWidth),
            paddingRight: offset ? styleUnit(offset) : undefined,
        });

        const rightColumn = style("rightColumn", {
            position: "relative",
            width: styleUnit(vars.panel.paddedWidth),
            flexBasis: styleUnit(vars.panel.paddedWidth),
            minWidth: styleUnit(vars.panel.paddedWidth),
            overflow: "initial",
            paddingRight: offset ? styleUnit(offset) : undefined,
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
