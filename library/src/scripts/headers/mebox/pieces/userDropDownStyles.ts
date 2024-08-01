/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { componentThemeVariables } from "@library/styles/styleUtils";
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
        fontSize: globalVars.fonts.size.subTitle,
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

    const userCard = css({
        listStyle: "none",
        ...Mixins.padding({
            vertical: 4,
            horizontal: 14,
        }),
        display: "flex",
        justifyContent: "start",
        alignItems: "center",
        gap: 12,
    });

    const userCardPhotoLink = css({
        display: "block",
    });

    const userCardPhoto = css({
        border: `solid 1px ${globalVars.mixBgAndFg(0.3)}`,
        // A little crazy that the difference between Medium(60) and Large(100) photos is 40px
        "&&": {
            width: 70,
            height: "auto",
            aspectRatio: "1/1",
        },
    });

    const userCardName = css({
        color: "inherit",
        fontWeight: vars.userName.fontWeight,
        fontSize: styleUnit(vars.userName.fontSize),
        lineHeight: vars.userName.lineHeight,
        textWrap: "pretty",
    });

    const contents = css({
        width: styleUnit(vars.contents.width),
    });

    const userInfo = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "start",
    });

    const email = css({
        fontSize: styleUnit(globalVars.fonts.size.small),
    });

    const accountLinks = css({
        fontSize: styleUnit(globalVars.fonts.size.medium),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.darkText),
        ...Mixins.linkDecoration(),
    });

    return {
        userCardPhotoLink,
        userCardPhoto,
        userCardName,
        contents,
        userInfo,
        accountLinks,
        email,
        userCard,
    };
});
