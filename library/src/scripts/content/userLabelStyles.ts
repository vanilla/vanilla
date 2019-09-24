/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const userLabelVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("userLabel");
    const globalVars = globalVariables();
    const { mainColors } = globalVars;

    const avatar = makeThemeVars("spacing", {
        size: 40,
    });

    const name = makeThemeVars("name", {
        fontSize: globalVars.fonts.size.medium,
    });

    return {
        avatar,
        name,
    };
});

export const userLabelClasses = useThemeCache(() => {
    const style = styleFactory("userLabel");
    const globalVars = globalVariables();

    const root = style({});
    const avatar = style("avatar", {});
    const avatarLink = style("avatarLink", {});
    const topRow = style("topRow", {});
    const bottomRow = style("bottomRow", {});
    const userName = style("userName", {});
    const main = style("main", {});
    const date = style("date", {});
    const dateLink = style("dateLink", {});

    return { root, avatar, avatarLink, topRow, bottomRow, userName, main, date, dateLink };
});
