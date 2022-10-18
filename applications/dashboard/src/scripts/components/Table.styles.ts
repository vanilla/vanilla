/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { percent } from "csx";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { tableVariables } from "./Table.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const tableClasses = () => {
    const globalVars = globalVariables();
    const vars = tableVariables();

    const table = css({
        width: percent(100),
    });

    const head = css({
        ...Mixins.padding({
            // displayed as table-row, this rectifies box-sizing calculations for pagination
            bottom: vars.head.padding.bottom - 1,
            top: vars.head.padding.top - 2,
            horizontal: vars.head.padding.horizontal * 2,
        }),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
            transform: "uppercase",
        }),
        whiteSpace: "nowrap",
        borderBottom: singleBorder({
            color: vars.separator.fg,
            width: vars.separator.width,
        }),
    });

    const leftAlignHead = css({
        paddingLeft: 0,
        textAlign: "left",
        "& span": {
            justifyContent: "left",
        },
    });

    const row = css({
        borderBottom: singleBorder({
            color: ColorsUtils.colorOut(globalVars.border.color.fade(0.6)),
        }),
        boxSizing: "border-box",
    });

    const cell = css({
        padding: 0,
    });

    const cellContentWrap = css({
        display: "flex",
        width: "100%",
        height: "100%",
        minHeight: 48,
        maxHeight: 48,
        alignItems: "center",
        justifyContent: "center",
        ...Mixins.padding({ vertical: 4 }),
    });

    const cellCompact = css({
        "&&": {
            ...Mixins.padding({
                bottom: 8,
                top: 16,
            }),
        },
    });

    const basicColumn = css({
        minWidth: styleUnit(vars.columns.basic.minWidth),
        verticalAlign: "middle",
    });

    const pagination = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "flex-end",
        alignItems: "center",
        "> div": {
            marginRight: globalVars.gutter.size,
        },
        "> nav": {
            display: "flex",
            "& a": {
                width: 24,
                height: 24,
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                color: "inherit",

                "&.disabled": {
                    opacity: 0.5,
                    cursor: "default",
                },
            },

            "& svg": {
                height: "auto",
                width: 18,
            },
        },
    });

    const layoutWrap = css({
        width: "100%",
        height: "100%",
        display: "flex",
        flexDirection: "column",
    });
    const tableWrap = css({
        overflowY: "hidden",
        overflowX: "auto",
        height: "100%",
    });
    const paginationWrap = css({
        minHeight: 32,
        display: "flex",
        alignItems: "flex-end",
        justifyContent: "flex-end",
    });

    const leftAlign = css({
        textAlign: "left",
        "& span": {
            justifyContent: "left",
        },
    });

    const scrollThumb = css({
        width: 4,
        borderRadius: 4,
        backgroundColor: "rgba(85, 90, 98, 0.8)",
    });

    return {
        table,
        head,
        row,
        cell,
        cellContentWrap,
        cellCompact,
        basicColumn,
        pagination,
        layoutWrap,
        tableWrap,
        paginationWrap,
        leftAlign,
        scrollThumb,
        leftAlignHead,
    };
};
