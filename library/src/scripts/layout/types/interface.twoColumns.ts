/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";

export enum twoColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface ITwoColumnLayoutMediaQueryStyles {
    noBleed?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export interface ITwoColumnLayoutMediaQueries {
    noBleed: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumnDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveOneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    noBleedDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
}
