/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { css } from "@emotion/css";

export const editProfileFieldsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const errorMessage = css({
        marginTop: globalVars.spacer.componentInner,
        marginBottom: globalVars.spacer.componentInner,
    });

    const submitButton = css({
        marginTop: globalVars.spacer.pageComponentCompact,
    });

    return {
        errorMessage,
        submitButton,
    };
});
