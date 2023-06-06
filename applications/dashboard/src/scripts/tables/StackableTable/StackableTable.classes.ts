/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { media } from "@library/styles/styleShim";

export default function stackableTableClasses() {
    const tableContainer = css({
        overflow: "auto",
        paddingBottom: 20,
        paddingTop: 20,
    });

    const table = css({});

    const tableHeader = css({
        background: "#FBFCFF",
        borderTop: "1px solid #D8D8D8",
        "& tr, & tr th": {
            borderBottom: "none",
        },
        "& th:not(:first-of-type)": {
            borderLeft: "1px solid #D8D8D8",
        },
    });

    const tableMobileQuery = media(
        { maxWidth: 375 },
        {
            ...{
                "& td:not(:first-child)": {
                    minWidth: 100,
                },
            },
        },
    );

    const tableRow = css({
        borderBottom: "1px solid #dddddd",

        "& th, & td": {
            paddingLeft: 12,
            paddingRight: 12,
            width: 140,
            maxHeight: "none",
            paddingTop: 8,
            paddingBottom: 8,
        },
        "& td:not(:last-child)": {
            textAlign: "left",
            "& span": {
                justifyContent: "left",
            },
        },
        "& td:first-child, & th:first-child": {
            paddingLeft: 18,
            minWidth: 240,
            display: "flex",
            alignItems: "flex-start",
        },
        "& td:first-child": {
            flexDirection: "column",
            "& > span": {
                fontSize: 11,
                color: "#949aa2",
                display: "inline-flex",
                lineHeight: 1,
            },
        },
        "& td:last-child": {
            paddingRight: 18,
            justifyContent: "flex-end",
        },
        "& td:not(:first-child)": {
            minWidth: 140,
        },
        ...tableMobileQuery,
    });

    const sortableHead = css({
        display: "flex",
        alignItems: "center",
        cursor: "pointer",
        color: "#0291db",
        width: "100%",
        height: "100%",
        "&:hover, &:focus, &.focus-visible, &:active": {
            color: "#015f8f",
        },
        "& span": {
            height: 24,
        },
    });

    const wrappedContent = css({
        display: "flex",
        flexDirection: "column",
        fontSize: 12,
    });

    return {
        tableContainer,
        table,
        tableHeader,
        sortableHead,
        tableRow,
        wrappedContent,
    };
}
