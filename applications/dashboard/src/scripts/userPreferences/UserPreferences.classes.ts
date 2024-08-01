/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { viewHeight } from "csx";

export default function UserPreferencesClasses() {
    return {
        frameBody: css({
            maxHeight: viewHeight(80),
        }),
        noBorder: css({
            border: "none",
        }),
        tableWrap: css({
            maxWidth: "100%",
            overflow: "auto",
        }),
        table: css({
            border: "1px solid #f4f6fb",
            marginBottom: 16,
            tableLayout: "fixed",
            width: "100%",
            minWidth: 600,
        }),
        headers: css({
            // This code smell brought to you by nested table styles and conflicting sass
            "tr > th": {
                "&:first-of-type": {
                    width: "30%",
                },
                "&:last-of-type": {
                    width: "15%",
                },
            },
        }),
        cell: css({
            "& > span": {
                maxHeight: "none",
            },
        }),
        categoryName: css({
            display: "flex",
            alignItems: "center",

            "& > div.photoWrap": {
                width: 30,
                height: 30,
            },
        }),
        errors: css({
            paddingTop: 10,
            textAlign: "center",
        }),
    };
}
