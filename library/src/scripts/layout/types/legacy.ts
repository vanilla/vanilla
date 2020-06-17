/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { calc, px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { LayoutTypes } from "@library/layout/types/LayoutTypes";
import { ThreeColumnLayoutDevices } from "@library/layout/types/threeColumn";
import { unit } from "@library/styles/styleHelpers";

export enum LegacyLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface ILegacyLayoutMediaQueryStyles {
    noBleed?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    mobile?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    twoColumns?: NestedCSSProperties;
    tablet?: NestedCSSProperties;
    tabletDown?: NestedCSSProperties;
    twoColumnsDown?: NestedCSSProperties;
    mobileDown?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
    aboveMobile?: NestedCSSProperties;
}

export interface ILegacyLayoutMediaQueries {
    noBleed: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    mobile: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumnDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveOneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    tabletDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumns: (styles: NestedCSSProperties) => NestedCSSProperties;
    tablet: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumnsDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    mobileDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    noBleedDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveMobile: (styles: NestedCSSProperties) => NestedCSSProperties;
}

export const legacyLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const Devices = LegacyLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("forumLayout", forcedVars);

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        ...globalVars.foundationalWidths,
    });

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panelPaddedWidth = () => {
        return panel.width + globalVars.constants.fullGutter;
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

    const mediaQueries = (): ILegacyLayoutMediaQueries => {
        console.log("OY!");
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

        // Alias
        const tabletDown = (styles: NestedCSSProperties) => {
            return twoColumnsDown(styles);
        };

        // Alias
        const tablet = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return twoColumns(styles, useMinWidth);
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

        const mobile = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(foundationalWidths.breakPoints.twoColumns),
                    minWidth: useMinWidth ? px(foundationalWidths.breakPoints.xs + 1) : undefined,
                },
                styles,
            );
        };

        const aboveMobile = (styles: NestedCSSProperties) => {
            return media(
                {
                    minWidth: px(foundationalWidths.breakPoints.twoColumns + 1),
                },
                styles,
            );
        };

        const mobileDown = (styles: NestedCSSProperties) => {
            return mobile(styles, false);
        };

        return {
            noBleed,
            noBleedDown,
            twoColumnsDown,
            tabletDown,
            twoColumns,
            tablet,
            oneColumn,
            oneColumnDown,
            aboveOneColumn,
            mobile,
            mobileDown,
            aboveMobile,
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

    const gutter = makeThemeVars("gutter", {
        ...globalVars.gutter,
        mainGutterOffset: 60 - globalVars.gutter.size,
    });

    const main = makeThemeVars("main", {
        width: calc(`100% - ${unit(panelPaddedWidth() + gutter.mainGutterOffset)}`),
        topSpacing: 40,
    });

    const cell = makeThemeVars("cell", {
        paddings: {
            horizontal: 8,
            vertical: 12,
        },
    });

    return {
        type: LayoutTypes.LEGACY,
        Devices,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
        contentWidth,
        panelPaddedWidth,
        gutter,
        main,
        cell,
    };
});
