/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

export default function ProfileFieldFormClasses() {
    return {
        formGroup: css({
            [`.formGroup-dropDown`]: {
                borderBottom: 0,
            },
            [`.formGroup-checkBox`]: {
                paddingTop: 0,
                paddingBottom: 0,
                borderBottom: 0,
                [`label > span:nth-of-type(2)`]: {
                    fontWeight: 500,
                },
            },

            [`.formGroup-checkBox:last-of-type`]: {
                paddingBottom: 16,
                borderBottom: "1px dotted #e7e8e9",
            },
        }),
    };
}
