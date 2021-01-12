/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const dayPickerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const makeThemeVars = variableFactory("datePicker");

    const spacing = makeThemeVars("spacing", {
        padding: 9,
    });

    const sizing = makeThemeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const colors = makeThemeVars("colors", {
        today: globalVars.mainColors.primary,
        selected: {
            color: globalVars.states.selected.highlight,
        },
        hover: {
            bg: globalVars.states.hover.highlight,
        },
    });

    const border = makeThemeVars("border", {
        radius: globalVars.border.radius,
    });

    return {
        spacing,
        sizing,
        colors,
        border,
    };
});

export const dayPickerClasses = useThemeCache(() => {
    const style = styleFactory("dayPicker");
    const vars = dayPickerVariables();

    // From third party, so we're targetting them this way
    const root = style({
        ...{
            ".DayPicker-wrapper": {
                padding: 0,
            },
            ".DayPicker-Month": {
                margin: styleUnit(vars.spacing.padding),
            },
            ".DayPicker-Day": {
                borderRadius: styleUnit(vars.border.radius),
                padding: styleUnit(vars.spacing.padding),
                whiteSpace: "nowrap",
                ...{
                    "&:hover": {
                        backgroundColor: vars.colors.hover.bg.toString(),
                    },
                },
            },
            ".DayPicker-Day--selected:not(.DayPicker-Day--disabled):not(.DayPicker-Day--outside)": {
                backgroundColor: vars.colors.selected.color.toString(),
                ...{
                    "&:hover": {
                        backgroundColor: vars.colors.selected.color.toString(),
                    },
                },
            },
            ".DayPicker-Day.DayPicker-Day--today": {
                color: ColorsUtils.colorOut(vars.colors.today),
            },
        },
    });

    const header = style("header", {
        display: "flex",
        alignItems: "center",
        height: styleUnit(vars.sizing.height),
        paddingLeft: styleUnit(vars.spacing.padding),
        marginTop: styleUnit(vars.spacing.padding),
    });

    const title = style("title", {
        flex: 1,
        padding: styleUnit(vars.spacing.padding),
    });

    const navigation = style("navigation", {
        display: "flex",
        alignItems: "center",
    });

    return {
        root,
        header,
        title,
        navigation,
    };
});
