/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";

export const reportModalClasses = () => {
    const layout = css({
        width: "100%",
        margin: "16px 0",
    });

    const reasonLayout = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "start",
        gap: 4,
        fontSize: globalVariables().fonts.size.small,
        "& .name": {
            display: "inline-flex",
            minHeight: 18,
            fontSize: globalVariables().fonts.size.medium,
            fontWeight: globalVariables().fonts.weights.normal,
        },
    });

    const checkbox = css({
        alignItems: "start",
        padding: "0px 0px 10px",
    });

    const moderationOptions = css({
        padding: "0px 0px 8px",
    });

    const editorClasses = css({
        margin: "0px 0px 16px",
        minHeight: 100,
    });

    const formHeadings = css({
        fontWeight: globalVariables().fonts.weights.semiBold,
        fontSize: globalVariables().fonts.size.large,
        marginTop: 16,
        marginBottom: 10,
    });

    const scrollableArea = css({
        maxHeight: "25vh",
        overflowY: "auto",
    });

    return {
        layout,
        reasonLayout,
        checkbox,
        moderationOptions,
        editorClasses,
        formHeadings,
        scrollableArea,
    };
};
