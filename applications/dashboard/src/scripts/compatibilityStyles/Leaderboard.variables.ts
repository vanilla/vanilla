/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";

enum TitleAlignment { // FIXME: The same interface probably exists elsewhere
    LEFT = "left",
    CENTER = "center",
    RIGHT = "right",
}

/**
 * @varGroup leaderboard
 * @description Variables affecting the leaderdboard module, made available through the Badges plugin.
 * @commonTitle Leaderboard
 */
export const leaderboardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();

    const makeThemeVars = variableFactory("leaderboard", forcedVars);

    /**
     * @varGroup leaderboard.colors
     * @commonTitle Colors
     */
    const colors = makeThemeVars("colors", {
        /**
         * @var leaderboard.colors.bg
         * @title Background color
         * @type string
         * @format hex-color
         */
        bg: globalVars.mainColors.bg,
    });

    /**
     * @varGroup leaderboard.box
     * @commonTitle Box
     */
    const box = makeThemeVars("box", {
        /**
         * @var leaderboard.box.enabled
         * @title Enabled
         * @description Enables a shadowed box around
         * @type boolean
         */
        enabled: false,
        /**
         * @var leaderboard.box.borderRadius
         * @title Border radius
         * @description Border radius of the box
         * @type number
         */
        borderRadius: globalVars.border.radius,
    });

    /**
     * @varGroup leaderboard.title
     * @commonTitle Title
     */
    const title = makeThemeVars("title", {
        /**
         * @varGroup leaderboard.title.background
         * @commonTitle Background
         * @expand background
         */
        background: Variables.background({}),
        /**
         * @varGroup leaderboard.title.font
         * @commonTitle Font
         * @expand font
         */
        font: Variables.font({}),
        spacing: {
            /**
             * @varGroup leaderboard.title.spacing.padding
             * @commonTitle Padding
             * @expand spacing
             */
            padding: Variables.spacing({}),
        },
        /**
         * @var leaderboard.title.alignment
         * @title Alignment
         * @type string
         * @enum center | left | right
         */
        alignment: "left" as TitleAlignment,
    });

    const list = makeThemeVars("list", {
        spacing: Variables.spacing({}),
    });

    const listItem = makeThemeVars("listItem", {
        /**
         * @varGroup leaderboard.listItem.spacing
         * @commonTitle List Item Spacing
         * @expand spacing
         */
        spacing: Variables.spacing({
            horizontal: 0,
            vertical: 6,
        }),
    });

    const profilePhoto = makeThemeVars("profilePhoto", {
        /**
         * @var leaderboard.profilePhoto.size
         * @title Profile photo size
         * @type number
         */
        size: 38,
    });

    const username = makeThemeVars("username", {
        /**
         * @varGroup leaderboard.username.font
         * @commonTitle Username - Font
         * @expand font
         */
        font: Variables.font({}),
        /**
         * @varGroup leaderboard.username.margin
         * @commonTitle Username - Margin
         * @expand spacing
         */
        margin: Variables.spacing({
            horizontal: 10,
        }),
    });

    const count = makeThemeVars("count", {
        /**
         * @varGroup leaderboard.count.font
         * @commonTitle Count - Font
         * @expand font
         */
        font: Variables.font({}),
    });

    return {
        colors,
        box,
        title,
        list,
        listItem,
        profilePhoto,
        username,
        count,
    };
});
