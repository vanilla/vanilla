/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { colorOut, getRatioBasedOnDarkness } from "@library/styles/styleHelpersColors";
import { fonts } from "@library/styles/styleHelpersTypography";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { ColorHelper } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const forumFontsVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumFonts");
    const globalVars = globalVariables();
    const fonts = makeThemeVars("fonts", {
        sizes: {
            title: globalVars.fonts.size.large,
            base: globalVars.fonts.size.medium,

            // large: 16,
            // medium: 14,
            // small: 12,
            // largeTitle: 32,
            // title: 22,
            // subTitle: 18,
        },
    });

    return { fonts };
});

export const fontCSS = () => {
    const globalVars = globalVariables();
    const vars = forumFontsVariables();
    const inputVars = inputVariables();

    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut(`.Meta .MItem`, {
        ...fonts({
            size: globalVars.meta.text.fontSize,
            color: globalVars.meta.text.color,
        }),
    });

    cssOut(`.Title, .Title a`, {
        ...fonts({
            size: vars.fonts.sizes.title,
        }),
    });
};
