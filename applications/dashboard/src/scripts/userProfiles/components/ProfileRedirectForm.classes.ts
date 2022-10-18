/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const ProfileRedirectFormClasses = () => {
    const globalVars = globalVariables();
    return {
        formFooter: css({
            display: "flex",
            justifyContent: "flex-end",
            ...Mixins.padding(
                Variables.spacing({
                    vertical: globalVars.spacer.size,
                }),
            ),
        }),
    };
};
