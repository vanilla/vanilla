/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const dashboardFormGroupClasses = useThemeCache(() => {
    const vertical = css({
        // In development builds the admin-new stylesheet is added after emotion.
        // We could probably fix that but it would be a real pain and likely cause other styling issues.
        "&&": {
            display: "block",
        },

        "& .label-wrap, &.label-wrap-wide": {
            marginBottom: 8,
        },
    });

    return { vertical };
});
