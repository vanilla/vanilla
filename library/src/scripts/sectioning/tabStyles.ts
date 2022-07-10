/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { negative, sticky, extendItemContainer, flexHelper, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { userSelect } from "@library/styles/styleHelpers";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, viewHeight, calc, quote, color } from "csx";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { css, CSSObject } from "@emotion/css";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";

interface IListOptions {
    includeBorder?: boolean;
    isLegacy?: boolean;
}

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
    const mediaQueries = oneColumnVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();

    const root = useThemeCache(() =>
        style(
            {
                display: "flex",
                flexDirection: "column",
                justifyContent: "stretch",
                height: calc(`100% - ${styleUnit(vars.navHeight.height)}`),
            },
            mediaQueries.oneColumnDown({
                height: calc(`100% - ${styleUnit(titleBarVars.sizing.mobile.height)}`),
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

    const tabList = useThemeCache((options?: IListOptions) =>
        style("tabList", {
            display: "flex",
            justifyContent: "space-between",
            alignItems: "stretch",
            background: ColorsUtils.colorOut(vars.colors.bg),
            ...sticky(),
            top: 0,
            zIndex: 1,
            // Offset for the outer borders.
            ...{
                "button:first-child": {
                    borderLeft: 0,
                },
                "button:last-child": {
                    borderRight: 0,
                },
            },
            ...(options?.isLegacy
                ? {
                      width: `calc(100% + 36px)`,
                      marginLeft: "-18px",
                  }
                : undefined),
        }),
    );

    const tab = useThemeCache((largeTabs?: boolean, legacyButton?: boolean) =>
        style(
            "tab",
            {
                ...userSelect(),
                position: "relative",
                flex: 1,
                fontWeight: globalVars.fonts.weights.semiBold,
                textAlign: "center",
                border: singleBorder({ color: color("#bfcbd8") }),
                borderTop: legacyButton ? "none" : undefined,
                padding: "2px 0",
                color: ColorsUtils.colorOut(vars.colors.fg),
                backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
                minHeight: styleUnit(28),
                fontSize: styleUnit(13),
                transition: "color 0.3s ease",
                ...flexHelper().middle(),
                ...{
                    "& > *": {
                        ...Mixins.padding({ horizontal: globalVars.gutter.half }),
                    },
                    "& + &": {
                        marginLeft: styleUnit(negative(vars.border.width)),
                    },
                    "&[data-selected]": {
                        background: ColorsUtils.colorOut(globalVars.elementaryColors.white),
                    },
                    "&:hover, &:focus, &:active": {
                        border: singleBorder({ color: color("#bfcbd8") }),
                        borderTop: legacyButton ? "none" : undefined,
                        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                        zIndex: 1,
                    },
                    "&&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&[disabled]": {
                        pointerEvents: "initial",
                        color: ColorsUtils.colorOut(vars.colors.fg),
                        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
                    },
                },
            },

            mediaQueries.oneColumnDown({
                ...{
                    label: {
                        minHeight: styleUnit(formElementVariables.sizing.height),
                        lineHeight: styleUnit(formElementVariables.sizing.height),
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

    const panel = useThemeCache(() =>
        style("panel", {
            flexGrow: 1,
            height: percent(100),
            flexDirection: "column",
        }),
    );

    const isActive = style("isActive", {
        backgroundColor: ColorsUtils.colorOut(
            ColorsUtils.modifyColorBasedOnLightness({
                color: vars.colors.bg,
                weight: 0.65,
                inverse: true,
                flipWeightForDark: true,
            }),
        ),
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
    const mediaQueries = oneColumnVariables().mediaQueries();

    const horizontalPadding = 12;
    const verticalPadding = globalVars.gutter.size / 2;
    const activeStyles: CSSObject = {
        "::before": {
            content: quote(""),
            display: "block",
            position: "absolute",
            bottom: 0,
            ...Mixins.margin({
                vertical: 0,
                horizontal: "auto",
            }),
            height: vars.activeIndicator.height,
            backgroundColor: ColorsUtils.colorOut(vars.activeIndicator.color),
            width: calc(`${percent(100)} - ${horizontalPadding * 2}px`),
        },
    };

    const root = useThemeCache((extend?: boolean) =>
        style({
            ...(extend ? extendItemContainer(horizontalPadding) : {}),
        }),
    );
    const tabPanels = style("tabPanels", {});

    const tabList = useThemeCache((options?: IListOptions) =>
        style(
            "tabList",
            {
                display: "flex",
                flexWrap: "nowrap",
                alignItems: "center",
                borderBottom: options?.includeBorder
                    ? singleBorder({ color: globalVars.separator.color, width: globalVars.separator.size })
                    : undefined,
            },

            mediaQueries.oneColumnDown({
                flexWrap: "wrap",
            }),
        ),
    );

    const tab = useThemeCache((largeTabs?: boolean, legacyButton?: boolean) =>
        style("tab", {
            position: "relative",
            ...buttonResetMixin(),
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars(largeTabs ? "large" : "small", "bold"),
                transform: largeTabs ? "inherit" : "uppercase",
            }),
            ...Mixins.padding({
                vertical: verticalPadding,
                horizontal: horizontalPadding,
            }),
            ...Mixins.margin({
                right: horizontalPadding,
                bottom: "-1px",
            }),
            ...{
                "&:active": activeStyles,
            },
        }),
    );

    const panel = useThemeCache((options?: { includeVerticalPadding?: boolean }) =>
        style("panel", {
            ...Mixins.padding({
                vertical: options?.includeVerticalPadding ? "24px" : 0,
                horizontal: horizontalPadding,
            }),
        }),
    );

    const extraButtons = style(
        "extraButtons",
        {
            ...Mixins.padding({
                horizontal: horizontalPadding / 2,
            }),
            flex: "1 0 auto",
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-end",
        },
        mediaQueries.oneColumnDown({
            borderTop: singleBorder(),
            width: "100%",
            flex: "1 0 auto",
            justifyContent: "flex-start",
            ...Mixins.padding({
                vertical: verticalPadding,
                horizontal: horizontalPadding, // For proper alignment.
            }),
        }),
    );

    const isActive = style("isActive", activeStyles);

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

export const tabGroupClasses = useThemeCache(() => {
    const vars = tabsVariables();
    const style = styleFactory(TabsTypes.GROUP);
    const mediaQueries = oneColumnVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();

    const root = useThemeCache(() =>
        style(
            {
                display: "flex",
                flexDirection: "column",
                justifyContent: "stretch",
                height: calc(`100% - ${styleUnit(vars.navHeight.height)}`),
            },
            mediaQueries.oneColumnDown({
                height: calc(`100% - ${styleUnit(titleBarVars.sizing.mobile.height)}`),
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

    const tabList = useThemeCache((options?: IListOptions) =>
        style("tabList", {
            display: "flex",
            justifyContent: "space-between",
            alignItems: "stretch",
            background: ColorsUtils.colorOut(vars.colors.bg),
            ...sticky(),
            top: 0,
            zIndex: 1,
            // Rounded corners for the outer borders.
            ...{
                "button:first-child": {
                    borderTopLeftRadius: globalVars.border.radius,
                    borderBottomLeftRadius: globalVars.border.radius,
                },
                "button:last-child": {
                    borderTopRightRadius: globalVars.border.radius,
                    borderBottomRightRadius: globalVars.border.radius,
                },
            },
            ...(options?.isLegacy
                ? {
                      width: `calc(100% + 36px)`,
                      marginLeft: "-18px",
                  }
                : undefined),
        }),
    );

    const tab = useThemeCache((largeTabs?: boolean, legacyButton?: boolean) =>
        style(
            "tab",
            {
                ...userSelect(),
                position: "relative",
                cursor: "pointer",
                flex: 1,
                fontWeight: globalVars.fonts.weights.semiBold,
                textAlign: "center",
                border: singleBorder({ color: globalVars.mixBgAndFg(0.35).saturate(0.1) }),
                borderTop: legacyButton ? "none" : undefined,
                padding: "2px 0",
                color: ColorsUtils.colorOut(vars.colors.fg),
                backgroundColor: ColorsUtils.colorOut(globalVars.elementaryColors.white),
                minHeight: styleUnit(28),
                fontSize: styleUnit(13),
                transition: "color 0.3s ease",
                ...flexHelper().middle(),
                ...{
                    "& > *": {
                        ...Mixins.padding({ horizontal: globalVars.gutter.half }),
                    },
                    "& + &": {
                        marginLeft: styleUnit(negative(vars.border.width)),
                    },
                    "&[data-selected]": {
                        background: ColorsUtils.colorOut(globalVars.elementaryColors.white),
                        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                    },
                    "&[data-selected] + *": {
                        borderLeftColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                    },
                    "&:hover:not(&[data-selected]), &:focus:not(&[data-selected]), &:active:not(&[data-selected])": {
                        border: singleBorder({ color: color("#bfcbd8") }),
                        borderTop: legacyButton ? "none" : undefined,
                        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                        zIndex: 1,
                    },
                    "&&:not(.focus-visible)": {
                        outline: 0,
                    },
                    "&[disabled]": {
                        pointerEvents: "initial",
                        color: ColorsUtils.colorOut(vars.colors.fg),
                        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
                    },
                },
            },

            mediaQueries.oneColumnDown({
                ...{
                    label: {
                        minHeight: styleUnit(formElementVariables.sizing.height),
                        lineHeight: styleUnit(formElementVariables.sizing.height),
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

    const panel = useThemeCache(() =>
        style("panel", {
            flexGrow: 1,
            height: percent(100),
            flexDirection: "column",
        }),
    );

    const isActive = style("isActive", {
        backgroundColor: ColorsUtils.colorOut(
            ColorsUtils.modifyColorBasedOnLightness({
                color: vars.colors.bg,
                weight: 0.65,
                inverse: true,
                flipWeightForDark: true,
            }),
        ),
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
