/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const developerProfileClasses = useThemeCache(() => {
    const pageContent = css({
        paddingTop: 0,
        paddingBottom: 0,
    });

    const listContent = css({
        paddingTop: 16,
        paddingBottom: 16,
        paddingLeft: 28,
        paddingRight: 28,
    });

    const listHeader = css({
        display: "flex",
        alignItems: "center",
        gap: 12,
    });

    const spacer = css({
        flex: 1,
    });

    const sortDropdown = css({
        display: "inline-flex",
        alignItems: "baseline",
        gap: 4,
        lineHeight: "40px",
    });

    const backlink = css({
        position: "absolute",
        left: 4,
        top: "50%",
        fontSize: 24,
        transform: "translateY(-50%)",
    });

    return { pageContent, listContent, listHeader, spacer, sortDropdown, backlink };
});
