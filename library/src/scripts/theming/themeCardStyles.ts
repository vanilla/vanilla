/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, color, rgba } from "csx";
import { unit, paddings, defaultTransition } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const themeCardVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themePreviewCard");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: color("#adb2bb"),
        imgColor: color("#0291db"),
        bg: {
            white: color("#fff"),
            overlay: rgba(103, 105, 109, 0.8),
        },
    });

    const container = makeThemeVars("container", {
        width: 310,
        height: 225,
    });
    const header = makeThemeVars("header", {
        height: 61,
        padding: {
            top: 25,
            right: 38,
            bottom: 11,
            left: 38,
        },
    });

    const title = makeThemeVars("title", {
        width: 110,
        margin: {
            bottom: 10,
        },
    });

    const titlebar = makeThemeVars("titlebar", {
        height: 10,
        padding: {
            top: 0,
            horizontal: 10,
        },
    });
    const titleBarNav = makeThemeVars("titleBarNav", {
        width: percent(100),
        padding: {
            vertical: 25,
            horizontal: 4,
        },
    });

    const titleBarLinks = makeThemeVars("titleBarLinks", {
        width: 16,
        height: 2,
        margin: {
            right: 7,
        },
    });

    const bar = makeThemeVars("bar", {
        width: 120,
        height: 9,
    });

    const search_btn = makeThemeVars("search_btn", {
        width: 24,
        height: 9,
    });
    const content = makeThemeVars("content", {
        maxWidth: 217,
        padding: {
            top: 10,
        },
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
        borderRadius: 13,
        width: 20,
        height: 20,
        margin: {
            bottom: 5,
        },
    });

    const tileHeader = makeThemeVars("tileHeader", {
        height: 4,
        width: 27,
    });

    const tileText = makeThemeVars("tileText", {
        width: 85,
        height: 2,
        backgroundColor: "#adb2bb",
        margin: {
            bottom: 2,
        },
    });

    const dots = makeThemeVars("dots", {
        width: 4,
        height: 4,
    });

    return {
        colors,
        container,
        header,
        title,
        titlebar,
        titleBarNav,
        titleBarLinks,
        bar,
        search_btn,
        content,
        contentList,
        contentTile,
        tileImg,
        tileHeader,
        tileText,
        dots,
    };
});

export const themeCardClasses = useThemeCache(() => {
    const vars = themeCardVariables();
    const style = styleFactory("themePreviewCard");

    const menuBar = style("menuBar", {
        height: unit(vars.titlebar.height),
        display: "flex",
        paddingTop: unit(vars.titlebar.padding.top + 2),
        paddingLeft: unit(vars.titlebar.padding.horizontal - 3),
    });

    const dots = style("dots", {
        height: unit(vars.dots.height),
        width: unit(vars.dots.width),
        backgroundColor: "#bbb",
        borderRadius: percent(50),
        marginRight: unit(3),
    });
    const actionButtons = style("actionButtons", {
        textAlign: "center",
        margin: "44px 0",
    });
    const overlay = style("overlay", {
        position: "absolute",
        top: 0,
        backgroundColor: "rgba(103, 105, 109, 0.8)",
        opacity: 0,
    });
    const wrapper = style("wrapper", {
        ...defaultTransition("transform"),
    });

    const container = style("container", {
        width: unit(vars.container.width),
        height: unit(vars.container.height),
        position: "relative",
        borderRadius: unit(2),
        boxShadow: "0 1px 3px 0 rgba(85, 90, 98, 0.31)",

        $nest: {
            "&:hover": {
                [`& .${overlay}`]: {
                    opacity: 1,
                },
            },
        },
    });

    const noActions = style("noActions", {
        width: unit(vars.container.width),
        height: unit(vars.container.height),
        position: "relative",
        borderRadius: unit(2),
        boxShadow: "0 1px 3px 0 rgba(85, 90, 98, 0.31)",
        backgroundColor: vars.colors.bg.white.toString(),
    });
    const header = style("header", {
        height: unit(vars.header.height),
        ...paddings({
            top: vars.header.padding.top,
            right: vars.header.padding.right,
            bottom: vars.header.padding.bottom,
            left: vars.header.padding.left,
        }),
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const title = style("title", {
        width: unit(vars.title.width),
        height: unit(vars.tileHeader.height),
        backgroundColor: vars.colors.bg.white.toString(),
        marginBottom: unit(vars.title.margin.bottom),
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
        listStyle: "none",
    });

    const titleBarLinks = style("titleBarLinks", {
        width: unit(vars.titleBarLinks.width),
        height: unit(vars.titleBarLinks.height),
        marginRight: unit(vars.titleBarLinks.margin.right),
    });

    const content = style("content", {
        margin: "auto",
        maxWidth: unit(vars.content.maxWidth),
        paddingTop: unit(vars.content.padding.top),
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
        boxShadow: "0 1px 3px 0 rgba(85, 90, 98, 0.31)",
        backgroundColor: vars.colors.bg.white.toString(),
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
        justifyContent: "space-around",
        position: "relative",
        background: vars.colors.bg.white.toString(),
        borderRadius: unit(2),
        margin: "0 auto",
        padding: 0,
    });
    const tileImg = style("tileImg", {
        borderRadius: unit(vars.tileImg.borderRadius),
        width: unit(vars.tileImg.width),
        height: unit(vars.tileImg.height),
        marginBottom: unit(vars.tileImg.margin.bottom),
        border: `1px solid ${vars.colors.imgColor.toString()}`,
    });
    const tileHeader = style("tileHeader", {
        width: unit(vars.tileHeader.width),
        height: unit(vars.tileHeader.height),
        marginBottom: unit(vars.tileImg.margin.bottom),
        background: vars.colors.fg.toString(),
    });
    const tileContent = style("tileContent", {
        width: percent(100),
        marginBottom: unit(vars.tileImg.margin.bottom),
        display: "contents",
    });

    const search = style("search", {
        display: "flex",
    });
    const bar = style("bar", {
        background: vars.colors.bg.white.toString(),
        width: vars.bar.width,
        height: vars.bar.height,
    });
    const search_btn = style("search_btn", {
        width: unit(vars.search_btn.width),
        height: unit(vars.search_btn.height),
        borderRadius: unit(1.3),
        border: "solid 0.3px #ffffff",
        backgroundColor: "rgba(0, 0, 0, 0.1)",
    });

    const searchText = style("searchText", {
        background: vars.colors.bg.white.toString(),
        width: unit(vars.titleBarLinks.width - 2),
        height: unit(vars.titleBarLinks.height),
        alignItems: "center",
        display: "flex",
        margin: unit(vars.tileText.margin.bottom + 1),
    });
    const text1 = style("text1", {
        width: percent(vars.tileText.width),
        marginBottom: unit(vars.tileText.margin.bottom),
        height: unit(vars.tileText.height),
        background: vars.colors.fg.toString(),
    });
    const text2 = style("text2", {
        width: percent(vars.tileText.width - 5),
        marginBottom: unit(vars.tileText.margin.bottom),
        height: unit(vars.tileText.height),
        background: vars.colors.fg.toString(),
    });
    const text3 = style("text3", {
        width: percent(vars.tileText.width - 15),
        height: unit(vars.tileText.height),
        background: vars.colors.fg.toString(),
    });

    const buttons = style("buttons", {
        marginBottom: unit(vars.contentTile.margin.bottom),
        width: unit(180),
    });
    const noOverlay = style("noOverlay", {
        display: "none",
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
        search,
        bar,
        search_btn,
        text1,
        text2,
        text3,
        actionButtons,
        buttons,
        wrapper,
        noActions,
        title,
        searchText,
        dots,
        menuBar,
        overlay,
        noOverlay,
    };
});

export default themeCardClasses;
