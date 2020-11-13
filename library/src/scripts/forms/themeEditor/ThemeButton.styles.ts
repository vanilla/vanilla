/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";
import { borders, colorOut, flexHelper, fonts, unit } from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables, IGlobalBorderStyles } from "@library/styles/globalStyleVars";

export const themeButtonClasses = useThemeCache(() => {
    const style = styleFactory("themeButton");
    const builderVariables = themeBuilderVariables();
    const globalVars = globalVariables();

    const root = style({
        width: percent(100),
        background: colorOut(globalVars.mainColors.bg),
        $nest: {
            "& button": {
                width: percent(100),
                height: unit(builderVariables.input.height),
                ...borders(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {},
                ),
            },
            "&& button:focus": {
                borderColor: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    return {
        root,
    };
});
