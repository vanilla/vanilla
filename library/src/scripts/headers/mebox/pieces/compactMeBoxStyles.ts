/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, flexHelper, unit, sticky, colorOut } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent, viewHeight } from "csx";

export const compactMeBoxVariables = useThemeCache(() => {
    const themeVars = variableFactory("compactMeBox");
    const globalVars = globalVariables();

    const tab = themeVars("tab", {
        height: 44,
        width: 44,
    });

    const colors = themeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    return { tab, colors };
});

export const compactMeBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = compactMeBoxVariables();
    const style = styleFactory("compactMeBox");

    const root = style({
        display: "block",
    });

    const openButton = style("openButton", {
        color: globalVars.elementaryColors.white.toString(),
    });

    const contents = style("contents", {
        position: "relative",
        height: percent(100),
    });

    const closeModal = style("closeModal", {
        $nest: {
            "&&": {
                ...absolutePosition.topRight(),
                width: unit(vars.tab.width),
                height: unit(vars.tab.height),
            },
        },
    });

    const tabList = style("tabList", sticky(), {
        top: 0,
        background: colorOut(vars.colors.bg),
        zIndex: 2,
        paddingRight: unit(vars.tab.width),
        height: unit(vars.tab.height),
        flexBasis: unit(vars.tab.width),
        color: globalVars.mainColors.fg.toString(),
    });

    const tabButtonContent = style("tabButtonContent", {
        ...flexHelper().middle(),
        position: "relative",
        width: unit(vars.tab.width),
        height: unit(vars.tab.height),
    });

    const tabPanels = style("tabPanels", {
        height: calc(`100% - ${vars.tab.height}px`),
        position: "relative",
    });

    const tabButton = style("tabButton", {
        ...flexHelper().middle(),
    });

    const panel = style("panel", {
        maxHeight: percent(100),
        borderTop: 0,
        borderRadius: 0,
    });

    const body = style("body", {
        flexGrow: 1,
    });

    const scrollContainer = style("scrollContainer", {
        overflow: "auto",
    });

    return {
        root,
        openButton,
        contents,
        closeModal,
        tabList,
        tabPanels,
        tabButton,
        tabButtonContent,
        panel,
        body,
        scrollContainer,
    };
});
