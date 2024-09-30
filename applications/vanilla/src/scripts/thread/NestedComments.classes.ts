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

    const highlightCommentItem = css({
        background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.2)),
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
        position: "relative",
    });

    const descenderLine = css({
        position: "absolute",
        top: 36,
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
        columnGap: 8,
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

    const holeError = css({
        gridColumn: "3/4",
    });

    const reply = css({
        position: "relative",
        display: "flex",
        placeContent: "center",
        placeItems: "center",
        width: "100%",
        paddingBlockStart: 16,
        paddingInlineStart: 20,
        "&:before": {
            content: "''",
            display: "block",
            position: "absolute",
            top: 0,
            left: -20,
            width: "30px",
            height: "20px",
            transform: "translateY(50%)",
            borderInlineStart: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderBlockEnd: `1px solid ${ColorsUtils.colorOut(globalVars.border.color)}`,
            borderEndStartRadius: "25% 50%",
        },
    });

    const replyEditor = css({
        margin: 0,
    });

    const warningModalContent = css({
        p: {
            marginBottom: 16,
            lineHeight: 1.5,
        },
    });

    const warningEmphasis = css({
        fontWeight: globalVariables().fonts.weights.semiBold,
        color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
    });

    const warningModalConfirm = css({
        color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
    });

    return {
        childCommentItem,
        highlightCommentItem,
        childContainer,
        descender,
        descenderLine,
        commentChildren,
        hole,
        holeError,
        reply,
        replyEditor,
        warningModalContent,
        warningEmphasis,
        warningModalConfirm,
    };
};
