/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    IThreeColumnLayoutMediaQueries,
    IThreeColumnLayoutMediaQueryStyles,
    threeColumnLayout,
    ThreeColumnLayoutDevices,
} from "@library/layout/types/threeColumn";
import {
    IOneColumnLayoutMediaQueries,
    IOneColumnLayoutMediaQueryStyles,
    oneColumnLayout,
    OneColumnLayoutDevices,
} from "@library/layout/types/oneColumn";
import {
    IOneColumnNarrowLayoutMediaQueries,
    IOneColumnNarrowLayoutMediaQueryStyles,
    oneColumnNarrowLayout,
    OneColumnNarrowLayoutDevices,
} from "@library/layout/types/oneColumnNarrow";
import { NestedCSSProperties } from "typestyle/lib/types";
import {
    ILegacyLayoutMediaQueries,
    ILegacyLayoutMediaQueryStyles,
    legacyLayout,
    LegacyLayoutDevices,
} from "@library/layout/types/legacy";
import { LayoutTypes } from "@library/layout/types/LayoutTypes";

export interface IAllLayoutMediaQueries {
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
    [LayoutTypes.ONE_COLUMN]?: IOneColumnLayoutMediaQueryStyles;
    [LayoutTypes.NARROW]?: IOneColumnNarrowLayoutMediaQueryStyles;
    [LayoutTypes.LEGACY]?: ILegacyLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices =
    | OneColumnLayoutDevices
    | OneColumnNarrowLayoutDevices
    | ThreeColumnLayoutDevices
    | LegacyLayoutDevices;

export const layoutVarsByLayoutType = (props: { type: LayoutTypes; layoutVariables }) => {
    const { type = LayoutTypes.THREE_COLUMNS, layoutVariables } = props;
    if (layoutVariables.layouts.types[type]) {
        return layoutVariables.layouts.types[type];
    } else {
        return layoutVariables.layouts.types[LayoutTypes.THREE_COLUMNS];
    }
};

export const allLayoutVariables = (props: { offset }) => {
    const { offset } = props;
    const mediaQueriesByType = {};
    const types = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout({ vars: { offset } }),
        [LayoutTypes.ONE_COLUMN]: oneColumnLayout(),
        [LayoutTypes.NARROW]: oneColumnNarrowLayout(),
        [LayoutTypes.LEGACY]: legacyLayout(),
    };

    Object.keys(LayoutTypes).forEach(layoutName => {
        const enumKey = LayoutTypes[layoutName];
        const layoutData = types[enumKey];
        mediaQueriesByType[enumKey] = layoutData.mediaQueries();
    });

    return {
        mediaQueries: mediaQueriesByType,
        types,
    };
};
