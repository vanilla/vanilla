/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { media } from "@library/styles/styleShim";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { generateSectionClasses } from "../Section.styles";
import { oneColumnVariables } from "../Section.variables";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { ITwoColumnMediaQueries, twoColumnDevices } from "@library/layout/types/interface.twoColumns";
import { IOneColumnVariables } from "@library/layout/types/interface.panelLayout";
import { mediaQueryFactory } from "@library/layout/types/mediaQueryFactory";

export const twoColumnVariables = useThemeCache((): IOneColumnVariables => {
    const layoutVars = oneColumnVariables();
    const Devices = twoColumnDevices;
    const { fullGutter } = layoutVars.foundationalWidths;

    const makeThemeVars = variableFactory("twoColumnLayout");

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter,
        minimalMiddleColumnWidth: 600,
        panelWidth: 292,
        breakPoints: {
            xs: layoutVars.foundationalWidths.breakPoints.xs,
        }, // Other break point are calculated
    });

    const gutter = makeThemeVars("gutter", {
        full: foundationalWidths.fullGutter, // 40
        size: foundationalWidths.fullGutter / 2, // 20
        halfSize: foundationalWidths.fullGutter / 4, // 10
        quarterSize: foundationalWidths.fullGutter / 8, // 5
    });

    const panelInit = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panel = makeThemeVars("panel", {
        ...panelInit,
        paddedWidth: panelInit.width + layoutVars.gutter.full,
    });

    const middleColumnInit = makeThemeVars("mainColumn", {
        width: layoutVars.contentWidth - panel.paddedWidth - layoutVars.gutter.full,
    });

    const middleColumn = makeThemeVars("mainColumn", {
        ...middleColumnInit,
        paddedWidth: middleColumnInit.width + layoutVars.gutter.full,
    });

    const breakPoints = makeThemeVars("breakPoints", {
        noBleed: layoutVars.contentWidth,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
        xs: foundationalWidths.breakPoints.xs,
    });

    const contentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2;

    const mediaQueries = (): ITwoColumnMediaQueries => {
        const noBleed = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: breakPoints.noBleed,
                    minWidth: useMinWidth ? breakPoints.oneColumn + 1 : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: CSSObject) => {
            return media(
                {
                    maxWidth: breakPoints.noBleed,
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

        const oneColumnDown = (styles: CSSObject) => {
            return media(
                {
                    maxWidth: breakPoints.oneColumn,
                },
                styles,
            );
        };

        const aboveOneColumn = (styles: CSSObject) => {
            return media(
                {
                    minWidth: breakPoints.oneColumn + 1,
                },
                styles,
            );
        };

        const xs = (styles: CSSObject) => {
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
            oneColumn,
            oneColumnDown,
            aboveOneColumn,
            xs,
        };
    };

    const calculateDevice = (): string => {
        const width = document.body.clientWidth;
        if (width <= breakPoints.xs) {
            return Devices.XS.toString();
        } else if (width <= breakPoints.oneColumn) {
            return Devices.MOBILE.toString();
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

    const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", layoutVars.panelLayoutSpacing);

    return {
        type: SectionTypes.TWO_COLUMNS.toString(),
        Devices,
        foundationalWidths,
        panel,
        middleColumn,
        gutter,
        panelLayoutSpacing,
        contentWidth,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
    };
});

export const twoColumnClasses = () => {
    return generateSectionClasses({
        vars: twoColumnVariables(),
        name: "twoColumnLayout",
        mediaQueries: mediaQueryFactory(twoColumnVariables().mediaQueries, SectionTypes.TWO_COLUMNS),
    });
};
