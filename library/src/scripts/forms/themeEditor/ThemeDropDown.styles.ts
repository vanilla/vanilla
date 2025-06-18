/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent } from "csx";
import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IGlobalBorderStyles } from "@library/styles/cssUtilsTypes";
import { css } from "@emotion/css";

export const themeDropDownClasses = useThemeCache(() => {
    const style = styleFactory("themeDropDown");
    const builderVariables = themeBuilderVariables();
    const globalVars = globalVariables();

    const root = style({
        width: percent(100),
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
    });

    const select = css({
        width: "100%",

        borderTopLeftRadius: `${builderVariables.wrap.borderRadius}px !important`,
        borderBottomRightRadius: `${builderVariables.wrap.borderRadius}px !important`,
        borderBottomLeftRadius: `${builderVariables.wrap.borderRadius}px !important`,
        borderTopRightRadius: `${builderVariables.wrap.borderRadius}px !important`,

        "& input": {
            paddingLeft: 8,
        },
    });

    return {
        root,
        select,
    };
});
