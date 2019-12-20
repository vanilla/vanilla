/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const themeCardVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themePreviewCard");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
    });

    const container = makeThemeVars("container", {
        width: 316,
        height: 231,
    });
    const header = makeThemeVars("header", {
        height: 45,
    });
    const titlebar = makeThemeVars("titlebar", {
        height: 16,
        padding: {
            vertical: 0,
            horizontal: 10,
        },
    });
    const titleBarNav = makeThemeVars("titleBarNav", {
        width: percent(100),
        padding: {
            vertical: 25,
            horizontal: 8,
        },
    });

    const titleBarLinks = makeThemeVars("titleBarLinks", {
        width: 20,
        height: 2,
        margin: {
            right: 12,
        },
    });

    const content = makeThemeVars("content", {
        maxWidth: 217,
        padding: 16,
        width: percent(100),
    });
    const contentList = makeThemeVars("contentList", {
        width: 86,
        height: 67,
    });
    const contentTile = makeThemeVars("contentTile", {
        padding: 4,
        margin: {
            bottom: 10,
        },
        borderRadius: 2,
    });

    const tileImg = makeThemeVars("tileImg", {
        borderRadius: unit(13),
        width: unit(20),
        height: unit(20),
        margin: {
            bottom: 2,
        },
    });

    const tileHeader = makeThemeVars("tileHeader", {
        height: 5,
        width: 12,
    });

    const tileText = makeThemeVars("tileText", {
        width: 85,
        height: 5,
    });

    return {
        colors,
        container,
        header,
        titlebar,
        titleBarNav,
        titleBarLinks,
        content,
        contentList,
        contentTile,
        tileImg,
        tileHeader,
        tileText,
    };
});

export const themeCardClasses = useThemeCache(() => {
    const vars = themeCardVariables();
    const style = styleFactory("themePreviewCard");

    const actionButtons = style("actionButtons", {
        opacity: 0,
        position: "absolute",
        top: percent(25),
        left: percent(30),
        textAlign: "center",
        display: "flex",
        flexDirection: "column",
    });
    const wrapper = style("wrapper", {
        opacity: 1,
    });

    const container = style("container", {
        width: unit(vars.container.width),
        height: unit(vars.container.height),
        position: "relative",
        $nest: {
            "&:hover": {
                backgroundColor: colorOut(vars.colors.fg),
                opacity: 0.6,
                position: "absolute",
                [`& .${actionButtons}`]: {
                    opacity: 1,
                },
            },
        },
    });
    const header = style("header", {
        height: unit(vars.header.height),
    });
    const titlebar = style("titlebar", {
        height: unit(vars.titlebar.height),
    });

    const titleBarNav = style("titleBarNav", {
        alignItems: "center",
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "flex-start",
        width: vars.titleBarNav.width,
        paddingTop: unit(vars.titleBarNav.padding.horizontal),
        paddingLeft: unit(vars.titleBarNav.padding.vertical),
    });

    const titleBarLinks = style("titleBarLinks", {
        width: unit(vars.titleBarLinks.width),
        height: unit(vars.titleBarLinks.height),
        marginRight: unit(vars.titleBarLinks.margin.right),
    });

    const content = style("content", {
        margin: "auto",
        maxWidth: unit(vars.content.maxWidth),
        padding: unit(vars.content.padding),
        width: vars.content.width,
    });
    const contentTile = style("contentTile", {
        alignItems: "center",
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
        padding: unit(vars.contentTile.padding),
        userSelect: "none",
        width: percent(100),
        marginBottom: unit(vars.contentTile.margin.bottom),
        borderRadius: unit(vars.contentTile.borderRadius),
    });

    const contentListItem = style("contentListItem", {
        width: unit(vars.contentList.width),
        height: unit(vars.contentList.height),
        alignItems: "center",
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
    });
    const contentList = style("contentList", {
        alignItems: "stretch",
        display: "flex",
        flexWrap: "wrap",
        justifyContent: "space-between",
        position: "relative",
    });
    const tileImg = style("tileImg", {
        borderRadius: unit(vars.tileImg.borderRadius),
        width: unit(vars.tileImg.width),
        height: unit(vars.tileImg.height),
        marginBottom: unit(vars.tileImg.margin.bottom),
    });
    const tileHeader = style("tileHeader", {
        width: unit(vars.titleBarLinks.width),
        height: unit(vars.tileHeader.height),
        marginBottom: unit(vars.tileImg.margin.bottom),
    });
    const tileContent = style("tileContent", {
        width: percent(100),
        marginBottom: unit(vars.tileImg.margin.bottom),
    });
    const text1 = style("text1", {
        width: percent(vars.tileText.width),
        marginBottom: unit(vars.tileImg.margin.bottom),
        height: unit(vars.tileText.height),
    });
    const text2 = style("text2", {
        width: percent(vars.tileText.width - 5),
        marginBottom: unit(vars.tileImg.margin.bottom),
        height: unit(vars.tileText.height),
    });
    const text3 = style("text3", {
        width: percent(vars.tileText.width - 5),
        height: unit(vars.tileText.height),
    });

    const buttons = style("buttons", {
        marginBottom: unit(vars.contentTile.margin.bottom),
    });

    return {
        container,
        titlebar,
        header,
        titleBarNav,
        titleBarLinks,
        content,
        contentList,
        contentListItem,
        contentTile,
        tileImg,
        tileHeader,
        tileContent,
        text1,
        text2,
        text3,
        actionButtons,
        buttons,
        wrapper,
    };
});

export default themeCardClasses;
