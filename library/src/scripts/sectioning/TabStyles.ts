/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { colorOut, unit, fonts, paddings, borders, negative, srOnly, IFont } from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, viewHeight } from "csx";

export const tabsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVars = variableFactory("onlineTabs");

    const colors = makeVars("colors", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        state: {
            border: {
                color: globalVars.mixPrimaryAndBg(0.5),
            },
            fg: globalVars.mainColors.primary,
        },
        selected: {
            bg: globalVars.mainColors.primary.desaturate(0.3).fade(0.05),
            fg: globalVars.mainColors.fg,
        },
    });

    const border = makeVars("border", {
        width: globalVars.border.width,
        color: globalVars.border.color,
        radius: globalVars.border.radius,
        style: globalVars.border.style,
        active: {
            color: globalVars.mixPrimaryAndBg(0.5),
        },
    });

    return {
        colors,
        border,
    };
});

export const tabClasses = useThemeCache(() => {
    const vars = tabsVariables();
    const style = styleFactory("tabs");
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();

    const root = style({
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
        height: viewHeight(90),
    });

    const tabsHandles = style("tabsHandles", {
        display: "flex",
        position: "relative",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "stretch",
        width: "100%",
    });

    const tabList = style("tabList", {
        display: "flex",
        width: percent(100),
        justifyContent: "space-between",
        alignItems: "stretch",
    });
    const tab = style(
        "tab",
        {
            ...userSelect(),
            position: "relative",
            width: percent(25),
            fontWeight: globalVars.fonts.weights.semiBold,
            textAlign: "center",
            borderRight: "1px solid #bfcbd8",
            padding: "2px 0",
            color: colorOut("#48576a"),
            backgroundColor: colorOut("#f5f6f7"),
            minHeight: unit(28),
            fontSize: unit(13),
            transition: "color 0.3s ease",
            $nest: {
                "& + &": {
                    marginLeft: unit(negative(vars.border.width)),
                },
                "&:hover, &:focus, &:active": {
                    border: "1px solid #bfcbd8",
                    color: colorOut(globalVars.mainColors.primary),
                    zIndex: 1,
                },
                "&&:not(.focus-visible)": {
                    outline: 0,
                },
            },
        },

        mediaQueries.oneColumnDown({
            flexGrow: 0,
            $nest: {
                label: {
                    minHeight: unit(formElementVariables.sizing.height),
                    lineHeight: unit(formElementVariables.sizing.height),
                },
            },
        }),
    );

    const tabPanels = style("tab", {
        flexGrow: 1,
        height: percent(100),
        flexDirection: "column",
    });

    const panel = style("panel", {
        flexGrow: 1,
        height: percent(100),
        flexDirection: "column",
    });

    const isActive = style("isActive", {
        backgroundColor: colorOut(globalVars.elementaryColors.white),
    });

    return {
        root,
        tabsHandles,
        tabList,
        tab,
        tabPanels,
        panel,
        isActive,
    };
});
