/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { percent, px } from "csx";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export function sticky(): CSSObject {
    return {
        position: ["-webkit-sticky", "sticky"],
    };
}

export function flexHelper() {
    const middle = (wrap = false): CSSObject => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexWrap: wrap ? "wrap" : "nowrap",
        };
    };

    const middleLeft = (wrap = false): CSSObject => {
        return {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: wrap ? "wrap" : "nowrap",
        };
    };

    return { middle, middleLeft };
}

export function fullSizeOfParent(): CSSObject {
    return {
        position: "absolute",
        display: "block",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
    };
}

export const inheritHeightClass = useThemeCache(() => {
    const style = styleFactory("inheritHeight");
    return style({
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
        position: "relative",
    });
});
