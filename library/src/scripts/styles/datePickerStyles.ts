/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { percent, px } from "csx";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

export function dayPickerVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "datePicker");

    const spacing = {
        padding: 9,
        ...themeVars.subComponentStyles("spacing"),
    };

    const sizing = {
        height: formElementVars.sizing.height,
    };

    const colors = {
        today: globalVars.mainColors.primary,
        selected: {
            color: globalVars.states.active.color,
        },
        hover: {
            bg: globalVars.states.hover.color,
        },
    };

    const border = {
        radius: globalVars.border.radius,
    };

    return { spacing, sizing, colors, border };
}

export function dayPickerClasses(theme?: object) {
    const debug = debugHelper("dayPicker");
    const vars = dayPickerVariables(theme);

    // From third party, so we're targetting them this way
    const root = style({
        ...debug.name(),
        $nest: {
            "& .DayPicker-wrapper": {
                ...debug.name("AMIBHERE"),
                padding: 0,
            },
            "& .DayPicker-Month": {
                margin: unit(vars.spacing.padding),
            },
            "& .DayPicker-Day": {
                borderRadius: unit(vars.border.radius),
                padding: unit(vars.spacing.padding),
                whiteSpace: "nowrap",
                $nest: {
                    "&:hover": {
                        backgroundColor: vars.colors.hover.bg.toString(),
                    },
                },
            },
            "& .DayPicker-Day--selected:not(.DayPicker-Day--disabled):not(.DayPicker-Day--outside)": {
                backgroundColor: vars.colors.selected.color.toString(),
                $nest: {
                    "&:hover": {
                        backgroundColor: vars.colors.selected.color.toString(),
                    },
                },
            },
            "& .DayPicker-Day--today": {
                color: vars.colors.today.toString(),
            },
        },
    });

    const header = style({
        display: "flex",
        alignItems: "center",
        height: unit(vars.sizing.height),
        paddingLeft: unit(vars.spacing.padding),
        marginTop: unit(vars.spacing.padding),
        ...debug.name("header"),
    });

    const title = style({
        flex: 1,
        padding: unit(vars.spacing.padding),
        ...debug.name("title"),
    });

    const navigation = style({
        display: "flex",
        alignItems: "center",
        ...debug.name("navigation"),
    });

    return { root, header, title, navigation };
}
