import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";

export enum LegacyLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface ILegacyLayoutMediaQueries {
    noBleed?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    twoColumns?: NestedCSSProperties;
    twoColumnsDown?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export const legacyLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();
    const Devices = LegacyLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("layoutLegacy", forcedVars);

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
        return currentDevice === LegacyLayoutDevices.DESKTOP || currentDevice === LegacyLayoutDevices.NO_BLEED;
    };

    const isCompact = currentDevice => {
        return currentDevice === LegacyLayoutDevices.XS || currentDevice === LegacyLayoutDevices.MOBILE;
    };

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
