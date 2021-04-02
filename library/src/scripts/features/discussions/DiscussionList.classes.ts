/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { styleUnit } from "../../styles/styleUnit";

export const discussionListClasses = useThemeCache(() => {
    const vars = discussionListVariables();
    const globalVars = globalVariables();

    const title = css({
        ...Mixins.font(vars.item.title.font),
        fontWeight: globalVars.fonts.weights.semiBold,
        "&.isRead": {
            ...Mixins.font(vars.item.title.fontRead),
        },
        "&:hover, &:focus, &:active": {
            ...Mixins.font(vars.item.title.fontState),
        },
    });

    const counterPosition = css({
        position: "absolute",
        top: percent(72.5),
        left: percent(35),
    });
    const options = {
        move: css({
            minHeight: styleUnit(200),
        }),
    };

    const userTag = css({
        "&:hover, &:focus, &:active": {
            "& span": {
                ...Mixins.font({ color: globalVars.mainColors.primary }),
            },
        },
    });

    return { title, counterPosition, options, userTag };
});
