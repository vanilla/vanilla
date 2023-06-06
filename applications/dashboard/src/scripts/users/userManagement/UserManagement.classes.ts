/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

export default function userManagementClasses() {
    const searchAndActionsContainer = css({
        paddingLeft: 18,
        paddingRight: 18,
        marginTop: 18,
        display: "flex",
        flexWrap: "wrap",
        justifyContent: "space-between",
        alignItems: "center",
    });

    const searchAndCountContainer = css({
        width: 480,
    });

    const actionsContainer = css({
        alignSelf: "start",
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

    const wrappedColumnLabel = css({
        fontWeight: 700,
    });

    const userName = css({
        display: "flex",
        alignItems: "center",
        "&& img": {
            display: "initial",
        },
    });

    const userNameAndEmail = css({
        display: "flex",
        flexDirection: "column",
        "& > span": {
            fontSize: 12,
            color: "#949aa2",
            display: "inline-flex",
            lineHeight: 1,
        },
    });

    const userPhoto = css({
        marginRight: 8,
        width: 30,
        height: 30,
        backgroundSize: "cover",
        borderRadius: "50%",
        border: "1px solid #aaadb14d",
    });

    const roleAsButton = css({
        padding: 0,
        lineHeight: 1,
        "&:hover, &:focus, &.focus-visible, &:active": {
            textDecoration: "none",
        },
        "&&": { marginLeft: 0 },
    });

    const actionButtons = css({
        display: "flex",
        gap: 8,
    });

    const deleteIcon = css({
        height: 24,
        minWidth: 26,
    });

    const spoofIcon = css({
        height: 24,
        minWidth: 24,
    });

    return {
        searchAndActionsContainer,
        searchAndCountContainer,
        actionsContainer,
        pager,
        countUsers,
        roleAsButton,
        wrappedColumnLabel,
        userName,
        userNameAndEmail,
        userPhoto,
        actionButtons,
        deleteIcon,
        spoofIcon,
    };
}
