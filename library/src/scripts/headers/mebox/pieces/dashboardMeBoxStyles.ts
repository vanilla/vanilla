/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const dashboardMeBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        alignSelf: "center",
    });

    const container = css({
        flexBasis: 168,
        display: "flex",
        justifyContent: "flex-end",
    });

    const mobileContainer = css({
        flexBasis: "initial",
    });

    const userPhoto = css({
        "&&": {
            border: 0,
        },
    });

    const dropdownBody = css({
        display: "flex",
        ...Mixins.padding({ all: 16 }),
    });

    const dropdownUserPhoto = css({
        "&&": {
            flexBasis: styleUnit(84),
            height: styleUnit(84),
            width: styleUnit(84),
            ...Mixins.border({ radius: 4, width: 0 }),
            ...Mixins.margin({ right: 16 }),
        },
    });

    const dropdownUserInfo = css({});

    const dropdownUserName = css({
        display: "block",
        ...Mixins.font({ color: globalVars.mainColors.fg, weight: 700 }),
        ...Mixins.padding({ bottom: 8 }),
        "&:hover, &:focus, &:active": {
            ...Mixins.font({ color: globalVars.links.colors.default }),
        },
    });

    const dropdownUserRank = css({});

    const dropdownProfileLink = css({
        "&&": {
            ...Mixins.margin({ top: 16 }),
            ...Mixins.border({ radius: 14 }),
            ...Mixins.font({ size: 10, transform: "uppercase", lineHeight: 2 }),
            minHeight: styleUnit(24),

            "&:not([disabled]):hover, &&:not([disabled]):focus, &&:not([disabled]):active": {
                ...Mixins.border({ radius: 14 }),
            },
        },
    });

    const dropdownFooter = css({
        "&&": {
            display: "flex",
            flexDirection: "column",
            justifyContent: "center",
            alignItems: "center",
            padding: 0,
        },
    });

    const supportSection = css({
        display: "flex",
        flexDirection: "column",
        width: "100%",
    });

    const supportLink = css({
        display: "flex",
        justifyContent: "space-between",
        ...Mixins.font({ color: globalVars.mainColors.fg }),
        ...Mixins.padding({ all: 16 }),
        borderBottom: singleBorder(),
        background: ColorsUtils.colorOut(globalVars.elementaryColors.white.darken(0.025)),

        "&:hover, &:focus, &:active": {
            ...Mixins.font({ color: globalVars.links.colors.default }),
            background: ColorsUtils.colorOut(globalVars.elementaryColors.white.darken(0.01)),
        },
    });

    const signOutButton = css({
        ...Mixins.margin({ all: 16 }),
    });

    return {
        root,
        container,
        mobileContainer,
        userPhoto,
        dropdownBody,
        dropdownUserPhoto,
        dropdownUserInfo,
        dropdownUserRank,
        dropdownUserName,
        dropdownProfileLink,
        dropdownFooter,
        supportSection,
        supportLink,
        signOutButton,
    };
});
