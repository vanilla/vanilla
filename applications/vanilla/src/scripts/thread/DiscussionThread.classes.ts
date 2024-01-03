/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const discussionThreadClasses = useThemeCache(() => {
    const closedTag = css({
        ...Mixins.margin({ horizontal: "1em" }),
        verticalAlign: "middle",
    });

    return {
        closedTag,
    };
});
