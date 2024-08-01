/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const truncatedTextClasses = useThemeCache(({ lineClamp = "none" }: { lineClamp: "none" | number }) => {
    const truncated = css({
        // Gecko also uses the "-webkit-" prefix: https://bugzilla.mozilla.org/show_bug.cgi?id=866102
        display: "-webkit-box",
        WebkitLineClamp: lineClamp,
        WebkitBoxOrient: "vertical",
        overflow: "hidden",
        textOverflow: "ellipsis",

        img: {
            display: "none",
        },
    });

    return { truncated };
});
