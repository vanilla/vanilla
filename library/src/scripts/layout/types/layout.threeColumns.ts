/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { generateSectionClasses } from "../Section.styles";
import { oneColumnVariables } from "../Section.variables";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { fallbackSectionVariables, IOneColumnVariables } from "@library/layout/types/interface.panelLayout";
import { mediaQueryFactory } from "@library/layout/types/mediaQueryFactory";

interface IProps extends IOneColumnVariables {
    contentSizes: object;
}

export const threeColumnVariables = useThemeCache((): IProps => {
    const layoutVars = oneColumnVariables();
    const Devices = fallbackSectionVariables;

    const makeThemeVars = variableFactory("threeColumnLayout");

    const colors = makeThemeVars("colors", {
        ...layoutVars.colors,
    });
    const contentSizes = makeThemeVars("contentSizes", {
        ...layoutVars.contentSizes,
    });
    const gutter = makeThemeVars("gutter", {
        ...layoutVars.gutter,
    });

    const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", {
        ...layoutVars.panelLayoutSpacing,
    });

    const foundationalWidths = makeThemeVars("foundationalWidths", {
        ...layoutVars.foundationalWidths,
    });

    const panelInit = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panel = makeThemeVars("panel", {
        ...panelInit,
        paddedWidth: panelInit.width + layoutVars.gutter.full,
    });

    const middleColumnInit = makeThemeVars("middleColumn", {
        width: layoutVars.middleColumn.width,
    });

    const middleColumn = makeThemeVars("middleColumn", {
        ...middleColumnInit,
        paddedWidth: middleColumnInit.width + layoutVars.gutter.full,
    });

    const contentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2;

    const breakPoints = makeThemeVars("breakPoints", {
        noBleed: contentWidth,
        twoColumns: foundationalWidths.breakPoints.twoColumns,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries = layoutVars.mediaQueries;

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

    return {
        colors,
        contentSizes,
        gutter,
        panelLayoutSpacing,
        type: SectionTypes.THREE_COLUMNS,
        Devices,
        foundationalWidths,
        panel,
        middleColumn,
        contentWidth,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
    };
});

export const threeColumnClasses = () => {
    return generateSectionClasses({
        vars: threeColumnVariables(),
        name: "threeColumnLayout",
        mediaQueries: mediaQueryFactory(threeColumnVariables().mediaQueries, SectionTypes.THREE_COLUMNS),
    });
};
