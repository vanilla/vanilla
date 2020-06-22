/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {useThemeCache, styleFactory, variableFactory} from "@library/styles/styleUtils";
import {IThemeVariables} from "@library/theming/themeReducer";
import {margins} from "@library/styles/styleHelpersSpacing";
import {calc, percent, translate, translateX} from "csx";
import {borders, colorOut, negativeUnit, srOnly, unit} from "@library/styles/styleHelpers";
import {NestedCSSProperties, TLength} from "typestyle/lib/types";
import {generateButtonStyleProperties} from "@library/forms/styleHelperButtonGenerator";
import {buttonVariables} from "@library/forms/buttonStyles";
import {cssOut, nestedWorkaround} from "@dashboard/compatibilityStyles";

export const searchInFilterVariables = useThemeCache((forcedVars?:IThemeVariables) => {
    const makeThemeVars = variableFactory("searchInFilter", forcedVars);
    const vars = globalVariables();

    const sizing = makeThemeVars("sizing", {
        height: 32,
    });

    const spacing = makeThemeVars("spacing", {
        margin: {
            vertical: 4,
            horizontal: 12,
        },
    });

    return {
        sizing,
        spacing,
    };
});

export const searchInFilterClasses = useThemeCache(() => {
    const style = styleFactory("searchInFilter");
    const globalVars = globalVariables();
    const vars = searchInFilterVariables();

    const root = style({
        overflow: "hidden", // to truncate the extra margin
    });

    const items = style("items", {
        display: "flex",
        alignItems: "center",
        flexWrap: "wrap",
        ...margins({
            horizontal: vars.spacing.margin.horizontal,
            vertical: vars.spacing.margin.vertical + 7,
        }),
        width: calc(`100% + ${unit(2 * vars.spacing.margin.horizontal)}`),
        transform: translate(negativeUnit(vars.spacing.margin.horizontal * 2)),
    } as NestedCSSProperties);

    const item = style("item", {
        display: "inline-flex",
        flexShrink: 1,
        ...margins(vars.spacing.margin),
    });

    const label = style("label", {});
    const input = style("input", {});

    // Style "button"
    const labelStateStyles = generateButtonStyleProperties(buttonVariables().radio);
    nestedWorkaround(`.${label}`, labelStateStyles.$nest);

    // Style states on actual radio button
    const hiddenInputStates = generateButtonStyleProperties(buttonVariables().radio, false, ` + .${label}`);
    nestedWorkaround(`.${input}`, hiddenInputStates.$nest);

    const separator = style("separator", {
        display: "inline-flex",
        height: unit(24),
        width: unit(globalVars.border.width),
        backgroundColor: colorOut(globalVars.border.color),
        ...margins({
            horizontal: vars.spacing.margin.horizontal,
        })
    });

    const labelWrap = style("labelWrap", {
        marginLeft: unit(9),
    });

    const iconWrap = style("iconWrap", {
        display: "inline-flex",
    });

    return {
        root,
        items,
        item,
        label,
        input,
        separator,
        iconWrap,
        labelWrap,
    };
});
