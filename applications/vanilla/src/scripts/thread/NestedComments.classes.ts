/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const nestCommentListClasses = () => {
    const globalVars = globalVariables();

    const rootCommentItem = css({
        // Reinventing the wheel here because separators between rely on previous siblings
        borderTop: singleBorder({
            color: ColorsUtils.colorOut(globalVars.border.color),
            width: 1,
        }),
        ...Mixins.padding(globalVars.itemList.padding),
    });

    const childCommentItem = css({
        position: "relative",
        border: singleBorder({
            color: ColorsUtils.colorOut(globalVars.border.color),
            width: 1,
        }),
        borderRadius: 8,
        ...Mixins.padding(globalVars.itemList.padding),

        "&:before": {
            content: "''",
            display: "block",
            position: "absolute",
            top: 20,
            left: -21,
            width: "20px",
            height: "20px",
            borderInlineStart: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderBlockEnd: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderEndStartRadius: "50%",
        },
    });

    const childContainer = css({
        display: "flex",
        ...Mixins.padding({
            top: globalVars.itemList.padding.top,
        }),
    });

    const descender = css({
        display: "flex",
        gap: 4,
        justifyContent: "start",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        width: 40,
    });

    const commentChildren = css({
        position: "relative",
        width: "100%",
        "& > div": {
            "&:first-child": {
                ...Mixins.margin({
                    top: 28,
                    bottom: 16,
                }),
            },
            "&:not(:first-child)": {
                ...Mixins.margin({
                    vertical: 16,
                    horizontal: 0,
                }),
            },
        },
    });

    const hole = css({
        position: "relative",
        border: singleBorder({
            color: ColorsUtils.colorOut(globalVars.border.color),
            width: 1,
        }),
        borderRadius: 8,
        ...Mixins.padding(globalVars.itemList.padding),
        display: "grid",
        gridTemplateColumns: "24px auto 1fr",
        alignItems: "center",
        alignContent: "center",
        gap: 8,
        width: "100%",
        overflow: "visible",
        "& span": {
            justifySelf: "start",
            textAlign: "start",
            textWrap: "pretty",
        },
        "&:before": {
            content: "''",
            display: "block",
            position: "absolute",
            top: 0,
            left: -21,
            width: "20px",
            height: "20px",
            transform: "translateY(50%)",
            borderInlineStart: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderBlockEnd: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderEndStartRadius: "50%",
        },
    });
    return {
        rootCommentItem,
        childCommentItem,
        childContainer,
        descender,
        commentChildren,
        hole,
    };
};
