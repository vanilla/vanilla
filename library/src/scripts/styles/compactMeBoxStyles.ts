/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, unit, componentThemeVariables, debugHelper, flexHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc, percent, px } from "csx";

export function compactMeBoxVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "compactMeBox");

    const tab = {
        height: 44,
        width: 44,
        ...themeVars.subComponentStyles("something"),
    };

    return { tab };
}

export function compactMeBoxClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = compactMeBoxVariables(theme);
    const debug = debugHelper("compactMeBox");

    const root = style({
        display: "block",
        ...debug.name(),
    });

    const openButton = style({
        color: globalVars.elementaryColors.white.toString(),
        ...debug.name("openButton"),
    });

    const contents = style({
        position: "relative",
        display: "flex",
        flexDirection: "column",
        height: percent(100),
        ...debug.name("contents"),
    });

    const closeModal = style({
        ...debug.name("closeModal"),
        $nest: {
            "&&": {
                ...absolutePosition.topRight(),
                width: unit(vars.tab.width),
                height: unit(vars.tab.height),
            },
        },
    });

    const tabList = style({
        marginRight: unit(vars.tab.width),
        height: unit(vars.tab.height),
        flexBasis: unit(vars.tab.width),
        color: globalVars.mainColors.fg.toString(),
        ...debug.name("tabList"),
    });

    const tabButtonContent = style({
        ...flexHelper().middle(),
        position: "relative",
        width: unit(vars.tab.width),
        height: unit(vars.tab.height),
        ...debug.name("tabButtonContent"),
    });

    const tabPanels = style({
        height: calc(`100vh - ${vars.tab.height}`),
        borderTop: `1px solid ${globalVars.overlay.border.color.toString()}`,
        ...debug.name("tabPanels"),
    });

    const tabButton = style({
        ...flexHelper().middle(),
        ...debug.name("tabButton"),
    });

    const panel = style({
        flexGrow: 1,
        borderTop: 0,
        borderRadius: 0,
        ...debug.name("panel"),
    });

    const body = style({
        flexGrow: 1,
        ...debug.name("body"),
    });

    return { root, openButton, contents, closeModal, tabList, tabPanels, tabButton, tabButtonContent, panel, body };
}
