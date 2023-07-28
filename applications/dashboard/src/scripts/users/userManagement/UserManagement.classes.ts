/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { media } from "@library/styles/styleShim";
import { useThemeCache } from "@library/styles/themeCache";

const userManagementClasses = useThemeCache(() => {
    const headerActionsMobileQuery = media(
        { maxWidth: 610 },
        {
            ...{
                marginLeft: 0,
            },
        },
    );
    const actionButtonsMobileQuery = media(
        { maxWidth: 806 },
        {
            ...{
                marginLeft: 0,
                minWidth: 36,
            },
        },
    );
    const headerContainer = css({
        position: "sticky",
        top: titleBarVariables().fullHeight,
        backgroundColor: "#ffffff",
        zIndex: 1,
        marginLeft: -18,
        marginRight: -18,
        paddingBottom: 14,
        "& .header-block": {
            marginLeft: 0,
            marginRight: 0,
        },
        borderBottom: "1px solid #D8D8D8",
    });

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

    const headerActionsContainer = css({
        alignSelf: "start",
        display: "flex",
    });

    const tableActionButtons = css({
        display: "flex",
        gap: 8,
    });

    const actionButton = css({
        color: "#0291db",
        ...actionButtonsMobileQuery,
    });

    const filterButtonsContainer = css({
        marginLeft: 16,
        display: "flex",
        alignItems: "center",
        ...headerActionsMobileQuery,
    });

    const clearFilterButton = css({
        marginRight: 16,
    });

    const filterModal = css({
        "& fieldset": {
            padding: 0,
        },

        "& .inputText.inputText": {
            fontSize: 16,
            fontWeight: 400,
            color: "#555a62",
            padding: "5px 11.3333px",
            width: "100%",
            minHeight: 36,
            border: "1px solid #bec2ce",
            borderRadius: 4,
            outline: 0,
        },
        "& .suggestedTextInput-option": {
            padding: 8,
            fontWeight: 400,
        },
        "& .DayPicker": {
            fontSize: 16,
            "& h3, & button": {
                fontSize: 16,
            },
        },
    });

    const columnsConfigurationButton = css({
        paddingTop: 4,
        paddingLeft: 2,
        color: "#0291db",
        ...actionButtonsMobileQuery,
    });

    const columnsConfigurationModal = css({
        "& .isItemHidden  > div": {
            color: "#555a6280",
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

    const table = css({
        fontSize: 13,
        "& button": {
            fontSize: 13,
        },
        "& th": {
            fontSize: 13,
        },
        "& td:first-child, & th:first-child": {
            paddingLeft: 18,
        },
        "& thead": {
            borderTop: "none",
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

    const exportToast = css({
        display: "flex",
        gap: 16,
    });

    const exportToastContent = css({
        flex: 1,
    });

    return {
        headerContainer,
        searchAndActionsContainer,
        searchAndCountContainer,
        headerActionsContainer,
        tableActionButtons,
        actionButton,
        pagerContainer,
        pager,
        countUsers,
        table,
        multipleValuesCellContent,
        roleAsButton,
        smallLineHeight,
        columnsConfigurationButton,
        columnsConfigurationModal,
        filterModal,
        filterButtonsContainer,
        clearFilterButton,
        wrappedColumnLabel,
        userName,
        userNameAndEmail,
        userPhoto,
        deleteIcon,
        spoofIcon,
        dropdownContainer,
        treeTitle,
        treeItem,
        treeItemName,
        modalFooter,
        alignRight,
        bottomSpace,
        exportToast,
        exportToastContent,
    };
});

export default userManagementClasses;
