/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { metaLinkItemStyle } from "@library/metas/Metas.styles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const reportListItemClasses = () => {
    const container = css({
        display: "flex",
        flexDirection: "column",
        padding: "16px 16px 8px",
        // From mockup
        borderRadius: 6,
        ...shadowHelper().embed(),
    });
    const header = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        marginBottom: 8,

        "& h3": {
            margin: 0,
        },
    });

    const reporterProfile = css({
        maxHeight: "unset",
        "& a": {
            display: "flex",
            alignItems: "center",
            gap: 8,
            ...metaLinkItemStyle(),
        },
    });

    const titleGroup = css({
        display: "flex",
        alignItems: "center",
        gap: 6,

        "& > a": {
            textDecoration: "none",
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.darkText),
            display: "flex",
            alignItems: "center",
        },
    });
    const actions = css({
        display: "flex",
        alignItems: "center",
        gap: 8,
    });

    const adminStyleOverrides = css({
        "& h3": {
            margin: 0,
        },
        "& p": {
            marginBottom: 0,
        },
    });

    const reportItem = css({
        marginBottom: 0,
        "& h3": {
            margin: 0,
        },
    });
    const recordItem = css({
        borderLeft: "6px solid rgb(216, 217, 219)",
        paddingLeft: 16,
        "& h3": {
            margin: 0,
        },
    });
    const recordOverrides = css({
        // Quick n Dirty
        marginTop: -4,
    });
    const reportSummaryContainer = css({
        borderTop: "1px solid #e7e8e9",
        marginTop: 16,
        marginLeft: -16,
        marginRight: -16,
    });

    const reportSummary = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "end",
        alignItems: "center",
        gap: 16,
        marginTop: 8,
        marginRight: 16,
    });

    const gradientOverride = css({
        height: "32px!important",
    });

    return {
        container,
        header,
        titleGroup,
        actions,
        reportItem,
        recordItem,
        recordOverrides,
        reportSummaryContainer,
        reportSummary,
        reporterProfile,
        gradientOverride,
        adminStyleOverrides,
    };
};
