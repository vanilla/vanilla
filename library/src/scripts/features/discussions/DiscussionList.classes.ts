/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { ListItemIconPosition, listItemVariables } from "@library/lists/ListItem.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "../../styles/styleUnit";
import voteCounterVariables from "@library/voteCounter/VoteCounter.variables";

export const discussionListClasses = useThemeCache(() => {
    const vars = discussionListVariables();
    const globalVars = globalVariables();

    const listItemVars = listItemVariables();

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

    const voteCounterPosition =
        listItemVars.options.iconPosition === ListItemIconPosition.META
            ? {
                  top: "20%",
                  left: "75%",
              }
            : {
                  top: "72.5%",
                  left: "35%",
              };

    const voteCounterContainer = css({
        position: "absolute",
        ...voteCounterPosition,
    });

    const options = {
        move: css({
            minHeight: styleUnit(200),
        }),
    };

    type AvailableReactionsCount = 1 | 2;

    const voteCounterVars = voteCounterVariables();

    const iconAndVoteCounterWrapper = useThemeCache((availableReactionsCount: AvailableReactionsCount = 1) => {
        return css({
            position: "relative",
            ...(listItemVars.options.iconPosition === ListItemIconPosition.META
                ? Mixins.margin({
                      right: voteCounterVars.sizing.width,
                      bottom: availableReactionsCount > 1 ? voteCounterVars.sizing.magicOffset : 0,
                  })
                : {}),
        });
    });

    const userTag = css({
        "&:hover, &:focus, &:active": {
            "& span": {
                ...Mixins.font({ color: globalVars.mainColors.primary }),
            },
        },
    });

    const resolved = css({
        margin: 0,
    });

    return {
        title,
        iconAndVoteCounterWrapper,
        voteCounterContainer,
        options,
        userTag,
        resolved,
    };
});
