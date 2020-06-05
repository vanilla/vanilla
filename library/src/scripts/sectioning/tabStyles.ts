/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    colorOut,
    unit,
    paddings,
    negative,
    sticky,
    extendItemContainer,
    flexHelper,
    modifyColorBasedOnLightness,
    margins,
    singleBorder,
} from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, viewHeight, calc, quote } from "csx";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { NestedCSSProperties } from "typestyle/lib/types";
import { buttonResetMixin } from "@library/forms/buttonStyles";

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

    const border = makeVars("border", {
        width: globalVars.border.width,
        color: globalVars.border.color,
        radius: globalVars.border.radius,
        style: globalVars.border.style,
        active: {
            color: globalVars.mixPrimaryAndBg(0.5),
        },
    });

    const navHeight = makeVars("navHeight", {
        height: titlebarVars.sizing.height + 2 * globalVars.border.width,
    });

    const activeIndicator = makeVars("activeIndicator", {
        height: 3,
        color: globalVars.mainColors.primary,
    });

    return {
        colors,
        border,
        navHeight,
        activeIndicator,
    };
});

export const tabStandardClasses = useThemeCache(() => {
    const vars = tabsVariables();
    const style = styleFactory(TabsTypes.STANDARD);
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();

    const root = useThemeCache(() =>
        style(
            {
                display: "flex",
                flexDirection: "column",
                justifyContent: "stretch",
                height: calc(`100% - ${unit(vars.navHeight.height)}`),
            },
            mediaQueries.oneColumnDown({
                height: calc(`100% - ${unit(titleBarVars.sizing.mobile.height)}`),
            }),
        ),
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
        justifyContent: "space-between",
        alignItems: "stretch",
        background: colorOut(vars.colors.bg),
        ...sticky(),
        top: 0,
        zIndex: 1,
        // Offset for the outer borders.
        $nest: {
            "button:first-child": {
                borderLeft: 0,
            },
            "button:last-child": {
                borderRight: 0,
            },
        },
    });

    const tab = useThemeCache(() =>
        style(
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
        ),
    );

    const tabPanels = style("tabPanels", {
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

    const extraButtons = style("extraButtons", {});

    return {
        root,
        tabsHandles,
        tabList,
        tab,
        tabPanels,
        panel,
        isActive,
        extraButtons,
    };
});

export const tabBrowseClasses = useThemeCache(() => {
    const vars = tabsVariables();
    const globalVars = globalVariables();
    const style = styleFactory(TabsTypes.BROWSE);
    const mediaQueries = layoutVariables().mediaQueries();

    const horizontalPadding = 12;
    const verticalPadding = globalVars.gutter.size / 2;
    const activeStyles = {
        "&::before": {
            content: quote(""),
            display: "block",
            position: "absolute",
            bottom: 0,
            ...margins({
                vertical: 0,
                horizontal: "auto",
            }),
            height: vars.activeIndicator.height,
            backgroundColor: colorOut(vars.activeIndicator.color),
            width: calc(`${percent(100)} - ${horizontalPadding * 2}px`),
        },
    };

    const root = useThemeCache((extend?: boolean) =>
        style({
            ...(extend ? extendItemContainer(horizontalPadding) : {}),
        }),
    );
    const tabPanels = style("tabPanels", {});

    const tabList = style("tabList", {
        display: "flex",
        flexWrap: "wrap",
        borderBottom: singleBorder({ color: globalVars.separator.color, width: globalVars.separator.size }),
    });

    const tab = useThemeCache((largeTabs?: boolean) =>
        style("tab", {
            ...buttonResetMixin(),
            textTransform: largeTabs ? "inherit" : "uppercase",
            fontSize: largeTabs ? globalVars.fonts.size.large : globalVars.fonts.size.small,
            fontWeight: globalVars.fonts.weights.bold,
            position: "relative",
            ...paddings({
                vertical: verticalPadding,
                horizontal: horizontalPadding,
            }),
            ...margins({
                right: horizontalPadding,
                bottom: "-1px",
            }),
            $nest: {
                "&:active": activeStyles as NestedCSSProperties,
            },
        }),
    );

    const panel = style("panel", {
        ...paddings({
            vertical: "24px",
            horizontal: horizontalPadding,
        }),
    });

    const extraButtons = style(
        "extraButtons",
        {
            ...paddings({ horizontal: horizontalPadding, vertical: verticalPadding }),
            flex: 1,
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-end",
        },
        mediaQueries.oneColumnDown({
            width: "100%",
            flex: "1 0 auto",
            justifyContent: "flex-start",
        }),
    );

    const isActive = style("isActive", activeStyles as NestedCSSProperties);

    return {
        root,
        tab,
        tabPanels,
        tabList,
        panel,
        isActive,
        extraButtons,
    };
});
