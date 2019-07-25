/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { unit } from "@library/styles/styleHelpers";

export interface IPaddings {
    top?: string | number;
    right?: string | number;
    bottom?: string | number;
    left?: string | number;
    horizontal?: string | number;
    vertical?: string | number;
    all?: string | number;
}

export const paddings = (styles: IPaddings) => {
    const paddingVals = {} as NestedCSSProperties;

    if (!styles) {
        return paddingVals;
    }

    if (styles.all !== undefined) {
        paddingVals.paddingTop = unit(styles.all);
        paddingVals.paddingRight = unit(styles.all);
        paddingVals.paddingBottom = unit(styles.all);
        paddingVals.paddingLeft = unit(styles.all);
    }

    if (styles.vertical !== undefined) {
        paddingVals.paddingTop = unit(styles.vertical);
        paddingVals.paddingBottom = unit(styles.vertical);
    }

    if (styles.horizontal !== undefined) {
        paddingVals.paddingLeft = unit(styles.horizontal);
        paddingVals.paddingRight = unit(styles.horizontal);
    }

    if (styles.top !== undefined) {
        paddingVals.paddingTop = unit(styles.top);
    }

    if (styles.right !== undefined) {
        paddingVals.paddingRight = unit(styles.right);
    }

    if (styles.bottom !== undefined) {
        paddingVals.paddingBottom = unit(styles.bottom);
    }

    if (styles.left !== undefined) {
        paddingVals.paddingLeft = unit(styles.left);
    }

    return paddingVals as NestedCSSProperties;
};
