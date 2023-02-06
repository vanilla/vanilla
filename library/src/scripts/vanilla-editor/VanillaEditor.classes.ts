/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const vanillaEditorClasses = useThemeCache(() => {
    const root = css({
        padding: 14,
        "&.focus-visible, &:focus, &:focus-visible": {
            outline: "none",
        },
    });

    const elementToolbarPosition = css({
        position: "absolute",
        zIndex: 10,
        transition: "transform linear 50ms",
        willChange: "transform",
        top: 0,
        left: 0,
    });

    const elementToolbarContents = css({
        position: "absolute",
        top: "100%",
        left: 0,
    });

    return { root, elementToolbarPosition, elementToolbarContents };
});
