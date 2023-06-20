/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { media } from "@library/styles/styleShim";

export default function userManagementClasses() {
    const headerActionsMobileQuery = media(
        { maxWidth: 560 },
        {
            ...{
                marginLeft: 0,
            },
        },
    );
    const columnsConfigurationButtonMobileQuery = media(
        { maxWidth: 806 },
        {
            ...{
                marginLeft: 0,
                minWidth: 36,
            },
        },
    );
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
        "&& .inputText.withButton": {
            height: 36,
        },
    });

    const actionsContainer = css({
        alignSelf: "start",
        display: "flex",
        "& > div": {
            marginLeft: 16,
            ...headerActionsMobileQuery,
        },
    });

    const pagerContainer = css({
        alignSelf: "start",
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

    const columnsConfigurationButton = css({
        paddingTop: 4,
        paddingLeft: 2,
        color: "#0291db",
        ...columnsConfigurationButtonMobileQuery,
    });

    const columnsConfigurationModal = css({
        "& .isItemHidden  > div": {
            color: "#555a6280",
        },
    });

    const table = css({
        fontSize: 13,
        "& button": {
            fontSize: 13,
        },
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
    const multipleValuesCellContent = css({
        display: "flex",
        flexWrap: "wrap",
        columnGap: 4,
    });

    const roleAsButton = css({
        padding: 0,

        "&:hover, &:focus, &.focus-visible, &:active": {
            textDecoration: "none",
        },
        "&&": { marginLeft: 0 },
    });

    const smallLineHeight = css({
        lineHeight: 1.5,
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

    const dropdownContainer = css({
        border: "none",
        paddingTop: 0,
    });

    const treeTitle = css({
        fontWeight: 600,
        marginBottom: 4,
    });

    const treeItem = css({
        minHeight: 32,
        "& > div:first-of-type": {
            width: 20,
            height: 20,
        },
        "& button": {
            height: 24,
            minWidth: 24,
            "& span": {
                color: "#555a62 !important",
            },
        },
    });

    const treeItemName = css({
        width: "100%",
        minHeight: 20,
        paddingLeft: 4,
    });

    const modalFooter = css({
        justifyContent: "normal",
        "& button:nth-child(2)": { marginLeft: "auto", marginRight: 16 },
    });

    const alignRight = css({
        textAlign: "right",
        justifyContent: "flex-end",
    });

    const bottomSpace = css({
        marginBottom: 6,
    });

    return {
        searchAndActionsContainer,
        searchAndCountContainer,
        actionsContainer,
        pagerContainer,
        pager,
        countUsers,
        table,
        multipleValuesCellContent,
        roleAsButton,
        smallLineHeight,
        columnsConfigurationButton,
        columnsConfigurationModal,
        wrappedColumnLabel,
        userName,
        userNameAndEmail,
        userPhoto,
        actionButtons,
        deleteIcon,
        spoofIcon,
        dropdownContainer,
        treeTitle,
        treeItem,
        treeItemName,
        modalFooter,
        alignRight,
        bottomSpace,
    };
}
