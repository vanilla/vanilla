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
import { percent } from "csx";

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

    const root = style({
        display: "block",
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
        border: "solid 1px #bfcbd8",
        backgroundColor: "#f5f6f7",
    });
    const tab = style(
        "tab",
        {
            ...userSelect(),

            width: percent(25),
            textAlign: "center",
            borderRight: "1px solid #bfcbd8",
            padding: "4px 0",
            $nest: {
                "& + &": {
                    marginLeft: unit(negative(vars.border.width)),
                },
                "&:hover, &:focus, &:active": {
                    color: colorOut(vars.colors.state.fg),
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

    const panels = style;

    const isActive = style("isActive", {
        backgroundColor: colorOut(vars.colors.selected.bg),
    });

    return {
        root,
        tabsHandles,
        tabList,
        tab,
        isActive,
    };
});
