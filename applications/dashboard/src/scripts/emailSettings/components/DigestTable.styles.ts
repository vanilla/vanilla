/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export default useThemeCache(() => {
    const table = css({
        "& > thead": {
            display: "block",
            height: 0,
            overflow: "hidden",
        },

        "td:last-child > span": {
            justifyContent: "flex-end",
        },

        "tr:last-child": {
            border: 0,
        },
    });

    return {
        table,
    };
});
