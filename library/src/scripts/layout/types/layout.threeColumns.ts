/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { generatePanelLayoutClasses, layoutVariables } from "../panelLayoutStyles";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { fallbackLayoutVariables, IPanelLayoutVariables } from "@library/layout/types/interface.panelLayout";
import { mediaQueryFactory } from "@library/layout/types/mediaQueryFactory";

interface IProps extends IPanelLayoutVariables {
    contentSizes: object;
}

export const threeColumnLayoutVariables = useThemeCache(
    (): IProps => {
        const layoutVars = layoutVariables();
        const Devices = fallbackLayoutVariables;

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

        const rightPanelCondition = (currentDevice, shouldRenderLeftPanel: boolean) => {
            return currentDevice === Devices.TABLET && !shouldRenderLeftPanel;
        };

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
            type: LayoutTypes.THREE_COLUMNS,
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
            rightPanelCondition,
        };
    },
);

export const threeColumnLayoutClasses = () => {
    return generatePanelLayoutClasses({
        vars: threeColumnLayoutVariables(),
        name: "threeColumnLayout",
        mediaQueries: mediaQueryFactory(threeColumnLayoutVariables().mediaQueries, LayoutTypes.THREE_COLUMNS),
    });
};
