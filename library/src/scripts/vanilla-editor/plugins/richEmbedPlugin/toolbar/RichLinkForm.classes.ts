/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const richLinkFormClasses = useThemeCache(() => {
    const separator = css({
        margin: "16px -16px 12px",
    });

    const addLinkButton = css({
        display: "block",
        marginLeft: "auto",
    });

    return { separator, addLinkButton };
});
