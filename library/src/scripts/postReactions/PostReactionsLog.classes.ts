/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

/**
 * Classes and styling for the reactions component
 */
export const postReactionsLogClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        ...Mixins.margin({ vertical: globalVars.gutter.half }),
        padding: 0,
        listStyle: "none",
    });

    const noReactions = css({
        ...Mixins.margin({ all: globalVars.gutter.size }),
    });

    const reactionLogItem = css({
        margin: 0,
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "space-between",
        gap: globalVars.gutter.half,
        ...Mixins.border({
            ...globalVars.border,
            radius: 0,
            width: 0,
        }),
        borderBottomWidth: 1,
        ...Mixins.padding({
            vertical: globalVars.gutter.quarter,
            left: globalVars.gutter.half,
            right: globalVars.gutter.quarter,
        }),
        "&:last-child": {
            borderWidth: 0,
        },
    });

    const reactionLogDate = css({
        width: "12ch",
    });

    const reactionLogUser = css({
        flex: 1,
        ...Mixins.clickable.itemState({ default: globalVars.mainColors.primary }),
    });

    const reactionLogName = css({
        width: "12ch",
        textAlign: "center",
    });

    const reactionLogDelete = css({});

    const reactionLogTrigger = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        gap: globalVars.gutter.quarter / 2,
    });

    return {
        root,
        noReactions,
        reactionLogItem,
        reactionLogDate,
        reactionLogUser,
        reactionLogName,
        reactionLogDelete,
        reactionLogTrigger,
    };
});
