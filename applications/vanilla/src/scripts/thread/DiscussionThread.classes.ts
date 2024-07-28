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

    const resolved = css({
        marginInlineEnd: 4,
    });

    const reportsTag = css({
        flexShrink: 0,
        inlineMarginStart: 4,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        gap: 4,
        padding: "4px inherit",
        "& svg": {
            transform: "translateY(-1%)",
        },
    });

    return {
        closedTag,
        resolved,
        reportsTag,
    };
});
