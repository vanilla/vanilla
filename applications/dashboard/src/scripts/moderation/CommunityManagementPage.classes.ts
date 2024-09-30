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
        padding: "6px 18px",
        minHeight: 45,
        backgroundColor: "white",
        position: "sticky",
        top: 95, // 🪄 number
        zIndex: 1050,
    });

    const secondaryTitleBarStart = css({
        display: "flex",
        alignItems: "center",
        gap: 32,
    });

    const secondaryTitleBarButtons = css({
        display: "flex",
        gap: 16,
    });

    const pager = css({
        padding: 0,
    });

    const content = css({
        margin: "16px 0",
        padding: "0 18px",
    });

    const list = css({
        display: "flex",
        flexDirection: "column",
        gap: 16,
    });

    const listItemLink = css({
        display: "inline-flex",
    });

    return {
        secondaryTitleBar,
        secondaryTitleBarStart,
        secondaryTitleBarButtons,
        pager,
        content,
        list,
        listItemLink,
    };
};
