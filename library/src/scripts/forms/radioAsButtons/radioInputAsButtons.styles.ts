/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { unit, srOnly, margins, negativeUnit } from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { buttonVariables } from "@library/forms/buttonStyles";
import { nestedWorkaround } from "@dashboard/compatibilityStyles";
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
        ...margins({
            horizontal: negativeUnit(globalVars.gutter.half),
            vertical: negativeUnit(globalVars.gutter.half),
        }),
        ...mediaQueries.xs({
            flexWrap: "wrap",
            justifyContent: "stretch",
            width: calc(`100% + ${unit(globalVars.gutter.size)}`),
        }),
    });

    const item = style(
        "item",

        {
            ...margins({
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

    const label = style("label", {
        ...userSelect(),
        display: "inline-flex",
        position: "relative",
        cursor: "pointer",
        textAlign: "center",
        justifyContent: "center",
    });

    const labelStateStyles = generateButtonStyleProperties(buttonVariables().primary);
    nestedWorkaround(`.${label}`, labelStateStyles.$nest);

    const hiddenInputStates = generateButtonStyleProperties(buttonVariables().primary, false, ` + .${label}`);
    const input = style("input", {
        ...srOnly(),
    });
    nestedWorkaround(`.${input}`, hiddenInputStates.$nest);
    return {
        root,
        items,
        item,
        label,
        input,
    } as IRadioInputAsButtonClasses;
});
