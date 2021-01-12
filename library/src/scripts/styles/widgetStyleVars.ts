/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";

export const widgetVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("widget", forcedVars);

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
