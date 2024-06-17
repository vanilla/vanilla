/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const triageListItemClasses = () => {
    const container = css({
        ...shadowHelper().embed(),
        borderRadius: 6,
        display: "flex",
        flexDirection: "column",
    });

    const main = css({
        display: "flex",
        alignItems: "start",
        justifyContent: "space-between",
        padding: 16,
        gap: 16,
    });

    const metaLine = css({
        marginTop: 6,
        marginLeft: "4px!important",
    });

    const statusIcon = css({ marginRight: -2 });

    const quickActions = css({
        display: "flex",
        alignItems: "center",
        gap: 6,
    });

    const footer = css({
        display: "flex",
        justifyContent: "end",
        alignItems: "center",
        gap: 12,
        borderTop: singleBorder(),
        padding: "0 16px",
    });

    const actions = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "end",
        alignItems: "center",
        gap: 21,
        minHeight: 42,
    });

    return {
        container,
        main,
        statusIcon,
        metaLine,
        quickActions,
        footer,
        actions,
    };
};
