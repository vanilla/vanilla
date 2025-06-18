/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { BorderStylesDefault } from "@udecode/plate-table";
import { singleBorder } from "@library/styles/styleHelpers";
import { userContentVariables } from "@library/content/UserContent.variables";

/**
 * These classes are mainly a mimication of the classes from the `@udecode/plate-table-ui` package
 * We'll override our table styles from UserContent here.
 */
export const richTableElementsClasses = useThemeCache((cellBorders?: BorderStylesDefault | undefined) => {
    const vars = userContentVariables();

    const MIN_COL_WIDTH = 80;

    const tableWrapper = css({
        position: "relative",
        overflowX: "auto",
    });

    const table = css({
        display: "table",
        tableLayout: "fixed",
        height: 1,
        marginTop: 1,
        marginBottom: 16,
        marginRight: 0,
        borderCollapse: "collapse",
        "& tbody": {
            minWidth: "100%",
        },
        // usercontent styles overrides
        "&&": {
            border: "none",
        },
        "&& th": {
            whiteSpace: "initial",
        },
    });

    const row = css({});

    const cell = css({
        borderStyle: "none",
        overflow: "visible",
        padding: 0,
        position: "relative",
        textAlign: "left",
        ...{
            "&::before": {
                content: "''",
                boxSizing: "border-box",
                position: "absolute",
                userSelect: "none",
                height: "100%",
                width: "100%",
                ...(cellBorders?.bottom && {
                    borderBottom: singleBorder(vars.tables.horizontalBorders.borders),
                }),
                ...(cellBorders?.right && {
                    borderRight: singleBorder(vars.tables.horizontalBorders.borders),
                }),
                ...(cellBorders?.left && {
                    borderLeft: singleBorder(vars.tables.horizontalBorders.borders),
                }),
                ...(cellBorders?.top && {
                    borderTop: singleBorder(vars.tables.horizontalBorders.borders),
                }),
            },
        },

        // usercontent styles overrides
        "&&": {
            padding: 0,
            border: "none",
            minWidth: MIN_COL_WIDTH,
            verticalAlign: "middle",
        },
    });

    const cellContent = css({
        position: "relative",
        boxSizing: "border-box",
        height: "100%",
        ...Mixins.padding({
            vertical: 6,
            horizontal: 12,
        }),
        zIndex: 1, // to allow using our toolbar on a table text
    });

    const cellResizableWrapper = css({
        height: "100%",
        width: "100%",
        position: "absolute",
        top: 0,
        userSelect: "none",
        " > div": { zIndex: `2 !important` },
    });

    return { tableWrapper, table, row, cell, cellContent, cellResizableWrapper };
});
