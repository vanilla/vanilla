/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { sticky } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, translate, translateX, viewHeight } from "csx";
import { CSSObject } from "@emotion/css";
import { cssRule } from "@library/styles/styleShim";
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
        height: percent(96), // VH units cause issues on iOS.
        zIndex: 1050, // Sorry it's so high. Our dashboard uses some bootstrap which specifies 1040 for the old modals.
        // When nesting our modals on top we need to be higher.
    });

    const spacing = makeThemeVars("spacing", {
        horizontalMargin: 40,
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
        boxShadow: `0 1px 2px 0 ${ColorsUtils.colorOut(globalVars.overlay.bg)}`,
    });

    const footer = makeThemeVars("footer", {
        minHeight: header.minHeight,
        verticalPadding: header.verticalPadding,
        boxShadow: `0 -1px 2px 0 ${ColorsUtils.colorOut(globalVars.overlay.bg)}`,
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

    const overlayMixin: CSSObject = {
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
        background: ColorsUtils.colorOut(vars.colors.overlayBg),
    });

    const overlayContent = style("overlayContent", {
        ...overlayMixin,
    });

    const sidePanelMixin: CSSObject = {
        left: styleUnit(vars.dropDown.padding),
        width: calc(`100% - ${styleUnit(vars.dropDown.padding)}`),
        display: "flex",
        flexDirection: "column",
        top: 0,
        bottom: 0,
        transform: "none",
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
        maxWidth: 400,
        ...{
            [`.${dropDownClasses().action}`]: {
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    };

    const root = style({
        display: "flex",
        flexDirection: "column",
        width: percent(100),
        maxWidth: percent(100),
        maxHeight: styleUnit(vars.sizing.height),
        zIndex: 1,
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        color: ColorsUtils.colorOut(vars.colors.fg),
        position: "fixed",
        top: percent(50),
        left: percent(50),
        bottom: "initial",
        overflow: "hidden",
        borderRadius: styleUnit(vars.border.radius),
        // NOTE: This transform can cause issues if anything inside of us needs fixed positioning.
        // See http://meyerweb.com/eric/thoughts/2011/09/12/un-fixing-fixed-elements-with-css-transforms/
        // See also https://www.w3.org/TR/2009/WD-css3-2d-transforms-20091201/#introduction
        // This is why fullscreen unsets the transforms.
        transform: translate(`-50%`, `-50%`),
        ...Mixins.margin({ all: "auto" }),
        ...{
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
                width: styleUnit(vars.sizing.xl),
                height: percent(100),
                maxWidth: calc(`100% - ${styleUnit(vars.spacing.horizontalMargin)}`),
            },
            "&.isLarge": {
                width: styleUnit(vars.sizing.large),
                maxWidth: calc(`100% - ${styleUnit(vars.spacing.horizontalMargin)}`),
            },
            "&.isMedium": {
                width: styleUnit(vars.sizing.medium),
                maxWidth: calc(`100% - ${styleUnit(vars.spacing.horizontalMargin)}`),
            },
            "&.isSmall": {
                width: styleUnit(vars.sizing.small),
                maxWidth: calc(`100% - ${styleUnit(vars.spacing.horizontalMargin)}`),
            },
            "&&&.isSidePanelRight": {
                ...sidePanelMixin,
                right: 0,
                left: "initial",
            },
            "&&&.isSidePanelRightLarge": {
                ...sidePanelMixin,
                right: 0,
                left: "initial",
                width: styleUnit(vars.sizing.xl),
                maxWidth: calc(`100% - ${styleUnit(vars.spacing.horizontalMargin)}`),
                height: percent(100),
                maxHeight: percent(100),
                top: 0,
                bottom: 0,
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
                maxHeight: calc(`100% - ${styleUnit(globalVars.gutter.size)}`),
                borderTopLeftRadius: 0,
                borderTopRightRadius: 0,
                border: "none",
            },
            "&.isShadowed": {
                ...shadows.dropDown(),
                ...Mixins.border(globalVars.borderType.modals),
            },
            ".form-group": {
                marginLeft: styleUnit(-16),
                marginRight: styleUnit(-16),
            },
        },
    });

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
            height: styleUnit(titleBarVars.sizing.height),
            minHeight: styleUnit(titleBarVars.sizing.height),
            zIndex: 2,
            background: ColorsUtils.colorOut(vars.colors.bg),
            ...{
                "&.noShadow": {
                    boxShadow: "none",
                },
            },
        },
        mediaQueries.oneColumnDown({
            height: styleUnit(titleBarVars.sizing.mobile.height),
            minHeight: styleUnit(titleBarVars.sizing.mobile.height),
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
        ...{
            ".frame": {
                maxHeight: "100%",
                flex: 1,
            },
        },
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
