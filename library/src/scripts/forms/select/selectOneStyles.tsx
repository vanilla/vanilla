/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { calc } from "csx";

export const selectOneVariables = useThemeCache(() => {
    const vars = variableFactory("selectOne");

    const padding = vars("padding", {
        right: 30,
    });

    return { padding };
});

export const selectOneClasses = useThemeCache(() => {
    const style = styleFactory("selectOne");
    const vars = selectOneVariables();

    const inputWrap = style("inputWrap", {
        $nest: {
            "&.hasFocus .inputBlock-inputText": {
                borderColor: colorOut(globalVariables().mainColors.primary),
            },
            ".inputBlock-inputText": {
                paddingRight: unit(vars.padding.right),
                position: "relative",
            },
            ".SelectOne__indicators": {
                position: "absolute",
                top: 0,
                right: 6,
                bottom: 0,
            },
            ".SelectOne__indicator": {
                cursor: "pointer",
            },
            "& .SelectOne__single-value": {
                textOverflow: "ellipsis",
                maxWidth: calc(`100% - ${unit(vars.padding.right + 26)}`),
            },
        },
    });

    return { inputWrap };
});
