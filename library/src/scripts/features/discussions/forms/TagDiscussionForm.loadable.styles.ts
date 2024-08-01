/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const tagDiscussionFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    // Using !important here to override the 'auto' overflow style
    // to allow suggestions to be rendered outside of the modal frame
    const modalSuggestionOverride = css({
        "&&&": {
            overflowY: "visible",
        },
    });
    const error = css({
        fontSize: globalVars.fonts.size.small,
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        marginTop: globalVars.fonts.size.extraSmall,
    });

    return {
        error,
        modalSuggestionOverride,
    };
});
