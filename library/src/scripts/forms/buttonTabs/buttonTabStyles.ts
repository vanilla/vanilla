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

export const buttonTabClasses = useThemeCache((props?: { detached?: boolean }) => {
    const style = styleFactory("buttonTabs");
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();

    const root = style({
        display: "block",
    });

    const tabs = style(
        "tabs",
        {
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "flex-start",
        },
        mediaQueries.xs({
            flexWrap: "wrap",
            marginLeft: negativeUnit(globalVars.gutter.half),
            ...margins({
                horizontal: negativeUnit(globalVars.gutter.quarter),
                vertical: negativeUnit(globalVars.gutter.quarter),
            }),
            width: calc(`100% + ${unit(globalVars.gutter.size)}`),
        }),
    );

    const tab = style(
        "tab",
        {
            marginRight: unit(globalVars.gutter.size),
            $nest: {
                "&.isLast": {
                    flexGrow: 1,
                    marginRight: 0,
                },
            },
        },
        mediaQueries.xs({
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "stretch",
            ...margins({
                all: globalVars.gutter.quarter,
            }),
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
        tabs,
        tab,
        label,
        input,
        leftTab,
        rightTab,
    };
});
