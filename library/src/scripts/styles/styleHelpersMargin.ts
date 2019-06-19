/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { unit } from "@library/styles/styleHelpers";

export interface IMargins {
    top?: string | number | undefined;
    right?: string | number | undefined;
    bottom?: string | number | undefined;
    left?: string | number | undefined;
    horizontal?: string | number | undefined;
    vertical?: string | number | undefined;
    all?: string | number | undefined;
}

export const margins = (styles: IMargins): NestedCSSProperties => {
    const marginVals = {} as NestedCSSProperties;

    if (styles.all !== undefined) {
        marginVals.marginTop = unit(styles.all);
        marginVals.marginRight = unit(styles.all);
        marginVals.marginBottom = unit(styles.all);
        marginVals.marginLeft = unit(styles.all);
    }

    if (styles.vertical !== undefined) {
        marginVals.marginTop = unit(styles.vertical);
        marginVals.marginBottom = unit(styles.vertical);
    }

    if (styles.horizontal !== undefined) {
        marginVals.marginLeft = unit(styles.horizontal);
        marginVals.marginRight = unit(styles.horizontal);
    }

    if (styles.top !== undefined) {
        marginVals.marginTop = unit(styles.top);
    }

    if (styles.right !== undefined) {
        marginVals.marginRight = unit(styles.right);
    }

    if (styles.bottom !== undefined) {
        marginVals.marginBottom = unit(styles.bottom);
    }

    if (styles.left !== undefined) {
        marginVals.marginLeft = unit(styles.left);
    }

    return marginVals as NestedCSSProperties;
};
