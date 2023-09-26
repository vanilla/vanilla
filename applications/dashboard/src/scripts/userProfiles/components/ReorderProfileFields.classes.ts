/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const ReorderProfileFieldsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    return {
        row: css({
            display: "flex",
            gap: 16,
            width: "100%",
        }),
        labelContainer: css({ width: "50%", display: "flex" }),
        statusLight: css({
            ...Mixins.margin({
                right: 8,
            }),
        }),
        enabledContainer: css({ width: "40%", display: "flex" }),
    };
});
