/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "../styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "../styles/styleUtils";
import { colorOut, margins, unit } from "../styles/styleHelpers";
import { layoutVariables } from "../styles/layoutStyles";
import { percent, viewHeight } from "csx";
import { shadowHelper } from "../styles/shadowHelpers";
import { borders } from "../styles/styleHelpers";
import { vanillaHeaderVariables } from "../headers/vanillaHeaderStyles";

export const modalVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("modal");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const sizing = makeThemeVars("sizing", {
        large: 720,
        medium: 516,
        small: 375,
    });

    const spacing = makeThemeVars("spacing", {
        horizontalMargin: globalVars.spacer.size / 2,
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
    const style = styleFactory("frame");
    const mediaQueries = layoutVariables().mediaQueries();
    const shadows = shadowHelper();
    const headerVars = vanillaHeaderVariables();

    const root = style({
        position: "relative",
        maxHeight: percent(100),
        zIndex: 1,
        backgroundColor: colorOut(vars.colors.bg),
        $nest: {
            "&.isFullScreen": {
                overflow: "auto",
                width: percent(100),
                height: viewHeight(100),
                maxHeight: viewHeight(100),
                borderRadius: 0,
            },
            "&.isLarge": {
                width: unit(vars.sizing.large),
                ...margins({
                    left: vars.spacing.horizontalMargin,
                    right: vars.spacing.horizontalMargin,
                }),
            },
            "&.isMedium": {
                width: unit(vars.sizing.medium),
                ...margins({
                    left: vars.spacing.horizontalMargin,
                    right: vars.spacing.horizontalMargin,
                }),
            },
            "&.isSmall": {
                width: unit(vars.sizing.small),
                ...margins({
                    left: vars.spacing.horizontalMargin,
                    right: vars.spacing.horizontalMargin,
                }),
            },
            "&.isSidePanel": {
                marginLeft: unit(vars.dropDown.padding),
            },
            "&.isDropDown": {
                overflow: "auto",
                width: percent(100),
                marginBottom: "auto",
                maxHeight: viewHeight(100),
            },
            "&.isShadowed": {
                ...shadows.dropDown(),
                ...borders(),
            },
        },
    });

    const scroll = style("scroll", {
        overflow: "auto",
        $nest: {
            "& > .container": {
                paddingTop: unit(globalVars.overlay.fullPageHeadingSpacer),
            },
            "&.hasError > .container": {
                paddingTop: 0,
            },
        },
    });

    const content = style("content", shadows.modal());

    const pageHeader = style(
        "pageHeader",
        {
            ...shadows.embed(),
            position: "relative",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            height: unit(headerVars.sizing.height),
            minHeight: unit(headerVars.sizing.height),
            zIndex: 1,
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
    };
});
