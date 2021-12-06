/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";

export const smartAlignClasses = useThemeCache(() => {
    const root = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: percent(100),
    });

    const inner = css({
        textAlign: "left",
    });

    return {
        root,
        inner,
    };
});
