/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const categoryNotificationPreferencesClasses = useThemeCache(() => {
    const table = css({
        width: "100%",
        borderCollapse: "collapse",
        margin: "10px 0",
        "&, & *": {
            lineHeight: "36px",
        },
    });
    const row = css({
        "& td, & th": {
            padding: "2px 6px",
            borderBottom: "1px solid rgba(0, 0, 0, 0.1)",
            '&[data-type="checkbox"]': {
                width: 50,
                textAlign: "center",
                verticalAlign: "middle",
                "& label, & > div": {
                    maxWidth: 30, // 18 for checkbox + 6 for left & right padding
                    margin: "auto",
                },
            },
        },
        "& th": {
            fontWeight: "bold",
            textAlign: "left",
            '&[data-type="checkbox"]': {
                minWidth: "9ex",
            },
        },
    });

    const loadingRect = css({
        margin: "10px 0",
    });

    return {
        table,
        row,
        loadingRect,
    };
});
