/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

export const nestCommentListClasses = () => {
    const item = css({
        "--indent-size": "40px",

        "& .commentItem": {
            display: "block",
            position: "relative",

            marginBlock: "16px",
        },

        "& .commentChildren": {
            "& .commentItem": {
                borderRadius: 12,
                border: "1px solid #ccc",
                padding: "16px",
                marginBlock: "16px",

                "&:before": {
                    content: "''",
                    display: "block",
                    position: "absolute",
                    top: "50%",
                    left: "calc((-1 * var(--indent-size)) - 1px)",
                    width: "var(--indent-size)",
                    height: "var(--indent-size)",
                    borderInlineStart: "1px solid #ccc",
                    borderBlockEnd: "1px solid #ccc",
                    borderRadius: "0% 0% 0% 50%",
                },
            },
        },
    });
    const children = css({
        display: "block",
        paddingInlineStart: "var(--indent-size)",
        position: "relative",
        overflow: "hidden",
        "&:before": {
            content: "''",
            display: "block",
            position: "absolute",
            top: -16,
            left: 0,
            width: 1,
            height: "calc(100% - 16px - 15px)",
            background: "#ccc",
        },
    });
    const hole = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        borderRadius: 12,
        border: "1px solid #ccc",
        position: "relative",
        padding: "16px",
        cursor: "pointer",
        minHeight: 60,
        width: "100%",
        overflow: "visible",
        "& > span:first-of-type": {
            display: "flex",
            alignItems: "center",
            justifyContent: "start",
            gap: "1ch",
        },
        "&:before": {
            content: "''",
            display: "block",
            position: "absolute",
            top: 0,
            left: "calc((-1 * var(--indent-size)) - 1px)",
            width: "var(--indent-size)",
            height: "55%",
            borderInlineStart: "1px solid #ccc",
            borderBlockEnd: "1px solid #ccc",
            borderRadius: "0% 0% 0% 50%",
        },
    });
    return {
        item,
        children,
        hole,
    };
};
