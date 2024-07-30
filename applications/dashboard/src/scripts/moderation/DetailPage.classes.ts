/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";

export const detailPageClasses = () => {
    const layout = css({
        margin: "16px 18px",
        "& > div": {
            marginBottom: 28,
            "& h2": {
                marginBottom: 16,
                display: "flex",
                justifyContent: "space-between",
                alignContent: "center",
            },
        },
    });

    const secondaryTitleBarTop = css({
        top: 156,
    });

    const titleLayout = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "start",
    });

    const backlink = css({
        display: "inline-flex",
        margin: 0,
        transform: "none",
        position: "relative",
        top: 1,
        marginRight: 6,
    });
    const tag = css({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
    });
    const headerIconLayout = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "start",
        gap: 8,
        "& > span": {
            maxHeight: 16,
        },
    });
    const postAttachment = css({
        marginBlock: 16,
    });
    const editableTitleLayout = css({
        flex: 1,
        display: "flex",
        justifyContent: "center",
        flexDirection: "row",
        alignItems: "center",
        fontWeight: 600,
    });
    const editableTitleInput = css({
        "&&": {
            ...Mixins.font({
                size: 20,
                weight: "bold",
                lineHeight: 1,
            }),
            marginInlineStart: -6,
        },
    });
    const assigneeDropdown = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "start",
        flexWrap: "wrap",
        gap: 2,
        "& > span": {
            ...Mixins.font({
                color: metasVariables().font.color,
                weight: globalVariables().fonts.weights.normal,
            }),
            textWrap: "nowrap",
        },
    });
    const assigneeOverrides = css({
        borderColor: "transparent",
        minHeight: "unset",
        height: 32,
        "&&": {
            marginTop: 0,
            marginLeft: 0,
        },
    });
    const autoCompleteOverrides = css({
        "& input": {
            fontSize: globalVariables().fonts.size.medium,
            fontWeight: globalVariables().fonts.weights.semiBold,
            lineHeight: 1,
        },
    });
    const commentsWrapper = css({
        marginInlineStart: 18,
    });
    return {
        layout,
        secondaryTitleBarTop,
        backlink,
        tag,
        headerIconLayout,
        postAttachment,
        editableTitleLayout,
        editableTitleInput,
        assigneeDropdown,
        assigneeOverrides,
        autoCompleteOverrides,
        titleLayout,
        commentsWrapper,
    };
};
