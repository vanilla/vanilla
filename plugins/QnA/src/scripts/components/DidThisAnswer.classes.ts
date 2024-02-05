/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";

const DidThisAnswerClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "normal"),
        }),
        fontStyle: "italic",
    });

    const button = css({
        padding: "0",
        ...Mixins.clickable.itemState(),
        ...Mixins.margin({
            left: "0.5ch",
        }),
        fontWeight: globalVars.fonts.weights.bold,
    });

    return { root, button };
});

export default DidThisAnswerClasses;
