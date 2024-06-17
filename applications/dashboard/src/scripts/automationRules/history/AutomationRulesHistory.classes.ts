/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { defaultTransition } from "@library/styles/styleHelpersAnimation";

export const automationRulesHistoryClasses = useThemeCache(() => {
    const table = css({
        width: "100%",

        "& th, tr": {
            fontSize: 13,
        },
        "&& button": {
            fontSize: 13,
        },
        "& th, & td": {
            paddingLeft: 12,
            paddingRight: 12,
        },
        "& td": {
            minHeight: 48,

            "&:last-child": {
                paddingRight: 18,
            },
            "&:not(:last-child)": {
                textAlign: "start",
                "& span": {
                    justifyContent: "left",
                },
            },
        },

        "& td:first-child, & th:first-child": {
            paddingLeft: 18,
        },
    });

    const tableHeader = css({
        background: "#FBFCFF",
        borderBottom: "1px solid #D8D8D8",
        "& tr, & tr th": {
            borderBottom: "none",
        },
        "& th:not(:first-of-type)": {
            borderLeft: "1px solid #D8D8D8",
        },
    });

    const tableFirstHeader = css({ minWidth: 180 });

    const tableCellWrapper = css({
        display: "flex",
        alignItems: "flex-start",
        flexDirection: "column",
        overflow: "hidden",
    });

    const tableDateCell = css({
        minWidth: 142,
    });

    const accordion = css({
        cursor: "pointer",
    });

    const accordionButton = css({
        minHeight: 40,
        width: "100%",
        minWidth: 200,
        textAlign: "start",
        paddingTop: 8,
        paddingBottom: 8,
    });

    const accordionChevron = css({
        ...defaultTransition("transform"),
        marginLeft: -4,
    });

    const filterConatainer = css({
        width: "80%",
        flexWrap: "wrap",
    });

    const dateIcon = css({
        color: "#026496",
        marginTop: 2,
    });

    return {
        tableCellWrapper,
        tableHeader,
        tableFirstHeader,
        table,
        tableDateCell,
        accordion,
        accordionButton,
        accordionChevron,
        filterConatainer,
        dateIcon,
    };
});
