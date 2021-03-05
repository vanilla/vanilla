/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const actsAsCheckboxClasses = useThemeCache(() => {
    const label = css({
        ...Mixins.margin({
            all: 0,
        }),
        cursor: "pointer",
        lineHeight: 0,
    });

    const checkbox = css({
        ...Mixins.absolute.srOnly(),
    });

    return {
        label,
        checkbox,
    };
});
