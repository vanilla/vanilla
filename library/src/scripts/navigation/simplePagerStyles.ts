/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { buttonGlobalVariables } from "@library/forms/buttonStyles";

export const simplePagerVariables = useThemeCache(() => {
    const themeVars = variableFactory("simplePage");
    const buttonVars = buttonGlobalVariables();

    const sizing = themeVars("sizing", {
        minWidth: buttonVars.sizing.minWidth,
    });

    const spacing = themeVars("spacing", {
        outerMargin: 10,
        innerMargin: 8,
    });

    return {
        spacing,
        sizing,
    };
});

export const simplePagerClasses = useThemeCache(() => {
    const vars = simplePagerVariables();
    const style = styleFactory("simplePager");

    const root = style({
        alignItems: "center",
        display: "flex",
        justifyContent: "center",
        margin: `${unit(vars.spacing.outerMargin)} 0`,
    });

    const button = style("button", {
        margin: unit(vars.spacing.innerMargin),
        minWidth: unit(vars.sizing.minWidth),
        $nest: {
            "&.isSingle": {
                minWidth: unit(vars.sizing.minWidth * 2),
            },
        },
    });

    return {
        root,
        button,
    };
});
