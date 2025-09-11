/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { media } from "@library/styles/styleShim";
import { useThemeCache } from "@library/styles/themeCache";

export default useThemeCache(() => {
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

    const tableActionButtons = css({
        display: "flex",
        gap: 8,
    });

    const alignRight = css({
        textAlign: "right",
        justifyContent: "flex-end",
    });

    const actionButtonsMobileQuery = media(
        { maxWidth: 806 },
        {
            ...{
                marginLeft: 0,
                minWidth: 36,
            },
        },
    );

    const actionButton = css({
        color: "#0291db",
        ...actionButtonsMobileQuery,
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

    const headerActionsMobileQuery = media(
        { maxWidth: 610 },
        {
            ...{
                marginLeft: 0,
            },
        },
    );

    const filterButtonsContainer = css({
        marginLeft: 16,
        display: "flex",
        alignItems: "center",
        ...headerActionsMobileQuery,
    });

    const clearFilterButton = css({
        marginRight: 16,
    });

    return {
        headerContainer,
        searchAndActionsContainer,
        searchAndCountContainer,
        tableActionButtons,
        alignRight,
        actionButton,
        pagerContainer,
        pager,
        table,
        filterButtonsContainer,
        clearFilterButton,
    };
});
