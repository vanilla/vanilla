import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import {LayoutTypes} from "@library/layout/layoutStyles";

export enum ThreeColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
    DESKTOP = "desktop",
}

export interface IThreeColumnLayoutMediaQueries {
    noBleed: NestedCSSProperties;
    noBleedDown: NestedCSSProperties;
    twoColumnsDown: NestedCSSProperties;
    twoColumns: NestedCSSProperties;
    oneColumn: NestedCSSProperties;
    oneColumnDown: NestedCSSProperties;
    aboveOneColumn: NestedCSSProperties;
    xs: NestedCSSProperties;
}

export const threeColumnLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();
    const Devices = ThreeColumnLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("layoutThreeColumn", forcedVars);

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: globalVars.constants.fullGutter,
        panelWidth: globalVars.panel.width,
        middleColumnWidth: globalVars.middleColumn.width,
        minimalMiddleColumnWidth: 550, // Will break if middle column width is smaller than this value.
        // narrowContentWidth: 900, // For home page widgets, narrower than full width
        breakPoints: {
            // Other break points are calculated
            twoColumns: 1200,
            xs: 500,
        },
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
        return middleColumnPaddedWidth() + panelPaddedWidth() * 2 + globalVars.gutter.size;
    };

    const breakPoints = makeThemeVars("breakPoints", {
        noBleed: contentWidth(),
        twoColumn: foundationalWidths.breakPoints.twoColumns,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panelPaddedWidth(),
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries: () => IThreeColumnLayoutMediaQueries = () => {
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
        } as IThreeColumnLayoutMediaQueries;
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

    const isFullWidth = (currentDevice) => {
        return currentDevice === ThreeColumnLayoutDevices.DESKTOP || currentDevice === ThreeColumnLayoutDevices.NO_BLEED,;
    }

    const isCompact = (currentDevice) => {
        return currentDevice === ThreeColumnLayoutDevices.XS || currentDevice === ThreeColumnLayoutDevices.MOBILE,;
    }

    return {
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
    };
});
