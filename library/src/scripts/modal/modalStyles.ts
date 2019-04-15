/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, colorOut, margins, unit, flexHelper, sticky } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { important, percent, viewHeight, viewWidth, calc, translateY } from "csx";
import { layoutVariables } from "@library/layout/layoutStyles";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";

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
        large: 720,
        medium: 516,
        small: 375,
    });

    const spacing = makeThemeVars("spacing", {
        horizontalMargin: 16,
    });

    const border = makeThemeVars("border", {
        radius: globalVars.border.radius,
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

    return {
        colors,
        sizing,
        spacing,
        border,
        dropDown,
        header,
        footer,
    };
});

export const modalClasses = useThemeCache(() => {
    const vars = modalVariables();
    const globalVars = globalVariables();
    const style = styleFactory("modal");
    const mediaQueries = layoutVariables().mediaQueries();
    const shadows = shadowHelper();
    const headerVars = vanillaHeaderVariables();

    const overlay = style("overlay", flexHelper().middle(), {
        position: "fixed",
        height: percent(100),
        width: viewWidth(100),
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: vars.colors.overlayBg.toString(),
        zIndex: 10,
    });

    const root = style({
        display: "block",
        // position: "relative",
        maxHeight: percent(90),
        zIndex: 1,
        backgroundColor: colorOut(vars.colors.bg),
        boxSizing: "border-box",
        position: "fixed",
        top: percent(50),
        left: 0,
        right: 0,
        bottom: "initial",
        overflow: "hidden",
        transform: translateY(`-50%`),
        ...margins({ all: "auto" }),
        $nest: {
            "&&.isFullScreen": {
                width: percent(100),
                height: percent(100),
                maxHeight: percent(100),
                borderRadius: 0,
                border: "none",
                top: 0,
                bottom: 0,
                transform: "none",
            },
            "&.isLarge": {
                maxWidth: unit(vars.sizing.large),
                left: vars.spacing.horizontalMargin,
                right: vars.spacing.horizontalMargin,
            },
            "&.isMedium": {
                maxWidth: unit(vars.sizing.medium),
                left: vars.spacing.horizontalMargin,
                right: vars.spacing.horizontalMargin,
            },
            "&.isSmall": {
                maxWidth: unit(vars.sizing.small),
                left: vars.spacing.horizontalMargin,
                right: vars.spacing.horizontalMargin,
            },
            "&&&.isSidePanel": {
                left: unit(vars.dropDown.padding),
                width: calc(`100% - ${vars.dropDown.padding}px`),
                display: "flex",
                flexDirection: "column",
                top: 0,
                bottom: 0,
                transform: "none",
            },
            "&.isDropDown": {
                overflow: "auto",
                width: percent(100),
                marginBottom: "auto",
            },
            "&.isShadowed": {
                ...shadows.dropDown(),
                ...borders(),
            },
        },
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
            height: unit(headerVars.sizing.height),
            minHeight: unit(headerVars.sizing.height),
            zIndex: 2,
            background: colorOut(vars.colors.bg),
            $nest: {
                "&.noShadow": {
                    boxShadow: "none",
                },
            },
        },
        mediaQueries.oneColumn({
            minHeight: unit(headerVars.sizing.mobile.height),
        }),
    );

    return {
        root,
        scroll,
        content,
        pageHeader,
        overlay,
    };
});
