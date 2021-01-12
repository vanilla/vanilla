/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { negativeUnit } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { buttonVariables } from "@library/forms/Button.variables";
import { calc } from "csx";

export const radioInputAsButtonsClasses = useThemeCache(() => {
    const style = styleFactory("radioInputAsButtons");
    const mediaQueries = layoutVariables().mediaQueries();
    const globalVars = globalVariables();

    const root = style({
        display: "block",
    });

    const items = style("items", {
        display: "flex",
        position: "relative",
        alignItems: "center",
        justifyContent: "flex-start",
        ...Mixins.margin({
            horizontal: negativeUnit(globalVars.gutter.half),
            vertical: negativeUnit(globalVars.gutter.half),
        }),
        ...mediaQueries.xs({
            flexWrap: "wrap",
            justifyContent: "stretch",
            width: calc(`100% + ${styleUnit(globalVars.gutter.size)}`),
        }),
    });

    const item = style(
        "item",

        {
            ...Mixins.margin({
                all: globalVars.gutter.half,
            }),
        },
        mediaQueries.xs({
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "stretch",
            flexGrow: 1,
        }),
    );

    const labelStateStyles = generateButtonStyleProperties({ buttonTypeVars: buttonVariables().primary });
    const label = style("label", {
        ...userSelect(),
        display: "inline-flex",
        position: "relative",
        cursor: "pointer",
        textAlign: "center",
        justifyContent: "center",
        ...labelStateStyles,
    });

    const hiddenInputStates = generateButtonStyleProperties({
        buttonTypeVars: buttonVariables().primary,
        stateSuffix: ` + .${label}`,
    });

    const input = style("input", {
        ...Mixins.absolute.srOnly(),
        ...hiddenInputStates,
    });

    return {
        root,
        items,
        item,
        label,
        input,
    } as IRadioInputAsButtonClasses;
});
