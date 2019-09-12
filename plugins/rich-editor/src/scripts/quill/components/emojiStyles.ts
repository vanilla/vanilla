/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "typestyle";
import { em, percent } from "csx";
import { margins } from "@library/styles/styleHelpers";

export const emojiCSS = useThemeCache(() => {
    const globalVars = globalVariables();

    cssRule(".safeEmoji", {
        display: "inline-flex",
        fontFamily: `"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif`,
        verticalAlign: "middle",
        textAlign: "center",
        height: em(1),
        maxWidth: percent(100),
        lineHeight: em(1),
    });

    cssRule(".nativeEmoji", {
        fontFamily: `"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif`,
    });

    cssRule(".fallBackEmoji", {
        display: "inline-block",
        height: em(1),
        width: em(1),
        ...margins({
            vertical: 0,
            right: em(0.05),
            left: em(0.1),
        }),
        verticalAlign: em(-0.1),
        userSelect: "none",
    });

    cssRule(".emojiGroup", {
        opacity: globalVars.states.icon.opacity,
    });

    cssRule(".emojiPicker", {
        position: "relative",
    });
});
