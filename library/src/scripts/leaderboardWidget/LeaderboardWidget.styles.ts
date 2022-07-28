/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Mixins } from "@library/styles/Mixins";
import { singleLineEllipsis, styleUnit } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { color, percent, px } from "csx";
import { css } from "@emotion/css";
import { BorderType } from "@library/styles/styleHelpers";
import { leaderboardVariables } from "@library/leaderboardWidget/LeaderboardWidget.variables";

export const leaderboardWidgetClasses = () => {
    const vars = leaderboardVariables();

    const rootStyles = css({
        ...Mixins.background({
            color: vars.colors.bg,
        }),
        ...(vars.box.enabled
            ? {
                  borderTopLeftRadius: vars.box.borderRadius,
                  borderTopRightRadius: vars.box.borderRadius,
                  ...shadowHelper().embed(),
                  overflow: "hidden",
              }
            : {}),
    });

    const titleStyles = css({
        display: "flex",
        justifyContent: vars.title.alignment,
        ...Mixins.background(vars.title.background),
        ...Mixins.font(vars.title.font),
        ...Mixins.padding(vars.title.spacing.padding),
    });

    const listStyles = css({
        ...Mixins.padding(vars.list.spacing),
        ...Mixins.border({ style: BorderType.NONE }),
    });

    const listItemStyles = css({
        ...Mixins.padding(vars.listItem.spacing),
    });

    const linkStyles = css({
        display: "flex",
        alignItems: "center",
        ...Mixins.clickable.itemState(),
        ...Mixins.font({
            color: vars.username.font.color ?? color(Mixins.clickable.itemState().color),
        }),
    });

    const asideStyles = css({
        order: 2,
        ...Mixins.margin({
            all: 0,
            left: "auto",
        }),
    });

    const countStyles = css({
        ...Mixins.font(vars.count.font),
    });

    const userStyles = css({
        whiteSpace: "nowrap",
        display: "flex",
        ...Mixins.padding({
            vertical: 4,
        }),
    });

    const usernameStyles = css({
        verticalAlign: "middle",
        display: "inline-block",
        ...singleLineEllipsis(),
        maxWidth: `calc(100% - ${vars.profilePhoto.size}px - ${vars.username.margin.horizontal}px)`,
        ...Mixins.margin(vars.username.margin),
        ...Mixins.font({
            ...vars.username.font,
            lineHeight: px(vars.profilePhoto.size),
        }),
    });

    const profilePhotoStyles = css({
        verticalAlign: "middle",
        borderRadius: "50%",
        overflow: "hidden",
        width: vars.profilePhoto.size,
        height: vars.profilePhoto.size,
        flexShrink: 0,
    });

    /**
     * Table styles
     */

    const table = css({
        width: percent(100),
    });

    const row = css({
        boxSizing: "border-box",
    });

    const cell = css({
        padding: 0,
        minWidth: styleUnit(100),
        verticalAlign: "middle",
        textAlign: "right",
        "&:first-of-type": {
            textAlign: "left",
        },
    });

    return {
        rootStyles,
        titleStyles,
        listStyles,
        listItemStyles,
        linkStyles,
        asideStyles,
        countStyles,
        userStyles,
        profilePhotoStyles,
        usernameStyles,
        table,
        row,
        cell,
    };
};
