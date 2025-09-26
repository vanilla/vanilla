/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const draftsClasses = useThemeCache(() => {
    const draftListItemTitle = css({
        fontWeight: globalVariables().fonts.weights.semiBold,
        fontSize: globalVariables().fonts.size.large,
    });

    const draftListLastItemInGroup = css({
        "&:after": {
            borderBottom: "none",
        },
    });

    const failedSchedulesDate = css({
        color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
    });

    const draftItemBreadCrumbs = css({ maxHeight: "initial" });

    const verticalGap = css({ marginTop: 8, marginBottom: 8 });

    const filterFormFooter = css({ "&&": { marginTop: 16, border: "none" } });

    const onlyDraftsHeader = css({
        marginBottom: 16,
    });

    const scheduleModalErrorMessage = css({
        "&&": {
            "& > div": {
                flexDirection: "column",
                "& a": {
                    marginRight: "auto",
                    marginLeft: 0,
                    paddingLeft: 0,
                },
            },
        },
    });

    return {
        verticalGap,
        filterFormFooter,
        draftListItemTitle,
        draftListLastItemInGroup,
        failedSchedulesDate,
        draftItemBreadCrumbs,
        onlyDraftsHeader,
        scheduleModalErrorMessage,
    };
});
