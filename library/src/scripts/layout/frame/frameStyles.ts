/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, viewHeight } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const frameVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("frame");

    const colors = makeThemeVars("colors", {
        bg: globalVars.body.backgroundImage.color,
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
    const mediaQueries = layoutVariables().mediaQueries();

    const headerWrap = style("headerWrap", {
        background: ColorsUtils.colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });
    const bodyWrap = style("bodyWrap", {
        position: "relative",
        background: ColorsUtils.colorOut(vars.colors.bg),
        width: percent(100),
    });
    const footerWrap = style("footerWrap", {
        background: ColorsUtils.colorOut(vars.colors.bg),
        zIndex: 2,
        willChange: "height",
    });

    const root = style(
        {
            backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
            maxHeight: viewHeight(80),
            height: percent(100),
            borderRadius: styleUnit(vars.border.radius),
            width: percent(100),
            position: "relative",
            display: "flex",
            flexDirection: "column",
            minHeight: 0, // https://bugs.chromium.org/p/chromium/issues/detail?id=927066
            ...{
                [`.${bodyWrap}`]: {
                    flexGrow: 1,
                    overflowY: "auto",
                },
            },
        },
        mediaQueries.xs({
            maxHeight: percent(100),
        }),
    );

    return {
        root,
        headerWrap,
        bodyWrap,
        footerWrap,
    };
});
