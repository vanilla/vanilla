/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { unit, srOnly, negativeUnit, margins, pointerEvents } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, important, percent } from "csx";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { buttonVariables } from "@library/forms/buttonStyles";
import { nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles";
import { IRadioTabClasses } from "@library/forms/radioTabs/RadioTabs";

export const buttonTabClasses = useThemeCache((props?: { detached?: boolean }) => {
    const style = styleFactory("buttonTabs");
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();

    const root = style({
        display: "block",
    });

    const items = style(
        "tabs",
        {
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "flex-start",
            ...margins({
                horizontal: negativeUnit(globalVars.gutter.half),
                vertical: negativeUnit(globalVars.gutter.half),
            }),
        },
        mediaQueries.xs({
            flexWrap: "wrap",
            justifyContent: "stretch",
            width: calc(`100% + ${unit(globalVars.gutter.size)}`),
        }),
    );

    const item = style(
        "tab",
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

    const leftTab = style("leftTab", {});
    const rightTab = style("rightTab", {});

    const label = style(
        "label",
        {
            $nest: {
                "&.isDisabled": {
                    ...pointerEvents("none"),
                    opacity: formElementVariables.disabled.opacity,
                },
            },
        },
        mediaQueries.xs({
            minWidth: important(0),
            flexGrow: 1,
        }),
    );
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
        leftTab,
        rightTab,
    } as IRadioTabClasses;
});
