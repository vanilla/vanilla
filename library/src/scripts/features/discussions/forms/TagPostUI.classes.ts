/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const tagPostUIClasses = useThemeCache(() => {
    const layout = css({
        padding: "16px 0 0",
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        gap: 8,
    });
    const title = css({
        fontSize: 16,
        fontWeight: 600,
        margin: "16px 0 0",
    });
    const token = css({
        maxWidth: "100%",
        "& > span": {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            gap: 4,
        },
    });
    const button = css({
        padding: 0,
        border: "none",
        cursor: "pointer",
    });
    const icon = css({
        transform: "translateY(2px)",
    });
    return { layout, title, token, button, icon };
});
