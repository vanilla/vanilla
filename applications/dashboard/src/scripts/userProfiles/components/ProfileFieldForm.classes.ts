/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export default function ProfileFieldFormClasses() {
    return {
        formGroupWrapper: css({
            [`.formGroup-checkBox`]: {
                paddingTop: 0,
                paddingBottom: 0,
                [`label > span:nth-of-type(2)`]: {
                    fontWeight: 500,
                },
            },
            [`[class*="formGroup"]`]: {
                borderBottom: 0,
                [`&:last-of-type`]: {
                    paddingBottom: 16,
                    borderBottom: singleBorder(),
                },
            },
        }),
    };
}
