/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    IThreeColumnLayoutMediaQueries,
    threeColumnLayout,
    ThreeColumnLayoutDevices,
} from "@library/layout/types/threeColumn";
import { IOneColumnLayoutMediaQueries, oneColumnLayout, OneColumnLayoutDevices } from "@library/layout/types/oneColumn";
import {
    IOneColumnNarrowLayoutMediaQueries,
    oneColumnNarrowLayout,
    OneColumnNarrowLayoutDevices,
} from "@library/layout/types/oneColumnNarrow";
import { useLayout } from "@library/layout/LayoutContext";
import { layoutVariables } from "@library/layout/layoutStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { ILegacyLayoutMediaQueries, legacyLayout, LegacyLayoutDevices } from "@library/layout/types/legacy";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    ONE_COLUMN = "one column", // Single column, but full width of page
    NARROW = "one column narrow", // Single column, but narrower than default
    LEGACY = "legacy", // Legacy layout used on the Forum pages. The media queries are also used for older components. Newer ones should use the context
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export interface IAllLayoutMediaQueries {
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueries;
    [LayoutTypes.ONE_COLUMN]?: IOneColumnLayoutMediaQueries;
    [LayoutTypes.NARROW]?: IOneColumnNarrowLayoutMediaQueries;
    [LayoutTypes.LEGACY]?: ILegacyLayoutMediaQueries;
}

export type IAllLayoutDevices =
    | OneColumnLayoutDevices
    | OneColumnNarrowLayoutDevices
    | ThreeColumnLayoutDevices
    | LegacyLayoutDevices;

export const allLayoutVariables = () => {
    const mediaQueriesByType = {};
    const types = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout(),
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
        mediaQueries: filterQueriesByType(mediaQueriesByType),
        types,
    };
};

const filterQueriesByType = mediaQueriesByType => {
    return (mediaQueriesByLayout: IAllLayoutMediaQueries) => {
        const { type } = useLayout();

        Object.keys(mediaQueriesByLayout).forEach(layoutName => {
            if (layoutName === type) {
                // Check if we're in the correct layout before applying
                const mediaQueriesForLayout = mediaQueriesByLayout[layoutName];
                const stylesForLayout = mediaQueriesByLayout[layoutName];

                if (mediaQueriesForLayout) {
                    Object.keys(mediaQueriesForLayout).forEach(queryName => {
                        mediaQueriesForLayout[queryName] = stylesForLayout;
                        return mediaQueriesForLayout[queryName];
                    });
                }
            }
        });
        return {};
    };
};

export const layoutVarsForCurrentLayout = (props: { type: LayoutTypes }) => {
    const { type = LayoutTypes.THREE_COLUMNS } = props;

    if (layoutVariables().layouts.types[type]) {
        return layoutVariables().layouts.types[type];
    } else {
        return layoutVariables().layouts.types[LayoutTypes.THREE_COLUMNS];
    }
};
