/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { media } from "@library/styles/styleShim";
import { viewHeight } from "csx";

export default function userManagementClasses() {
    const searchAndActionsContainer = css({
        paddingLeft: 18,
        paddingRight: 18,
        marginTop: 18,
        display: "flex",
    });

    const searchAndCountContainer = css({
        width: 480,
    });

    const actionsContainer = css({
        display: "flex",
        marginLeft: "auto",
    });

    const pager = css({
        maxWidth: 500,
        padding: 0,
        borderTop: "none",
        alignSelf: "flex-end",
    });

    const countUsers = css({
        fontSize: 12,
        fontStyle: "italic",
        color: "#949aa2",
        marginTop: 4,
    });

    const tableContainer = css({
        overflow: "auto",
        paddingBottom: 20,
    });

    const userRow = css({
        marginLeft: 18,
        height: 50,
        borderBottom: "1px solid #dddddd",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        "& > span": {
            marginRight: 40,
            width: 140,
        },

        "& > span:first-of-type": {
            display: "flex",
            flexDirection: "column",
            justifyContent: "center",
            "& > span": {
                fontSize: 11,
                color: "#949aa2",
                display: "inline-flex",
                lineHeight: 1,
            },
        },
    });

    const actionButtons = css({
        display: "flex",
        gap: 8,
    });

    const deleteIcon = css({
        height: 24,
        minWidth: 26,
    });

    return {
        searchAndActionsContainer,
        searchAndCountContainer,
        actionsContainer,
        pager,
        countUsers,
        tableContainer,
        userRow,
        actionButtons,
        deleteIcon,
    };
}
