/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const reportGroupClasses = () => {
    const container = css({
        display: "flex",
        flexDirection: "column",
        padding: 16,
        border: "1px solid #e7e8e9",
        // From mockup
        borderRadius: 6,
        boxShadow: "1px 3px 4px 0px rgba(0, 0, 0, 0.22), 0px 0px 16px 0px rgba(0, 0, 0, 0.10)",
    });
    const header = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        marginBottom: 16,
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
        justifyContent: "space-between",
        alignItems: "center",
        gap: 50,
        marginTop: 16,
        paddingLeft: 62, // Icky magic number
        paddingRight: 16,

        "& > div": {
            display: "flex",
            flexDirection: "row",
            gap: 8,
        },
    });

    const reporterBlock = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "center",
        alignContent: "center",
        gap: 8,

        "& a": {
            display: "flex",
            alignContent: "center",
            marginTop: 4,
            "& svg": {
                transform: "scale(.75)",
            },
        },
    });

    return {
        container,
        header,
        titleGroup,
        actions,
        recordOverrides,
        reportSummaryContainer,
        reportSummary,
        reporterBlock,
    };
};
