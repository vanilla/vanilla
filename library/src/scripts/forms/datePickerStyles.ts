/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, unit } from "@library/styles/styleHelpers";
import { componentThemeVariables, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { style } from "typestyle";

export const dayPickerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = componentThemeVariables("datePicker");

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
            color: globalVars.states.selected.highlight,
        },
        hover: {
            bg: globalVars.states.hover.highlight,
        },
    };

    const border = {
        radius: globalVars.border.radius,
    };

    return { spacing, sizing, colors, border };
});

export const dayPickerClasses = useThemeCache(() => {
    const debug = debugHelper("dayPicker");
    const vars = dayPickerVariables();

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
});
