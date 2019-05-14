/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    borders,
    colorOut,
    flexHelper,
    paddings,
    singleBorder,
    unit,
    userSelect,
    fonts,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { border, calc, percent, px, translateX } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { frameVariables } from "@library/layout/frame/frameStyles";

export const mobileDropDownVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();
    const mixBgAndFg = globalVars.mixBgAndFg;
    const vars = variableFactory("mobileDropDown");

    const title = vars("title", {
        letterSpacing: -0.26,
        maxWidth: calc(`100% - ${px(titleBarVars.endElements.flexBasis * 2)}`),
        lineHeight: 2,
    });
    const chevron = vars("chevron", {
        width: 8,
        height: 8,
        color: mixBgAndFg(0.7),
    });

    const header = vars("header", {
        minHeight: titleBarVars.sizing.height,
    });

    const padding = vars("padding", {
        horizontal: 2,
    });

    const side = vars("side", {
        width: globalVars.icon.sizes.default + padding.horizontal,
    });

    return {
        title,
        chevron,
        header,
        padding,
        side,
    };
});

export const mobileDropDownClasses = useThemeCache(() => {
    const vars = mobileDropDownVariables();
    const globalVars = globalVariables();
    const frameVars = frameVariables();
    const titleBarVars = titleBarVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("mobileDropDown");

    const root = style({
        ...flex.middle(),
        position: "relative",
        flexGrow: 1,
    });

    const modal = style("modal", {
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        alignItems: "flex-start",
        $nest: {
            ".siteNav": {
                paddingLeft: px(globalVars.gutter.half),
            },
            "&.modal": {
                borderTopLeftRadius: 0,
                borderTopRightRadius: 0,
            },
        },
    });

    const panel = style("panel", {
        position: "relative",
        maxHeight: percent(100),
        padding: px(0),
    });

    const toggleButton = style(
        "toggleButton",
        {
            ...flex.middle(),
            ...userSelect(),
            flexGrow: 1,
            maxWidth: calc(`100% - ${px(globalVars.spacer.size)}`),
            marginLeft: px(globalVars.spacer.size / 2),
            marginRight: px(globalVars.spacer.size / 2),
            outline: 0,
        },
        mediaQueries.xs({
            maxWidth: percent(100),
            margin: 0,
            padding: px(0),
        }),
    );

    const buttonContents = style("buttonContents", {
        display: "inline-block",
        position: "relative",
        paddingRight: vars.chevron.width * 2,
        lineHeight: 1.5,
        overflow: "hidden",
        textOverflow: "ellipsis",
        maxWidth: percent(100),
    });

    const title = style(
        "title",
        {
            display: "inline",
            letterSpacing: vars.title.letterSpacing,
            fontWeight: globalVars.fonts.weights.semiBold,
            textAlign: "center",
            lineHeight: vars.title.lineHeight,
        },
        mediaQueries.xs({
            textAlign: "left",
        }),
    );

    const icon = style("icon", {
        position: "absolute",
        display: "block",
        top: 0,
        right: 0,
        bottom: 0,
        maxHeight: percent(100),
        maxWidth: percent(100),
        margin: `auto 0`,
        height: vars.chevron.height,
        width: vars.chevron.width,
    });

    const closeModalIcon = style("closeModalIcon", {
        padding: px(0),
        margin: "auto",
        color: vars.chevron.color.toString(),
        $nest: {
            "&:hover": {
                color: colorOut(globalVars.mainColors.primary),
            },
            "&:active": { color: colorOut(globalVars.mainColors.primary) },
            "&:focus": { color: colorOut(globalVars.mainColors.primary) },
        },
    });

    const closeModal = style("closeModal", {
        width: percent(100),
        height: percent(100),
    });

    const header = style("header", {
        borderBottom: singleBorder(),
    });

    const headerContent = style("headerContent", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        height: unit(vars.header.minHeight - globalVars.border.width * 6),
        margin: "auto",
        width: percent(100),
    });

    const closeWidth =
        Math.floor(globalVars.icon.sizes.xSmall) + 2 * (globalVars.gutter.half + globalVars.gutter.quarter);
    const closeButton = style("closeButton", {
        ...absolutePosition.middleLeftOfParent(),
        height: unit(closeWidth),
        width: unit(closeWidth),
        minWidth: unit(closeWidth),
        padding: 0,
        transform: translateX("-50%"),
    });

    const subTitle = style("subTitle", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        textTransform: "uppercase",
        minHeight: unit(titleBarVars.sizing.height - 4),
        fontSize: unit(globalVars.fonts.size.small),
        textOverflow: "ellipsis",
        ...paddings({
            vertical: unit(4),
        }),
        ...fonts({
            size: globalVars.fonts.size.small,
            transform: "uppercase",
            color: globalVars.mixBgAndFg(0.6),
        }),
    });

    const listContainer = style("listContainer", {
        borderBottom: singleBorder(),
    });

    return {
        root,
        modal,
        panel,
        toggleButton,
        buttonContents,
        closeButton,
        title,
        icon,
        closeModalIcon,
        closeModal,
        header,
        headerContent,
        listContainer,
        subTitle,
    };
});
