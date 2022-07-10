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
import { ColorsUtils } from "@library/styles/ColorsUtils";

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

    const checkedboxRowStyle = css({
        backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
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

    const resolved = css({
        ...Mixins.margin({
            horizontal: 0,
        }),
    });

    const bulkActionsText = css({
        display: "block",
        marginBottom: 12,
        fontSize: globalVars.fonts.size.medium,
        color: "#838691",
    });

    const bulkActionsButtons = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "start",

        "& > *": {
            ...Mixins.margin({
                horizontal: 6,
            }),
            "&:first-child": {
                marginLeft: 0,
            },
        },
    });

    // Used to make sure the legacy checkbox aligns properly.
    const legacySelectAllCheckbox = css({
        paddingTop: 0,
        paddingBottom: 0,
        transform: "translateY(2px)",
    });

    return {
        title,
        iconAndVoteCounterWrapper,
        voteCounterContainer,
        checkedboxRowStyle,
        options,
        resolved,
        bulkActionsButtons,
        bulkActionsText,
        legacySelectAllCheckbox,
    };
});
