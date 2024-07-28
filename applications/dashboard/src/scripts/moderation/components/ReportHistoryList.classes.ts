/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";

export const reportHistoryListClasses = () => {
    const root = css({
        ...Mixins.margin({
            bottom: globalVariables().spacer.componentInner,
        }),
        "&:not(:first-child)": {
            ...Mixins.margin({
                top: globalVariables().spacer.panelComponent * 1.5,
            }),
        },
    });
    const group = css({
        "&:not(:first-of-type)": {
            ...Mixins.margin({
                top: globalVariables().spacer.panelComponent * 1.5,
            }),
        },
    });
    const title = css({
        display: "block",
        fontSize: globalVariables().fonts.size.medium,
        fontWeight: globalVariables().fonts.weights.semiBold,
        marginBlockEnd: globalVariables().spacer.componentInner / 2,
    });

    const reportList = css({
        "&&": {
            // Fighting with AdminLayout styles
            padding: 0,
            margin: "0px 0px 0px -18px",
            listStyle: "none",
            "& > li": {
                listStyle: "none",
                margin: "4px 0px",
            },
        },
    });
    const reportListItem = css({
        "& button": {
            width: "100%",
            display: "flex",
            flexDirection: "column",
            alignContent: "center",
            justifyContent: "start",
            gap: 4,
            padding: "8px 16px",
        },
    });
    const active = css({
        backgroundColor: "#e8ecf2",
        borderRadius: "0px 6px 6px 0px",
    });
    const reportTime = css({
        ...Mixins.font({
            size: globalVariables().fonts.size.small,
            color: "#9297a0",
        }),
        fontStyle: "italic",
    });

    const profileLine = css({
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        width: "100%",
        alignItems: "center",
        justifyContent: "space-between",
        marginBlockStart: 4,
        marginInlineStart: 2,
    });

    const userLink = css({
        "&&": {
            display: "flex",
            alignItems: "center",
            justifyContent: "start",
            gap: 4,
            flexWrap: "wrap",
            "& > div": {
                maxWidth: "16px",
                height: "auto",
                aspectRatio: "1 / 1",
            },
            "&, &:hover, &:active, &:focus, &:focus-visible": {
                ...Mixins.font({
                    size: globalVariables().fonts.size.medium,
                    color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
                    lineHeight: 1,
                }),
            },
        },
    });
    const noteContent = css({
        ...Mixins.font({
            size: globalVariables().fonts.size.medium,
        }),
        textAlign: "start",
        marginBlockStart: 2,
        marginInlineStart: 2,
    });
    const metaItems = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "start",
        justifyContent: "start",
        columnGap: 4,
        rowGap: 4,
        marginInlineStart: -6,
        "& > div": {
            marginBlock: 0,
            marginInline: 0,
        },
    });

    const emptyState = css({
        display: "flex",
        flexDirection: "column",
        gap: 8,
    });
    const emptyHeadline = css({
        ...Mixins.font({
            size: globalVariables().fonts.size.large,
            weight: globalVariables().fonts.weights.semiBold,
        }),
    });
    const emptyByline = css({
        ...Mixins.font({
            size: globalVariables().fonts.size.medium,
            weight: globalVariables().fonts.weights.normal,
        }),
    });

    return {
        root,
        group,
        title,
        reportList,
        reportListItem,
        metaItems,
        reportTime,
        profileLine,
        userLink,
        noteContent,
        active,
        emptyState,
        emptyHeadline,
        emptyByline,
    };
};
