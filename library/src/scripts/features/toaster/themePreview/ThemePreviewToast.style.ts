/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { buttonGlobalVariables } from "@library/forms/Button.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { flexHelper } from "@library/styles/styleHelpersPositioning";
import { styleUnit } from "@library/styles/styleUnit";

export const themePreviewToastClasses = () => {
    const globalVars = globalVariables();

    const toastActions = css({
        minHeight: buttonGlobalVariables().sizing.minHeight,
        ...flexHelper().middleLeft(),

        "& button": {
            margin: styleUnit("3px"),
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
            }),
        },
    });

    return {
        toastActions,
    };
};
