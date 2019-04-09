/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, flexHelper, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { calc, percent } from "csx";

export const compactMeBoxVariables = useThemeCache(() => {
    const themeVars = componentThemeVariables("compactMeBox");

    const tab = {
        height: 44,
        width: 44,
        ...themeVars.subComponentStyles("something"),
    };

    return { tab };
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
        display: "flex",
        flexDirection: "column",
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

    const tabList = style("tabList", {
        marginRight: unit(vars.tab.width),
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
        height: calc(`100vh - ${unit(vars.tab.height)}`),
        overflow: "auto",
        borderTop: `1px solid ${globalVars.overlay.border.color.toString()}`,
    });

    const tabButton = style("tabButton", {
        ...flexHelper().middle(),
    });

    const panel = style("panel", {
        flexGrow: 1,
        borderTop: 0,
        borderRadius: 0,
    });

    const body = style("body", {
        flexGrow: 1,
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
    };
});
