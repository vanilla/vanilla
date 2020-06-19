/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, srOnly, IFont, margins, negativeUnit } from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { buttonVariables } from "@library/forms/buttonStyles";
import { nestedWorkaround } from "@dashboard/compatibilityStyles";
import { calc } from "csx";

// export const radioInputAsButtonsVariables = useThemeCache(() => {
//     const globalVars = globalVariables();
//     const makeVars = variableFactory("radioInputAsButtons");
//
//     const colors = makeVars("colors", {
//         bg: globalVars.mainColors.bg,
//         fg: globalVars.mainColors.fg,
//         state: {
//             border: {
//                 color: globalVars.mixPrimaryAndBg(0.5),
//             },
//             fg: globalVars.mainColors.primary,
//         },
//         selected: {
//             bg: globalVars.mainColors.primary.desaturate(0.3).fade(0.05),
//             fg: globalVars.mainColors.fg,
//         },
//     });
//
//     const sizing = makeVars("sizing", {
//         height: 24,
//     });
//
//     const font: IFont = makeVars("font", {
//         size: globalVars.fonts.size.small,
//         align: "center",
//         lineHeight: unit(sizing.height),
//     });
//
//     const spacing = makeVars("spacing", {
//         paddings: {
//             horizontal: 8,
//         },
//     });
//
//     const border = makeVars("border", {
//         width: globalVars.border.width,
//         color: globalVars.border.color,
//         radius: 0,
//         style: globalVars.border.style,
//         active: {
//             color: globalVars.mixPrimaryAndBg(0.5),
//         },
//     });
//
//     return {
//         colors,
//         sizing,
//         font,
//         spacing,
//         border,
//     };
// });

export const radioInputAsButtonsClasses = useThemeCache(() => {
    // const vars = radioInputAsButtonsVariables();
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
