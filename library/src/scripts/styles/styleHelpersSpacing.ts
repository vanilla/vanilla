/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { styleUnit } from "@library/styles/styleUnit";
import { calc } from "csx";

/**
 * Extend an item container outward to compensate for item paddings.
 * Usually used in a grid.
 *
 * If you use this function, make sure the parent is set to 100% width with over flow hidden.
 *
 * @param itemPaddingX A single unit of padding of a horizontal side of the item.
 */
export function extendItemContainer(itemPaddingX: number): CSSObject {
    return {
        width: calc(`100% + ${styleUnit(itemPaddingX * 2)}`),
        marginLeft: styleUnit(-itemPaddingX),
    };
}
