import { CSSObject } from "@emotion/css";
import { IThreeColumnMediaQueries, IThreeColumnMediaQueryStyles } from "@library/layout/types/interface.threeColumns";
import {
    ITwoColumnMediaQueries,
    ITwoColumnMediaQueryStyles,
    twoColumnDevices,
} from "@library/layout/types/interface.twoColumns";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";

export enum fallbackSectionVariables {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface IOneColumnMediaQueryStyles {
    noBleed?: CSSObject;
    noBleedDown?: CSSObject;
    oneColumn?: CSSObject;
    oneColumnDown?: CSSObject;
    aboveOneColumn?: CSSObject;
    twoColumns?: CSSObject;
    twoColumnsDown?: CSSObject;
    xs?: CSSObject;
}

export interface IOneColumnMediaQueries {
    noBleed: (styles: CSSObject) => CSSObject;
    oneColumn: (styles: CSSObject) => CSSObject;
    oneColumnDown: (styles: CSSObject) => CSSObject;
    aboveOneColumn: (styles: CSSObject) => CSSObject;
    twoColumns: (styles: CSSObject) => CSSObject;
    twoColumnsDown: (styles: CSSObject) => CSSObject;
    noBleedDown: (styles: CSSObject) => CSSObject;
    xs: (styles: CSSObject) => CSSObject;
}

export type IAllMediaQueries = IThreeColumnMediaQueries | ITwoColumnMediaQueries | IOneColumnMediaQueries;

export interface IOneColumnVariables {
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
}

export interface IAllSectionMediaQueries {
    [SectionTypes.TWO_COLUMNS]?: ITwoColumnMediaQueryStyles;
    [SectionTypes.THREE_COLUMNS]?: IThreeColumnMediaQueryStyles;
}

export type ISectionMediaQueryFunction = (styles: IAllSectionMediaQueries) => CSSObject;

export type IAllSectionDevices = twoColumnDevices | fallbackSectionVariables;

export type IAllMediaQueriesForSections = ITwoColumnMediaQueries | IThreeColumnMediaQueries | {};

export type IMediaQueryFunction = (mediaQueriesForAllLayouts: IAllSectionMediaQueries) => CSSObject;
