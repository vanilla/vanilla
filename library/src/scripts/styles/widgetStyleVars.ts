/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const widgetVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("widget");

    const spacing = makeThemeVars("spacing", {
        inner: {
            verticalPadding: globalVars.gutter.half,
            horizontalPadding: globalVars.gutter.quarter,
        },
    });

    const section = makeThemeVars("section", {
        gap: globalVars.gutter.half,
    });

    const color = makeThemeVars("color", {
        bg: globalVars.elementaryColors.transparent,
        fg: globalVars.mainColors.fg,
    });

    const border = makeThemeVars("border", {
        color: globalVars.border.color,
        width: globalVars.border.width,
        radius: globalVars.border.radius,
    });

    const title = makeThemeVars("title", {
        size: globalVars.fonts.size.subTitle,
    });

    const subTitle = makeThemeVars("subTitle", {
        size: globalVars.fonts.size.large,
    });

    const text = makeThemeVars("text", {
        size: globalVars.fonts.size.medium,
    });

    const contents = makeThemeVars("contents", {
        bg: color.bg,
    });

    return {
        spacing,
        section,
        color,
        border,
        title,
        subTitle,
        text,
        contents,
    };
});
