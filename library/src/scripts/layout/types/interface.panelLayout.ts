import { CSSObject } from "@emotion/css";
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
    noBleed?: CSSObject;
    noBleedDown?: CSSObject;
    oneColumn?: CSSObject;
    oneColumnDown?: CSSObject;
    aboveOneColumn?: CSSObject;
    twoColumns?: CSSObject;
    twoColumnsDown?: CSSObject;
    xs?: CSSObject;
}

export interface IPanelLayoutMediaQueries {
    noBleed: (styles: CSSObject) => CSSObject;
    oneColumn: (styles: CSSObject) => CSSObject;
    oneColumnDown: (styles: CSSObject) => CSSObject;
    aboveOneColumn: (styles: CSSObject) => CSSObject;
    twoColumns: (styles: CSSObject) => CSSObject;
    twoColumnsDown: (styles: CSSObject) => CSSObject;
    noBleedDown: (styles: CSSObject) => CSSObject;
    xs: (styles: CSSObject) => CSSObject;
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

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => CSSObject;

export type IAllLayoutDevices = twoColumnLayoutDevices | fallbackLayoutVariables;

export type IAllMediaQueriesForLayouts = ITwoColumnLayoutMediaQueries | IThreeColumnLayoutMediaQueries | {};

export type IMediaQueryFunction = (mediaQueriesForAllLayouts: IAllLayoutMediaQueries) => CSSObject;
