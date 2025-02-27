/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export default useThemeCache(() => {
    const form = css({
        "& li": {
            width: "100%",
            margin: "auto",
        },
    });

    return {
        form,
    };
});
