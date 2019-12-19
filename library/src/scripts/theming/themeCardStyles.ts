/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px, calc, quote } from "csx";
import {
    absolutePosition,
    colorOut,
    flexHelper,
    margins,
    negative,
    paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const themeCardVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themePreviewCard");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    });

    const container = makeThemeVars("subCommunityTile", {
        width: 316,
        height: 231,
    });
    const header = makeThemeVars("subCommunityTile", {
        height: 45,
    });
    const titlebar = makeThemeVars("subCommunityTile", {
        height: 16,
        padding: {
            vertical: 0,
            horizontal: 10,
        },
    });
    const titleBarNav = makeThemeVars("subCommunityTile", {
        width: percent(100),
        padding: {
            vertical: 25,
            horizontal: 8,
        },
    });

    const titleBarLinks = makeThemeVars("subCommunityTile", {
        width: 20,
        height: 2,
        margin: {
            right: 12,
        },
    });

    const subCommunityContent = makeThemeVars("subCommunityTile", {
        maxWidth: 217,
        padding: 16,
        width: percent(100),
    });
    const subCommunityListItem = makeThemeVars("subCommunityTile", {
        width: 86,
        height: 67,
    });
    const subCommunityTile = makeThemeVars("subCommunityTile", {
        padding: 4,
        margin: {
            bottom: 10,
        },
        borderRadius: 2,
    });

    const tileImg = makeThemeVars("subCommunityTile", {
        borderRadius: unit(13),
        width: unit(20),
        height: unit(20),
        margin: {
            bottom: 2,
        },
    });

    const tileHeader = makeThemeVars("subCommunityTile", {
        height: 5,
        width: 12,
    });

    const tileText = makeThemeVars("subCommunityTile", {
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
        subCommunityContent,
        subCommunityListItem,
        subCommunityTile,
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
                position: "absolute",
                [`& .${actionButtons}`]: {
                    opacity: 1,
                },
                [`& .${wrapper}`]: {
                    backgroundColor: colorOut(vars.colors.fg),
                    opacity: 0.3,
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

    const subCommunityContent = style("subCommunityContent", {
        margin: "auto",
        maxWidth: unit(vars.subCommunityContent.maxWidth),
        padding: unit(vars.subCommunityContent.padding),
        width: vars.subCommunityContent.width,
    });
    const subCommunityTile = style("subCommunityTile", {
        alignItems: "center",
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
        padding: unit(vars.subCommunityTile.padding),
        userSelect: "none",
        width: percent(100),
        marginBottom: unit(vars.subCommunityTile.margin.bottom),
        borderRadius: unit(vars.subCommunityTile.borderRadius),
    });

    const subCommunityListItem = style("subCommunityListItem", {
        width: unit(vars.subCommunityListItem.width),
        height: unit(vars.subCommunityListItem.height),
        alignItems: "center",
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
    });
    const subCommunityList = style("subCommunityList", {
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
        marginBottom: unit(vars.subCommunityTile.margin.bottom),
    });

    return {
        container,
        titlebar,
        header,
        titleBarNav,
        titleBarLinks,
        subCommunityContent,
        subCommunityList,
        subCommunityListItem,
        subCommunityTile,
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
