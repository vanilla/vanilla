/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";

export const smartAlignClasses = useThemeCache(() => {
    const style = styleFactory("smartAlign");

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: percent(100),
    });

    const inner = style("inner", {
        textAlign: "left",
    });

    return {
        root,
        inner,
    };
});
