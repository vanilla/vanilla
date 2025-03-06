/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const convertPostFormClasses = () => {
    const globalVars = globalVariables();

    const warningContainer = css({
        marginTop: "1em",
    });

    const errorContainer = css({
        marginTop: "1em",
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
    });

    return { warningContainer, errorContainer };
};
