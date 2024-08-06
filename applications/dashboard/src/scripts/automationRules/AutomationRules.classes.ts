/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { useThemeCache } from "@library/styles/themeCache";

export const automationRulesClasses = useThemeCache((isEscalationRulesList?: boolean) => {
    const headerContainer = css({
        position: "sticky",
        top: titleBarVariables().fullHeight,
        backgroundColor: "#ffffff",
        zIndex: 1,
        marginLeft: -18,
        marginRight: -18,
        "& .header-block": {
            marginLeft: 0,
            marginRight: 0,
        },
        borderBottom: "1px solid #D8D8D8",
    });

    const searchAndFilterContainer = css({
        display: "flex",
        padding: 16,
        ...(isEscalationRulesList && { paddingLeft: 0 }),
        "&& .inputText.withButton": {
            height: 36,
        },
    });

    const table = css({
        "& th, tr": {
            fontSize: 13,
        },
        "&& button": {
            fontSize: 13,
        },
        ...(isEscalationRulesList && {
            "&& th:first-child": {
                paddingLeft: 18,
            },
            "&& td:first-child": {
                paddingLeft: 12,
            },
        }),
    });

    const tableCell = css({
        "& > span": {
            maxHeight: "none",
        },
    });

    const tableDateCell = css({
        minWidth: 80,
    });

    const tableHeader = css({
        background: "#FBFCFF",
        "& th:not(:first-of-type)": {
            borderLeft: "1px solid #D8D8D8",
        },
    });

    const triggerAndActionCell = css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        "& > div": {
            height: "50%",
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
        },
    });

    const triggerAndActionLabels = css({
        fontWeight: 600,
        fontSize: 12,
    });

    const scrollTable = css({
        overflow: "auto",
        paddingBottom: 20,
    });

    const filterForm = css({
        "& .form-group": {
            borderBottom: "none",
            paddingTop: 0,
        },
    });

    const disabled = css({
        opacity: 0.5,
        pointerEvents: "none",
        "&:hover, &:focus, &.focus-visible, &:active": {
            background: "none",
        },
        "&.btn-primary": {
            color: "#0291db",
            borderColor: "#0291db",
        },
    });

    const sectionHeader = css({
        paddingLeft: 16,
        height: 32,
        background: "#FBFCFF",
        display: "flex",
        alignItems: "center",
        borderTop: "1px solid #e7e8e9",
        borderBottom: "1px solid #e7e8e9",
        textTransform: "uppercase",
    });

    const summaryTitle = css({
        fontWeight: 600,
        fontSize: 12,
    });

    const runningStatusWrapper = css({
        textTransform: "none",
        marginLeft: 8,
    });

    const runningStatusIcon = css({
        marginLeft: 8,
        marginRight: 4,
    });

    const summaryValue = css({
        fontWeight: 600,
        marginTop: 2,
        marginBottom: 2,
    });

    const noBorderTop = css({
        borderTop: "none",
    });

    const padded = (verticalOnly?: boolean) => {
        return css({
            paddingTop: 16,
            paddingBottom: 16,
            ...(!verticalOnly && { paddingLeft: 16, paddingRight: 16 }),
        });
    };

    const verticalGap = css({ marginTop: 8, marginBottom: 8 });

    const bottomGap = (gap: number = 16) => css({ marginBottom: gap });

    const leftGap = (gap: number = 18) => css({ marginLeft: gap });

    const rightGap = (gap: number = 8) => css({ marginRight: gap });

    const spaceBetween = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
    });

    const italic = css({
        fontStyle: "italic",
    });

    const bold = css({
        fontWeight: 600,
    });

    const normalFontWeight = css({
        fontWeight: 400,
    });

    const noOverflow = css({ overflow: "unset" });

    const noWrap = css({ whiteSpace: "nowrap" });

    const noPadding = css({ padding: 0 });

    const smallFont = css({
        fontSize: 12,
    });

    const leftAlign = css({
        justifyContent: "flex-start",
    });

    const addEditForm = css({
        "& .form-group.formGroup-checkBox .input-wrap": {
            "@media (min-width: 544px)": {
                flex: "0 0 41.67%",
            },
            "& p": {
                marginLeft: 25,
            },
        },
    });

    const addEditHeader = css({
        width: "100%",
        margin: "0 auto",
        display: "flex",
        alignItems: "center",
    });

    const addEditHeaderItem = css({
        flex: 1,
        "&:nth-child(2)": {
            justifyContent: "center",
        },
        "&:last-child": {
            justifyContent: "flex-end",
        },
    });

    const flexContainer = (withGap?: boolean) => {
        return css({
            display: "flex",
            alignItems: "center",
            ...(withGap && { gap: 8 }),
        });
    };

    const previewPager = css({
        padding: 0,
        paddingTop: 16,
        marginBottom: -16,
    });

    const previewUserItem = css({
        color: "#555a62",
        "&:hover, &:focus, &.focus-visible, &:active": {
            textDecoration: "none",
        },
    });

    const previewDiscussionItem = css({
        paddingBottom: 8,
        paddingLeft: 8,
        "&:first-child": {
            marginTop: 24,
            paddingTop: 8,
            borderTop: "1px solid #dddee0",
        },
    });

    const previewDiscussionMeta = css({
        color: "#767676",
        fontSize: 12,
    });

    const previewDiscussionBorder = css({
        borderBottom: "1px solid #dddee0",
    });

    const addEditLoader = css({
        display: "flex",
        justifyContent: "space-between",
        "& > span": {
            width: "27%",
            height: 24,
            marginTop: 16,
        },
        "& > span:last-of-Type": {
            width: "70%",
        },
        ...(isEscalationRulesList && { paddingLeft: 16, paddingRight: 16 }),
    });

    const historyLoader = css({
        "& td > span": {
            width: 100,
            height: 16,
            marginTop: 16,
        },
        "& td:first-of-type > span": {
            width: 300,
        },
    });

    const previewLoader = css({
        marginTop: 16,
        paddingTop: 16,
        paddingBottom: 16,
        "& > div": {
            marginBottom: 16,
            display: "flex",
            alignItems: "center",
            "& > span:first-of-type": { width: 25, height: 25, marginRight: 10, borderRadius: "50%" },
            "& > span:last-of-type": { width: "95%", height: 25 },
        },
    });

    const escalationRuleAddEditForm = css({
        "& li": {
            marginLeft: 0,
            marginRight: 0,
            width: "auto",
        },
    });

    const escalationRuleAddEditTitleBar = css({
        "& > div": {
            justifyContent: "normal",
        },
    });

    const escalationRuleAddEditTitleBarActionsWrapper = css({
        width: "100%",
        justifyContent: "space-between",
    });

    return {
        headerContainer,
        searchAndFilterContainer,
        table,
        tableHeader,
        tableCell,
        tableDateCell,
        scrollTable,
        triggerAndActionCell,
        triggerAndActionLabels,
        filterForm,
        disabled,
        sectionHeader,
        runningStatusWrapper,
        runningStatusIcon,
        summaryTitle,
        summaryValue,
        noBorderTop,
        addEditForm,
        addEditHeader,
        addEditHeaderItem,
        flexContainer,
        italic,
        bold,
        smallFont,
        leftAlign,
        normalFontWeight,
        padded,
        noPadding,
        spaceBetween,
        leftGap,
        rightGap,
        verticalGap,
        bottomGap,
        noOverflow,
        noWrap,
        previewPager,
        previewUserItem,
        previewDiscussionItem,
        previewDiscussionMeta,
        previewDiscussionBorder,
        addEditLoader,
        historyLoader,
        previewLoader,
        escalationRuleAddEditTitleBar,
        escalationRuleAddEditTitleBarActionsWrapper,
        escalationRuleAddEditForm,
    };
});
