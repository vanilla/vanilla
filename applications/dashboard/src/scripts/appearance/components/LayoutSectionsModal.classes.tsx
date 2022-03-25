/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const layoutSectionsModalClasses = useThemeCache(() => {
    const form = css({
        display: "flex",
        flexDirection: "column",
        minHeight: 0,
    });

    return { form };
});
