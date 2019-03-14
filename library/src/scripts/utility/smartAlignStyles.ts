/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { debugHelper } from "../styles/styleHelpers";
import { useThemeCache } from "../styles/styleUtils";
import { percent } from "csx";
import { style } from "typestyle";

export const smartAlignClasses = useThemeCache(() => {
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
});
