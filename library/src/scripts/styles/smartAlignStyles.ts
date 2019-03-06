/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { percent } from "csx";

export function smartAlignClasses(theme?: object) {
    const debug = debugHelper("smartAlign");

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: percent(100),
        ...debug.name(),
    });

    const inner = style({
        textAlign: "left",
    });

    return { root, inner };
}
