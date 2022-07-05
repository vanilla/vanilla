import { media } from "@library/styles/styleShim";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSObject } from "@emotion/css";
import { IThemeVariables } from "@library/theming/themeReducer";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { fallbackSectionVariables, IOneColumnVariables } from "@library/layout/types/interface.panelLayout";

interface IProps extends IOneColumnVariables {
    contentSizes: any;
    setMediaQueries: (breakPoints, Devices) => any;
    breakPoints: any;
}
// Global defaults for layouts. These variables are not meant to be used extended through a layout type, like a three or two column layout

export const oneColumnVariables = useThemeCache((forcedVars?: IThemeVariables): IProps => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory(["layoutVariables", "panelLayout"], forcedVars);
    const Devices = fallbackSectionVariables;

    const colors = makeThemeVars("colors", {
        leftColumnBg: globalVars.mainColors.bg,
    });

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: globalVars.constants.fullGutter,
        panelWidth: globalVars.panel.width,
        middleColumn: globalVars.middleColumn.width,
        minimalMiddleColumnWidth: 550,
        narrowContentWidth: 900,
        breakPoints: {
            // Other break points are calculated
            twoColumns: 1200,
            xs: 500,
        },
    });

    const gutter = {
        full: foundationalWidths.fullGutter,
        size: foundationalWidths.fullGutter / 2,
        halfSize: foundationalWidths.fullGutter / 4,
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
            foundationalWidths.narrowContentWidth < contentWidth ? foundationalWidths.narrowContentWidth : contentWidth,
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
        padding: {
            top: gutter.halfSize * 1.5,
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
        type: SectionTypes.THREE_COLUMNS.toString(),
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
    };

    return vars;
});
