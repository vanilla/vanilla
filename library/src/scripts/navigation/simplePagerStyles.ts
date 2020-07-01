/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { margins, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { buttonGlobalVariables } from "@library/forms/buttonStyles";

export const simplePagerVariables = useThemeCache(() => {
    const themeVars = variableFactory("simplePage");
    const buttonVars = buttonGlobalVariables();

    const sizing = themeVars("sizing", {
        minWidth: buttonVars.sizing.minWidth,
    });

    const spacing = themeVars("spacing", {
        top: 32,
        bottom: 26,
        button: 8,
    });

    return {
        spacing,
        sizing,
    };
});

export const simplePagerClasses = useThemeCache(() => {
    const vars = simplePagerVariables();
    const style = styleFactory("simplePager");
    const { spacing } = vars;

    const root = style({
        alignItems: "center",
        display: "flex",
        justifyContent: "center",
        ...margins({
            top: spacing.top - spacing.button,
            bottom: spacing.bottom - spacing.button,
        }),
    });

    const button = style("button", {
        margin: unit(vars.spacing.button),
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
