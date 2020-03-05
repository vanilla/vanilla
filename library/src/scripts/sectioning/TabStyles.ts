/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    colorOut,
    unit,
    fonts,
    paddings,
    borders,
    negative,
    srOnly,
    IFont,
    sticky,
    extendItemContainer,
    flexHelper,
    modifyColorBasedOnLightness,
} from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, viewHeight, calc } from "csx";

export const tabsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const titlebarVars = titleBarVariables();
    const makeVars = variableFactory("onlineTabs");

    const colors = makeVars("colors", {
        bg: globalVars.mixBgAndFg(0.05),
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

    const navHeight = makeVars("navHeight", {
        height: titlebarVars.sizing.height,
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
        navHeight,
    };
});

export const tabClasses = useThemeCache(() => {
    const vars = tabsVariables();
    const style = styleFactory("tabs");
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();

    const root = style(
        {
            display: "flex",
            flexDirection: "column",
            justifyContent: "stretch",
            height: calc(`100% - ${unit(vars.navHeight.height)}`),
        },
        mediaQueries.oneColumnDown({
            height: calc(`100% - ${unit(titleBarVars.sizing.mobile.height)}`),
        }),
    );

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
        // Offset for the outer borders.
        ...extendItemContainer(globalVariables().border.width),
        justifyContent: "space-between",
        alignItems: "stretch",
        background: colorOut(vars.colors.bg),
        ...sticky(),
        top: 0,
        zIndex: 1,
    });

    const tab = style(
        "tab",
        {
            ...userSelect(),
            position: "relative",
            flex: 1,
            fontWeight: globalVars.fonts.weights.semiBold,
            textAlign: "center",
            border: "1px solid #bfcbd8",
            padding: "2px 0",
            color: colorOut(vars.colors.fg),
            backgroundColor: colorOut(vars.colors.bg),
            minHeight: unit(28),
            fontSize: unit(13),
            transition: "color 0.3s ease",
            ...flexHelper().middle(),
            $nest: {
                "& > *": {
                    ...paddings({ horizontal: globalVars.gutter.half }),
                },
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
                "&[disabled]": {
                    pointerEvents: "initial",
                    color: colorOut(vars.colors.fg),
                    backgroundColor: colorOut(vars.colors.bg),
                },
            },
        },

        mediaQueries.oneColumnDown({
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
        position: "relative",
    });

    const panel = style("panel", {
        flexGrow: 1,
        height: percent(100),
        flexDirection: "column",
    });

    const isActive = style("isActive", {
        backgroundColor: colorOut(modifyColorBasedOnLightness(vars.colors.bg, 0.65, true, true)),
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
