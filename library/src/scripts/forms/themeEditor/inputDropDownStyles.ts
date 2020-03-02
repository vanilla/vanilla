/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";
import { unit } from "@library/styles/styleHelpers";
import { themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";

export const inputDropDownClasses = useThemeCache(() => {
    const style = styleFactory("numberInput");
    const builderVariables = themeBuilderVariables();

    const root = style({
        width: percent(100),
        $nest: {
            "& .input-wrap-right": {
                width: percent(100),
            },
            "& .suggestedTextInput-valueContainer": {
                minHeight: unit(builderVariables.input.height),
                paddingTop: 0,
                paddingBottom: 0,
            },
            "& .SelectOne__indicators": {
                height: unit(builderVariables.input.height),
            },
        },
    });

    return {
        root,
    };
});
