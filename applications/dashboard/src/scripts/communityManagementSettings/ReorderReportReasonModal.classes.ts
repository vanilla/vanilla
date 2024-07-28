/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

export const reorderReportReasonModalClasses = () => {
    const frameBody = css({
        padding: "8px 4px",
    });

    const row = css({
        width: "100%",
        display: "grid",
        gridTemplateColumns: "200px auto",
        alignItems: "center",
        padding: "4px 16px",
        gap: 48,
    });

    return {
        frameBody,
        row,
    };
};
