/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import {
    borders,
    colorOut,
    fullSizeOfParent,
    margins,
    sticky,
    unit,
    absolutePosition,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent, translate, translateX, viewHeight } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

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
    const globalVars = globalVariables();
    const vars = modalVariables();
    const style = styleFactory("modal");
    const mediaQueries = layoutVariables().mediaQueries();
    const shadows = shadowHelper();
    const titleBarVars = titleBarVariables();

    const overlay = style("overlay", {
        position: "fixed",
        // Viewport units are useful here because
        // we're actually fine this being taller than the initially visible viewport.
        height: viewHeight(100),
        width: percent(100),
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: colorOut(vars.colors.overlayBg),
        zIndex: 10,
    });

    const root = style({
        display: "flex",
        flexDirection: "column",
        width: percent(100),
        maxWidth: percent(100),
        maxHeight: viewHeight(80),
        zIndex: 1,
        backgroundColor: colorOut(vars.colors.bg),
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
            "&&&.isSidePanel": {
                left: unit(vars.dropDown.padding),
                width: calc(`100% - ${unit(vars.dropDown.padding)}`),
                display: "flex",
                flexDirection: "column",
                top: 0,
                bottom: 0,
                right: 0,
                transform: "none",
                borderTopRightRadius: 0,
                borderBottomRightRadius: 0,
            },
            "&&.isDropDown": {
                top: 0,
                left: 0,
                right: 0,
                bottom: globalVars.gutter.size,
                width: percent(100),
                marginBottom: "auto",
                transform: "none",
                maxHeight: percent(100),
                borderTopLeftRadius: 0,
                borderTopRightRadius: 0,
                border: "none",
            },
            "&.isShadowed": {
                ...shadows.dropDown(),
                ...borders(),
            },
        },
    } as NestedCSSProperties);

    const scroll = style("scroll", {
        // ...absolutePosition.fullSizeOfParent(),
        width: percent(100),
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

    return {
        root,
        scroll,
        content,
        pageHeader,
        overlay,
    };
});
