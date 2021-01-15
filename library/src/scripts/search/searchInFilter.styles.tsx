/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonVariables } from "@library/forms/Button.variables";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc, translate } from "csx";
import { CSSObject } from "@emotion/css";

export const searchInFilterVariables = useThemeCache((forcedVars?: IThemeVariables) => {
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
        ...Mixins.margin({
            horizontal: vars.spacing.margin.horizontal,
            vertical: vars.spacing.margin.vertical + 7,
        }),
        width: calc(`100% + ${styleUnit(2 * vars.spacing.margin.horizontal)}`),
        transform: translate(negativeUnit(vars.spacing.margin.horizontal * 2)),
    });

    const item = style("item", {
        display: "inline-flex",
        flexShrink: 1,
        ...Mixins.margin(vars.spacing.margin),
    });

    // Style "button"
    const labelStateStyles = generateButtonStyleProperties({
        buttonTypeVars: buttonVariables().radio,
    });

    const label = style("label", {
        ...labelStateStyles,
    });

    // Style states on actual radio button
    const hiddenInputStates = generateButtonStyleProperties({
        buttonTypeVars: buttonVariables().radio,
        stateSuffix: ` + .${label}`,
    });

    const input = style("input", {
        ...hiddenInputStates,
    });

    const separator = style("separator", {
        display: "inline-flex",
        height: styleUnit(24),
        width: styleUnit(globalVars.border.width),
        backgroundColor: ColorsUtils.colorOut(globalVars.border.color),
        ...Mixins.margin({
            horizontal: vars.spacing.margin.horizontal,
        }),
    });

    const labelWrap = style("labelWrap", {
        marginLeft: styleUnit(9),
    });

    const iconWrap = style("iconWrap", {
        display: "inline-flex",
    });

    const buttonAutoMinWidth = style("buttonAutoMinWidth", {
        minWidth: "auto",
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
        buttonAutoMinWidth,
    };
});
