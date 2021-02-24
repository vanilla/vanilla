/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const listItemMediaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaItem = css({
        background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.05)),
    });

    return {
        mediaItem,
    };
});
