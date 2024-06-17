/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const communityManagementPageClasses = () => {
    const secondaryTitleBar = css({
        borderBottom: singleBorder(),
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        padding: "6px 28px",
        backgroundColor: "white",
        position: "sticky",
        top: 98, // 🪄 number
        zIndex: 1050,
    });

    const pager = css({
        padding: 0,
    });

    const content = css({
        margin: "16px 0",
        padding: "0 28px",
    });

    const list = css({
        display: "flex",
        flexDirection: "column",
        gap: 16,
    });

    return {
        secondaryTitleBar,
        pager,
        content,
        list,
    };
};
