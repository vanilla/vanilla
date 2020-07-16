import { NestedCSSProperties } from "typestyle/lib/types";
import {
    IThreeColumnLayoutMediaQueries,
    IThreeColumnLayoutMediaQueryStyles,
} from "@library/layout/types/interface.threeColumns";
import {
    ITwoColumnLayoutMediaQueries,
    ITwoColumnLayoutMediaQueryStyles,
    twoColumnLayoutDevices,
} from "@library/layout/types/interface.twoColumns";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export enum fallbackLayoutVariables {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface IPanelLayoutMediaQueryStyles {
    noBleed?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    twoColumns?: NestedCSSProperties;
    twoColumnsDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export interface IPanelLayoutMediaQueries {
    noBleed: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumnDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveOneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumns: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumnsDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    noBleedDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
}

export type IAllMediaQueries = IThreeColumnLayoutMediaQueries | ITwoColumnLayoutMediaQueries | IPanelLayoutMediaQueries;

export interface IPanelLayoutVariables {
    type: string;
    Devices: any;
    colors?: any;
    foundationalWidths: any;
    gutter: any;
    panel: any;
    middleColumn: any;
    contentWidth: number;
    mediaQueries: () => IAllMediaQueries;
    isFullWidth: (currentDevice) => boolean;
    isCompact: (currentDevice) => boolean;
    panelLayoutSpacing: any;
    breakPoints: any;
    calculateDevice: () => string;
    rightPanelCondition?: (currentDevice, shouldRenderLeftPanel) => boolean;
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.TWO_COLUMNS]?: ITwoColumnLayoutMediaQueryStyles;
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices = twoColumnLayoutDevices | fallbackLayoutVariables;

export type IAllMediaQueriesForLayouts = ITwoColumnLayoutMediaQueries | IThreeColumnLayoutMediaQueries | {};

export type IMediaQueryFunction = (mediaQueriesForAllLayouts: IAllLayoutMediaQueries) => NestedCSSProperties;
