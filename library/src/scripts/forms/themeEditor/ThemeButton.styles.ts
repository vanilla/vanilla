/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";

export const themeButtonClasses = useThemeCache(() => {
    const style = styleFactory("themeButton");
    const builderVariables = themeBuilderVariables();
    const globalVars = globalVariables();

    const root = style({
        width: percent(100),
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...{
            "& button": {
                width: percent(100),
                height: styleUnit(builderVariables.input.height),
                ...Mixins.border(
                    {
                        ...(builderVariables.border as IGlobalBorderStyles),
                        radius: builderVariables.wrap.borderRadius,
                    },
                    {},
                ),
            },
            "&& button:focus": {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    return {
        root,
    };
});
