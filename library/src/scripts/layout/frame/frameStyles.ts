/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";

export const frameVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("frame");

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    });

    const sizing = makeThemeVars("sizing", {
        large: 720,
        medium: 516,
        small: 375,
    });

    const border = makeThemeVars("border", {
        radius: globalVars.border.radius,
    });

    const spacing = makeThemeVars("spacing", {
        padding: 16,
    });

    const header = makeThemeVars("header", {
        spacing: spacing.padding,
        minHeight: 44,
        fontSize: globalVars.fonts.size.subTitle,
    });

    const footer = makeThemeVars("footer", {
        spacing: spacing.padding,
        minHeight: header.minHeight,
    });

    return {
        colors,
        sizing,
        border,
        spacing,
        header,
        footer,
    };
});

export const frameClasses = useThemeCache(() => {
    const vars = frameVariables();
    const style = styleFactory("frame");

    const headerWrap = style("headerWrap", {
        background: colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });
    const bodyWrap = style("bodyWrap", {
        background: colorOut(vars.colors.bg),
    });
    const footerWrap = style("footerWrap", {
        background: colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });

    const root = style({
        backgroundColor: colorOut(vars.colors.bg),
        maxHeight: percent(100),
        height: percent(100),
        borderRadius: unit(vars.border.radius),
        width: percent(100),
        position: "relative",
        display: "flex",
        flexDirection: "column",
        $nest: {
            [`.${bodyWrap}`]: {
                flex: 1,
                overflow: "auto",
            },
        },
    });

    return {
        root,
        headerWrap,
        bodyWrap,
        footerWrap,
    };
});
