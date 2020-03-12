/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { unit } from "@library/styles/styleHelpers";
import { calc } from "csx";
import { emptyObject } from "expect/build/utils";

export interface ISpacing {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
    horizontal?: string | number;
    vertical?: string | number;
    all?: string | number;
}

export const EMPTY_SPACING = {
    top: undefined,
    right: undefined,
    bottom: undefined,
    left: undefined,
    horizontal: undefined,
    vertical: undefined,
    all: undefined,
};

function spacings(property: "margin" | "padding", spacings?: ISpacing) {
    if (!spacings) {
        return {};
    }

    const spacingVals: NestedCSSProperties = {};

    const propertyLeft = `${property}Left`;
    const propertyRight = `${property}Right`;
    const propertyTop = `${property}Top`;
    const propertyBottom = `${property}Bottom`;

    if (spacings.all !== undefined) {
        spacingVals[propertyTop] = unit(spacings.all);
        spacingVals[propertyRight] = unit(spacings.all);
        spacingVals[propertyBottom] = unit(spacings.all);
        spacingVals[propertyLeft] = unit(spacings.all);
    }

    if (spacings.vertical !== undefined) {
        spacingVals[propertyTop] = unit(spacings.vertical);
        spacingVals[propertyBottom] = unit(spacings.vertical);
    }

    if (spacings.horizontal !== undefined) {
        spacingVals[propertyLeft] = unit(spacings.horizontal);
        spacingVals[propertyRight] = unit(spacings.horizontal);
    }

    if (spacings.top !== undefined) {
        spacingVals[propertyTop] = unit(spacings.top);
    }

    if (spacings.right !== undefined) {
        spacingVals[propertyRight] = unit(spacings.right);
    }

    if (spacings.bottom !== undefined) {
        spacingVals[propertyBottom] = unit(spacings.bottom);
    }

    if (spacings.left !== undefined) {
        spacingVals[propertyLeft] = unit(spacings.left);
    }

    return spacingVals;
}

export function paddings(spacing: ISpacing) {
    return spacings("padding", spacing);
}

export function margins(spacing: ISpacing) {
    return spacings("margin", spacing);
}

/**
 * Extend an item container outward to compensate for item paddings.
 * Usually used in a grid.
 *
 * @param itemPaddingX A single unit of padding of a horizontal side of the item.
 */
export function extendItemContainer(itemPaddingX: number): NestedCSSProperties {
    return {
        width: calc(`100% + ${unit(itemPaddingX * 2)}`),
        marginLeft: unit(-itemPaddingX),
    };
}
