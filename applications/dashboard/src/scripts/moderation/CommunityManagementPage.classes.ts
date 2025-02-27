/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const communityManagementPageClasses = () => {
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
        pager,
        content,
        list,
        listItemLink,
    };
};
