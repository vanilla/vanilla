/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const bulkActionsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const bulkActionsToast = css({
        width: "fit-content",
    });

    const bulkActionsText = css({
        display: "block",
        marginBottom: 12,
        fontSize: globalVars.fonts.size.medium,
        color: "#838691",
    });

    const bulkActionsButtons = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "start",

        "& > *": {
            ...Mixins.margin({
                horizontal: 6,
            }),
            "&:first-child": {
                marginLeft: 0,
            },
        },
    });
    return { bulkActionsButtons, bulkActionsText, bulkActionsToast };
});
