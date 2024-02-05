/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";

const ViewInContextClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        display: "block",
        ...Mixins.margin({ top: globalVars.gutter.size }),
    });

    const link = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "bold"),
        }),
        fontStyle: "italic",
        ...Mixins.clickable.itemState(),
    });

    return { root, link };
});

export default ViewInContextClasses;
