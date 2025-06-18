/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";

export default function UserPreferencesClasses() {
    return {
        frameBody: css({
            height: "100%",
        }),
        tableWrap: css({
            overflow: "auto",
            ...extendItemContainer(16),
        }),
        table: css({
            border: "1px solid #f4f6fb",
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
        preferencesTableOverrides: css({
            '& td[role="cell"], & th[role="columnheader"]': {
                padding: "0!important",
                "& > label": {
                    paddingTop: 4,
                    paddingBottom: 4,
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
    };
}
