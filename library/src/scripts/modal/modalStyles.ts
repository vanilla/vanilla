/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { borders, colorOut, margins, sticky, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent, translate, translateX, viewHeight } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { cssRule } from "typestyle";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

export const modalVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("modal");

    const { elementaryColors } = globalVars;

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        overlayBg:
            globalVars.mainColors.fg.lightness() > 0.5
                ? elementaryColors.white.fade(0.4)
                : elementaryColors.black.fade(0.4),
    });

    const sizing = makeThemeVars("sizing", {
        xl: 1022, // from legacy back-end modals
        large: 720,
        medium: 516,
        small: 375,
        height: viewHeight(96),
        zIndex: 1050, // Sorry it's so high. Our dashboard uses some bootstrap which specifies 1040 for the old modals.
        // When nesting our modals on top we need to be higher.
    });

    const spacing = makeThemeVars("spacing", {
        horizontalMargin: 16,
    });

    const border = makeThemeVars("border", {
        radius: globalVars.borderType.modals.radius,
    });

    const dropDown = makeThemeVars("dropDown", {
        padding: globalVars.spacer.size,
    });

    const header = makeThemeVars("header", {
        minHeight: 60,
        verticalPadding: 12,
        boxShadow: `0 1px 2px 0 ${colorOut(globalVars.overlay.bg)}`,
    });

    const footer = makeThemeVars("footer", {
        minHeight: header.minHeight,
        verticalPadding: header.verticalPadding,
        boxShadow: `0 -1px 2px 0 ${colorOut(globalVars.overlay.bg)}`,
    });

    const fullScreenTitleSpacing = makeThemeVars("fullScreenModalTitle", {
        gap: 52,
    });

    return {
        colors,
        sizing,
        spacing,
        border,
        dropDown,
        header,
        footer,
        fullScreenTitleSpacing,
    };
});

export const modalClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = modalVariables();
    const style = styleFactory("modal");
    const mediaQueries = layoutVariables().mediaQueries();
    const shadows = shadowHelper();
    const titleBarVars = titleBarVariables();

    cssRule("#modals", {
        position: "relative",
        zIndex: 1050, // Sorry it's so high. Our dashboard uses some bootstrap which specifies 1040 for the old modals.
        // When nesting our modals on top we need to be higher.
    });

    const overlayMixin: NestedCSSProperties = {
        position: "fixed",
        // Viewport units are useful here because
        // we're actually fine this being taller than the initially visible viewport.
        height: viewHeight(100),
        width: percent(100),
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        zIndex: 10,
    };

    const overlayScrim = style("overlayScrim", {
        ...overlayMixin,
        background: colorOut(vars.colors.overlayBg),
    });

    const overlayContent = style("overlayContent", {
        ...overlayMixin,
    });

    const sidePanelMixin: NestedCSSProperties = {
        left: unit(vars.dropDown.padding),
        width: calc(`100% - ${unit(vars.dropDown.padding)}`),
        display: "flex",
        flexDirection: "column",
        top: 0,
        bottom: 0,
        transform: "none",
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
        maxWidth: 400,
        $nest: {
            [`& .${dropDownClasses().action}`]: {
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    };

    const root = style({
        display: "flex",
        flexDirection: "column",
        width: percent(100),
        maxWidth: percent(100),
        maxHeight: unit(vars.sizing.height),
        zIndex: 1,
        backgroundColor: colorOut(vars.colors.bg),
        color: colorOut(vars.colors.fg),
        position: "fixed",
        top: percent(50),
        left: percent(50),
        bottom: "initial",
        overflow: "hidden",
        borderRadius: unit(vars.border.radius),
        // NOTE: This transform can cause issues if anything inside of us needs fixed positioning.
        // See http://meyerweb.com/eric/thoughts/2011/09/12/un-fixing-fixed-elements-with-css-transforms/
        // See also https://www.w3.org/TR/2009/WD-css3-2d-transforms-20091201/#introduction
        // This is why fullscreen unsets the transforms.
        transform: translate(`-50%`, `-50%`),
        ...margins({ all: "auto" }),
        $nest: {
            "&&.isFullScreen": {
                width: percent(100),
                height: percent(100),
                maxHeight: percent(100),
                maxWidth: percent(100),
                borderRadius: 0,
                border: "none",
                top: 0,
                bottom: 0,
                transform: "none",
                left: 0,
                right: 0,
            },
            "&.isXL": {
                width: unit(vars.sizing.xl),
                height: percent(100),
                maxWidth: calc(`100% - ${unit(vars.spacing.horizontalMargin * 2)}`),
            },
            "&.isLarge": {
                width: unit(vars.sizing.large),
                maxWidth: calc(`100% - ${unit(vars.spacing.horizontalMargin * 2)}`),
            },
            "&.isMedium": {
                width: unit(vars.sizing.medium),
                maxWidth: calc(`100% - ${unit(vars.spacing.horizontalMargin * 2)}`),
            },
            "&.isSmall": {
                width: unit(vars.sizing.small),
                maxWidth: calc(`100% - ${unit(vars.spacing.horizontalMargin * 2)}`),
            },
            "&&&.isSidePanelRight": {
                ...sidePanelMixin,
                right: 0,
                left: "initial",
            },
            "&&&.isSidePanelLeft": {
                ...sidePanelMixin,
                left: 0,
                right: "initial",
            },
            "&&.isDropDown": {
                top: 0,
                left: 0,
                right: 0,
                width: percent(100),
                marginBottom: "auto",
                transform: "none",
                maxHeight: calc(`100% - ${unit(globalVars.gutter.size)}`),
                borderTopLeftRadius: 0,
                borderTopRightRadius: 0,
                border: "none",
            },
            "&.isShadowed": {
                ...shadows.dropDown(),
                ...borders(globalVars.borderType.modals),
            },
            "& .form-group": {
                marginLeft: unit(-16),
                marginRight: unit(-16),
            },
        },
    } as NestedCSSProperties);

    const scroll = style("scroll", {
        // ...absolutePosition.fullSizeOfParent(),
        width: percent(100),
        height: percent(100),
        maxHeight: percent(100),
        overflow: "auto",
    });

    const content = style("content", shadows.modal());

    const pageHeader = style(
        "pageHeader",
        sticky(),
        {
            ...shadows.embed(),
            top: 0,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            height: unit(titleBarVars.sizing.height),
            minHeight: unit(titleBarVars.sizing.height),
            zIndex: 2,
            background: colorOut(vars.colors.bg),
            $nest: {
                "&.noShadow": {
                    boxShadow: "none",
                },
            },
        },
        mediaQueries.oneColumnDown({
            height: unit(titleBarVars.sizing.mobile.height),
            minHeight: unit(titleBarVars.sizing.mobile.height),
        }),
    );

    const frameWrapper = style("frameWrapper", {
        position: "relative",
        display: "flex",
        flexDirection: "column",
        height: percent(100),
        maxHeight: percent(100),
        minHeight: percent(0),
        width: percent(100),
    });

    return {
        root,
        scroll,
        content,
        pageHeader,
        overlayScrim,
        overlayContent,
        frameWrapper,
    };
});
