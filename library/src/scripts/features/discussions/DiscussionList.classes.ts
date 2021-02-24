/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const discussionListClasses = useThemeCache(() => {
    const vars = discussionListVariables();
    const title = css({
        ...Mixins.font(vars.item.title.font),
        "&.isRead": {
            ...Mixins.font(vars.item.title.fontRead),
        },
        "&:hover, &:focus, &:active": {
            ...Mixins.font(vars.item.title.fontState),
        },
    });

    return { title };
});
