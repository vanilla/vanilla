/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";

export const DashboardTitleBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const container = css({
        background: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
        height: 48,
        position: "sticky",
        top: 0,
        zIndex: 1051,
    });

    const flexContainer = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "space-between",
        alignItems: "center",
        height: 48,
    });

    const backBtn = css({
        display: "inline-block",
        ...Mixins.font({ size: 10, color: globalVars.elementaryColors.white, transform: "uppercase" }),
        ...Mixins.padding({ vertical: 2, horizontal: 12 }),
        ...Mixins.border({ radius: 14, width: 1 }),
        marginLeft: 18,

        svg: {
            marginLeft: 3,
            verticalAlign: -4,
            width: 11,
        },

        "&:hover, &:focus, &:active": {
            backgroundColor: "#1db1fd",
            color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        },
    });

    const brand = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "center",
        minWidth: 168,
    });

    const logoContainer = css({
        width: 79,
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),

        height: "100%",
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "center",
    });

    const logo = css({
        width: 79,
        height: 32,
    });

    const nav = css({
        display: "flex",
        flexDirection: "row",
        height: "100%",
        flexBasis: 800,
        justifyContent: "center",
        ...Mixins.padding({ horizontal: 40 }),
    });

    const link = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "center",
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        flexBasis: 180,
        borderRight: "1px solid rgba(0, 0, 0, 0.125)",

        "&.active, &:hover, &:focus": {
            background: "#04a8fd",
            color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        },

        "&:first-child": {
            borderLeft: "1px solid rgba(0, 0, 0, 0.125)",
        },
    });

    const linkLabel = css({});

    const meBox = css({
        width: 240,
    });

    const iconWrapper = css({
        ...Mixins.margin({ left: -4, right: 4 }),
        width: 28,
        height: 28,
        "& svg": {
            width: "100%",
            height: "100%",
        },
    });

    return {
        container,
        flexContainer,
        brand,
        backBtn,
        logoContainer,
        logo,
        nav,
        link,
        linkLabel,
        meBox,
        iconWrapper,
    };
});
