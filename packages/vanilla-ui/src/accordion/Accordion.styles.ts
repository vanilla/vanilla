/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";

export const accordionClasses = () => ({
    panel: css({}),
    arrow: css({
        verticalAlign: "middle",
        marginTop: -1,
        marginRight: 5,
    }),
    header: css({
        cursor: "pointer",
        padding: "6px 0",
        display: "block",
    }),
    item: css({
        "&:not(:first-child)": {
            borderTop: "solid 1px #c1cbd7",
        },
    }),
});
