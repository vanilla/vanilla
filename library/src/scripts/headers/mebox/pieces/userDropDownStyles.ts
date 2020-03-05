/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, unit } from "@library/styles/styleHelpers";
import { componentThemeVariables, styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { style } from "typestyle";

export const userDropDownVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = componentThemeVariables("userDropDown");

    const item = {
        topPadding: 6,
        rightPadding: 18,
        bottomPadding: 6,
        leftPadding: 18,
        ...themeVars.subComponentStyles("item"),
    };

    const userCard = {
        topMargin: 24,
        bottomMargin: 24,
        ...themeVars.subComponentStyles("userCard"),
    };

    const userName = {
        topMargin: 9,
        bottomMargin: 24,
        paddingRight: item.rightPadding,
        paddingLeft: item.leftPadding,
        fontWeight: globalVars.fonts.weights.semiBold,
        fontSize: globalVars.fonts.size.large,
        lineHeight: globalVars.lineHeights.condensed,
        ...themeVars.subComponentStyles("userName"),
    };

    const contents = {
        width: 300,
        ...themeVars.subComponentStyles("contents"),
    };

    return { userCard, userName, contents, item };
});

export const userDropDownClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = userDropDownVariables();
    const style = styleFactory("userDropDown");

    const userCard = style("userCard", {
        listStyle: "none",
    });

    const userCardPhotoLink = style("userCardPhotoLink", {
        display: "block",
    });

    const userCardPhoto = style("userCardPhoto", {
        border: `solid 1px ${globalVars.mixBgAndFg(0.3)}`,
        marginTop: unit(vars.userCard.topMargin),
        marginLeft: "auto",
        marginRight: "auto",
    });

    const userCardName = style("userCardName", {
        display: "block",
        color: "inherit",
        fontWeight: vars.userName.fontWeight,
        fontSize: unit(vars.userName.fontSize),
        lineHeight: vars.userName.lineHeight,
        textAlign: "center",
        marginTop: unit(vars.userName.topMargin),
        marginRight: "auto",
        marginBottom: unit(vars.userName.bottomMargin),
        marginLeft: "auto",
        paddingRight: unit(vars.userName.paddingRight),
        paddingLeft: unit(vars.userName.paddingLeft),
    });

    const contents = style("contents", {
        width: unit(vars.contents.width),
    });

    return {
        userCardPhotoLink,
        userCardPhoto,
        userCardName,
        contents,
        userCard,
    };
});
