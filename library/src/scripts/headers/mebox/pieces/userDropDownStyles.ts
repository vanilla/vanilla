/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { componentThemeVariables, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

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
        marginTop: styleUnit(vars.userCard.topMargin),
        marginLeft: "auto",
        marginRight: "auto",
    });

    const userCardName = style("userCardName", {
        display: "block",
        color: "inherit",
        fontWeight: vars.userName.fontWeight,
        fontSize: styleUnit(vars.userName.fontSize),
        lineHeight: vars.userName.lineHeight,
        textAlign: "center",
        marginTop: styleUnit(vars.userName.topMargin),
        marginRight: "auto",
        marginBottom: styleUnit(vars.userName.bottomMargin),
        marginLeft: "auto",
        paddingRight: styleUnit(vars.userName.paddingRight),
        paddingLeft: styleUnit(vars.userName.paddingLeft),
    });

    const contents = style("contents", {
        width: styleUnit(vars.contents.width),
    });

    return {
        userCardPhotoLink,
        userCardPhoto,
        userCardName,
        contents,
        userCard,
    };
});
