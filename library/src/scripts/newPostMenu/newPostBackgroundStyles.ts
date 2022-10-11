/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const newPostBackgroundClasses = useThemeCache(() => {
    const container = css({
        height: 0,
        width: 0,
        position: "fixed",
        top: 0,
        left: 0,
        zIndex: 1050, //Our dashboard uses some bootstrap which specifies 1040 for the old modals, so this one will be 1050,
    });

    const overlay = css({
        height: "100vh",
        width: "100vw",
    });

    return {
        container,
        overlay,
    };
});
