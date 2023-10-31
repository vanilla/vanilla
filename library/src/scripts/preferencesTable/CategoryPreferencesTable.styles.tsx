/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

export const categoryPreferencesTableClasses = () => {
    const checkBox = css({
        paddingLeft: 0,
        paddingBottom: 4,
        "& > span": {
            fontWeight: "normal",
        },
    });
    const inset = css({
        marginLeft: 26,
    });

    const errorBlock = css({
        paddingLeft: 7,
    });
    return {
        checkBox,
        inset,
        errorBlock,
    };
};
