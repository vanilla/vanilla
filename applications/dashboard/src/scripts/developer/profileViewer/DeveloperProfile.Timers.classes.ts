/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { activeSelector } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const developerProfileTimersClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const span = css({
        padding: 24,
        minWidth: 90,
        border: singleBorder({ radius: 6 }),
        borderRadius: 6,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",

        [activeSelector("&")]: {
            borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
    });

    return { span };
});
