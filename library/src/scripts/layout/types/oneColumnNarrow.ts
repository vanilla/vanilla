import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";

export enum OneColumnNarrowLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    DESKTOP = "desktop",
}

export interface IOneColumnNarrowLayoutMediaQueries {
    xs?: NestedCSSProperties;
}

export const oneColumnNarrowLayout = useThemeCache((forcedVars?: IThemeVariables) => {
    const Devices = OneColumnNarrowLayoutDevices;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("layoutOneNarrowColumn", forcedVars);

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        width: 900,
        breakPoints: {
            xs: 500,
        },
    });

    const contentWidth = () => {
        return foundationalWidths.width;
    };

    const breakPoints = makeThemeVars("breakPoints", {
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries = () => {
        const xs = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.xs),
                },
                styles,
            );
        };

        return {
            xs,
        };
    };

    const calculateDevice = () => {
        const width = document.body.clientWidth;
        if (width <= breakPoints.xs) {
            return Devices.XS;
        } else {
            return Devices.DESKTOP;
        }
    };

    const isFullWidth = currentDevice => {
        return currentDevice === Devices.DESKTOP;
    };

    const isCompact = currentDevice => {
        return currentDevice === OneColumnNarrowLayoutDevices.XS;
    };

    return {
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
