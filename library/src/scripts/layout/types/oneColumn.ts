/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { LayoutTypes } from "@library/layout/types/LayoutTypes";

export enum OneColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    DESKTOP = "desktop",
}
export interface IOneColumnLayoutMediaQueryStyles {
    small?: NestedCSSProperties;
    smallDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export interface IOneColumnLayoutMediaQueries {
    small: (styles: NestedCSSProperties, useMinWidth?: boolean) => NestedCSSProperties;
    smallDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
}

export const oneColumnLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const Devices = OneColumnLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("layoutOneColumn", forcedVars);

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        width: globalVars.contentWidth(),
        breakPoints: {
            // Other break points are calculated
            small: 1200,
            xs: 500,
        },
    });

    const contentWidth = () => {
        return foundationalWidths.width;
    };

    const breakPoints = makeThemeVars("breakPoints", {
        small: contentWidth(),
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries = (): IOneColumnLayoutMediaQueries => {
        const small = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(breakPoints.small),
                    minWidth: useMinWidth ? px(breakPoints.xs + 1) : undefined,
                },
                styles,
            );
        };

        const smallDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.small),
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
            small,
            smallDown,
            xs,
        } as IOneColumnLayoutMediaQueries;
    };

    const calculateDevice = () => {
        const width = document.body.clientWidth;
        if (width <= breakPoints.xs) {
            return Devices.XS;
        } else if (width <= breakPoints.small) {
            return Devices.MOBILE;
        } else {
            return Devices.DESKTOP;
        }
    };

    const isFullWidth = currentDevice => {
        return currentDevice === Devices.DESKTOP;
    };

    const isCompact = currentDevice => {
        return currentDevice === OneColumnLayoutDevices.XS || currentDevice === OneColumnLayoutDevices.MOBILE;
    };

    return {
        type: LayoutTypes.ONE_COLUMN,
        Devices,
        foundationalWidths,
        contentWidth,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
    };
});
