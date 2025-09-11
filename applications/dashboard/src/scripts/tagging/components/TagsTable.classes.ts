/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export default useThemeCache(() => {
    return {
        scopeCellWrapper: css({
            display: "flex",
            gap: 4,
        }),
    };
});
