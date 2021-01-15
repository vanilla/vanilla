/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";

export enum twoColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface ITwoColumnLayoutMediaQueryStyles {
    noBleed?: CSSObject;
    noBleedDown?: CSSObject;
    oneColumn?: CSSObject;
    oneColumnDown?: CSSObject;
    aboveOneColumn?: CSSObject;
    xs?: CSSObject;
}

export interface ITwoColumnLayoutMediaQueries {
    noBleed: (styles: CSSObject) => CSSObject;
    oneColumn: (styles: CSSObject) => CSSObject;
    oneColumnDown: (styles: CSSObject) => CSSObject;
    aboveOneColumn: (styles: CSSObject) => CSSObject;
    noBleedDown: (styles: CSSObject) => CSSObject;
    xs: (styles: CSSObject) => CSSObject;
}
