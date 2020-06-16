import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { calc, percent, px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { unit } from "@library/styles/styleHelpers";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { LayoutTypes } from "@library/layout/types/LayoutUtils";
import { layoutVariables } from "@library/layout/layoutStyles";

export enum ThreeColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface IThreeColumnLayoutMediaQueries {
    noBleed?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    twoColumns?: NestedCSSProperties;
    twoColumnsDown?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export const threeColumnLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();
    const layoutVars = layoutVariables();
    const Devices = ThreeColumnLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("layoutThreeColumn", forcedVars);

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        ...globalVars.foundationalWidths,
    });

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panelPaddedWidth = () => {
        return panel.width + globalVars.constants.fullGutter;
    };

    const middleColumn = makeThemeVars("middleColumn", {
        width: globalVars.middleColumn.width,
    });

    const middleColumnPaddedWidth = () => {
        return middleColumn.width + globalVars.constants.fullGutter;
    };

    const contentWidth = () => {
        return globalVars.contentWidth();
    };

    const breakPoints = makeThemeVars("breakPoints", {
        noBleed: contentWidth(),
        twoColumn: foundationalWidths.breakPoints.twoColumns,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panelPaddedWidth(),
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries = () => {
        const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(breakPoints.noBleed),
                    minWidth: useMinWidth ? px(breakPoints.twoColumn + 1) : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.noBleed),
                },
                styles,
            );
        };

        const twoColumnsDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.twoColumn),
                },
                styles,
            );
        };

        const twoColumns = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(breakPoints.twoColumn),
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

        const oneColumnDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.oneColumn),
                },
                styles,
            );
        };

        const aboveOneColumn = (styles: NestedCSSProperties) => {
            return media(
                {
                    minWidth: px(breakPoints.oneColumn + 1),
                },
                styles,
            );
        };

        const xs = (styles: NestedCSSProperties) => {
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
            twoColumnsDown,
            twoColumns,
            oneColumn,
            oneColumnDown,
            aboveOneColumn,
            xs,
        };
    };

    const calculateDevice = () => {
        const width = document.body.clientWidth;
        if (width <= breakPoints.xs) {
            return Devices.XS;
        } else if (width <= breakPoints.oneColumn) {
            return Devices.MOBILE;
        } else if (width <= breakPoints.twoColumn) {
            return Devices.TABLET;
        } else if (width <= breakPoints.noBleed) {
            return Devices.NO_BLEED;
        } else {
            return Devices.DESKTOP;
        }
    };

    const isFullWidth = currentDevice => {
        return (
            currentDevice === ThreeColumnLayoutDevices.DESKTOP || currentDevice === ThreeColumnLayoutDevices.NO_BLEED
        );
    };

    const isCompact = currentDevice => {
        return currentDevice === ThreeColumnLayoutDevices.XS || currentDevice === ThreeColumnLayoutDevices.MOBILE;
    };

    const offset = panelBackgroundVariables().config.render
        ? layoutVars.spacing.withPanelBackground.gutter - panelWidgetVariables().spacing.padding * 2
        : 0;

    const layoutSpecificStyles = style => {
        const myMediaQueries = mediaQueries();
        const middleColumnMaxWidth = style("middleColumnMaxWidth", {
            $nest: {
                "&.hasAdjacentPanel": {
                    flexBasis: calc(`100% - ${unit(panelPaddedWidth())}`),
                    maxWidth: calc(`100% - ${unit(panelPaddedWidth())}`),
                    ...myMediaQueries.oneColumnDown({
                        flexBasis: percent(100),
                        maxWidth: percent(100),
                    }),
                },
                "&.hasTwoAdjacentPanels": {
                    flexBasis: calc(`100% - ${unit(panelPaddedWidth() * 2)}`),
                    maxWidth: calc(`100% - ${unit(panelPaddedWidth() * 2)}`),
                    ...myMediaQueries.oneColumnDown({
                        flexBasis: percent(100),
                        maxWidth: percent(100),
                    }),
                },
            },
        });

        const leftColumn = style("leftColumn", {
            position: "relative",
            width: unit(panelPaddedWidth()),
            flexBasis: unit(panelPaddedWidth()),
            minWidth: unit(panelPaddedWidth()),
            paddingRight: unit(offset),
        });

        const rightColumn = style("rightColumn", {
            position: "relative",
            width: unit(panelPaddedWidth()),
            flexBasis: unit(panelPaddedWidth()),
            minWidth: unit(panelPaddedWidth()),
            overflow: "initial",
            paddingLeft: unit(offset),
        });

        return {
            leftColumn,
            rightColumn,
            middleColumnMaxWidth,
        };
    };

    return {
        type: LayoutTypes.THREE_COLUMNS,
        Devices,
        foundationalWidths,
        panel,
        panelPaddedWidth,
        middleColumn,
        middleColumnPaddedWidth,
        contentWidth,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
        layoutSpecificStyles,
    };
});
