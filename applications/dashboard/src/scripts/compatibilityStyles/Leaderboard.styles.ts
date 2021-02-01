/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { leaderboardVariables } from "@dashboard/compatibilityStyles/Leaderboard.variables";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { Mixins } from "@library/styles/Mixins";
import { singleLineEllipsis } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { px } from "csx";
import { CSSObject } from "@emotion/css";

export const leaderboardCSS = () => {
    const vars = leaderboardVariables();

    const rootStyles = {
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
    };

    const titleStyles = {
        display: "flex",
        justifyContent: vars.title.alignment,
        ...Mixins.background(vars.title.background),
        ...Mixins.font(vars.title.font),
        ...Mixins.padding(vars.title.spacing.padding),
    };

    const listStyles = {
        ...Mixins.margin(vars.list.spacing),
    };

    const listItemStyles = {
        ...Mixins.padding(vars.listItem.spacing),
    };

    const linkStyles = {
        display: "flex",
        alignItems: "center",
        ...Mixins.font({
            color: vars.username.font.color,
        }),
    };

    const asideStyles = {
        order: 2,
        ...Mixins.margin({
            all: 0,
            left: "auto",
        }),
    };

    const countStyles = {
        ...Mixins.font(vars.count.font),
    };

    const userStyles: CSSObject = {
        whiteSpace: "nowrap",
    };

    const usernameStyles = {
        verticalAlign: "middle",
        display: "inline-block",
        ...singleLineEllipsis(),
        maxWidth: `calc(100% - ${vars.profilePhoto.size}px - ${vars.username.margin.horizontal}px)`,
        ...Mixins.margin(vars.username.margin),
        ...Mixins.font({
            ...vars.username.font,
            lineHeight: px(vars.profilePhoto.size),
        }),
    };

    const profilePhotoStyles = {
        verticalAlign: "middle",
        borderRadius: "50%",
        overflow: "hidden",
        width: vars.profilePhoto.size,
        height: vars.profilePhoto.size,
    };

    cssOut(".Leaderboard", rootStyles, {
        "h4.Leaderboard__title": titleStyles,
        ".Leaderboard__user-list": {
            ...listStyles,
            ".Leaderboard__user-list__item": listItemStyles,
            ...{
                a: linkStyles,
                ".Aside": asideStyles,
                ".Count": countStyles,
                ".Leaderboard-User": userStyles,
                ".Leaderboard-User .ProfilePhoto": profilePhotoStyles,
                ".Leaderboard-User .Username": usernameStyles,
            },
        },
    });
};
