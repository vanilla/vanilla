/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export const editUsernameClasses = () => {
    const labelAndStatusLayout = css({
        display: "flex",
        alignItems: "center",
    });

    const statusLayout = css({
        display: "inline-flex",
        alignItems: "center",
        ...Mixins.padding({
            horizontal: globalVariables().spacer.headingItem / 2,
        }),
    });
    const loadingSpinner = css({
        width: globalVariables().fonts.size.medium,
        height: globalVariables().fonts.size.medium,
        padding: 0,
    });
    return {
        labelAndStatusLayout,
        statusLayout,
        loadingSpinner,
    };
};
