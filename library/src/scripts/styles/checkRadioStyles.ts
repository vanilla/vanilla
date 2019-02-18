/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    addUnitIfNumber,
    borderStyles,
    componentThemeVariables,
    debugHelper,
    defaultTransition,
    srOnly,
    disabledInput,
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { percent, px } from "csx";

export function checkRadioVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "checkRadio");

    const border = {
        width: formElementVars.border.width,
        radius: 2,
        color: globalVars.mixBgAndFg(0.8),
        ...themeVars.subComponentStyles("border"),
    };

    const main = {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.bg,
        checked: {
            bg: globalVars.mainColors.primary,
        },
        hover: {
            border: {
                color: globalVars.mainColors.primary,
            },
            bg: globalVars.states.hover.color,
            opacity: 0.8,
        },
        ...themeVars.subComponentStyles("check"),
    };

    const checkBox = {
        check: {
            width: 10,
            height: 10,
        },
        disk: {
            width: 6,
            height: 6,
        },
        bar: {
            fg: globalVars.mainColors.primary,
            width: 8,
            height: 2,
        },
    };

    const radioButton = {
        icon: {
            width: 6,
            height: 6,
        },
    };

    const labelNote = {
        fontSize: ".8em",
        opacity: 0.7,
        ...themeVars.subComponentStyles("labelNote"),
    };

    const sizing = {
        width: 16,
    };

    return { border, main, checkBox, radioButton, labelNote, sizing };
}

export function checkRadioClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = checkRadioVariables(theme);
    const debug = debugHelper("attachment");

    //.radioButton,
    //.checkbox
    const root = style({
        ...debug.name(),
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        whiteSpace: "nowrap",
        $nest: {
            "&:hover": {
                $nest: {
                    ".radioButton-input:not([disabled]), .checkbox-input:not([disabled])": {
                        $nest: {
                            "& + .radioButton-disk, & + .checkbox-box": {
                                borderColor: vars.main.hover.border.color.toString(),
                                opacity: vars.main.hover.opacity,
                            },
                        },
                    },
                    ".radioButton-disk, .checkbox-box": {
                        backgroundColor: vars.main.hover.bg,
                    },
                },
            },
            ".radioButton-input:not([disabled]), .checkbox-input:not([disabled])": {
                $nest: {
                    ".radioButton-input:not([disabled]), .checkbox-input:not([disabled])": {
                        $nest: {
                            "&:focus, &:active": {
                                $nest: {
                                    "& + .radioButton-disk, & + .checkbox-box": {
                                        borderColor: vars.main.hover.border.color.toString(),
                                    },
                                },
                            },
                        },
                    },
                },
            },

            "& + .radioButton,& + .checkbox": {
                marginTop: px(12),
            },
        },
        ...debug.name(),
    });

    //.radioButton-label,
    // .checkbox-label
    const label = {
        ...debug.name("label"),
        lineHeight: addUnitIfNumber(vars.sizing.width),
        marginLeft: addUnitIfNumber(8),
        cursor: "pointer",
        userSelect: "none",
    };

    const labelNote = {
        ...debug.name("labelNote"),
        display: "inline-block",
        fontSize: addUnitIfNumber(vars.labelNote.fontSize),
        marginLeft: addUnitIfNumber(24),
        opacity: vars.labelNote.opacity,
        verticalAlign: "middle",
    };

    // .radioButton-disk,
    // .checkbox-box
    const iconContainer = {
        ...debug.name("iconContainer"),
        ...defaultTransition("border", "background", "opacity"),
        position: "relative",
        display: "inline-block",
        width: addUnitIfNumber(vars.sizing.width),
        height: addUnitIfNumber(vars.sizing.width),
        verticalAlign: "-0.18em",
        cursor: "pointer",
        backgroundColor: vars.main.bg,
        ...borderStyles(vars.border),
    };

    const radioIcon = {
        ...debug.name("radioIcon"),
        ...absolutePosition.middleLeftOfParent(),
        display: "none",
        width: addUnitIfNumber(vars.radioButton.icon.width),
        height: addUnitIfNumber(vars.radioButton.icon.height),
        margin: "auto",
    };

    const checkBoxIcon = {
        ...debug.name("checkBoxIcon"),
        ...absolutePosition.middleLeftOfParent(),
        display: "none",
        width: addUnitIfNumber(vars.checkBox.check.width),
        height: addUnitIfNumber(vars.checkBox.check.height),
        margin: "auto",
    };

    // For mixed values. Example, you've got a checkbox above a columb to check or uncheck all, but the column has both checked and unchecked values.
    const checkBoxBar = {
        ...debug.name("checkBoxBar"),
        color: vars.checkBox.bar.fg.toString(),
        width: addUnitIfNumber(vars.checkBox.bar.width),
        height: addUnitIfNumber(vars.checkBox.bar.height),
    };

    const disk = {
        ...debug.name("disk"),
        borderRadius: percent(50),
    };

    // .radioButton-state,
    // .checkbox-state
    const state = {
        ...debug.name("state"),
        color: vars.main.checked.fg.toString(),
    };

    const diskIcon = {
        width: vars.checkBox.disk.width,
        height: vars.checkBox.disk.height,
    };

    // .radioButton-input,
    // .checkbox-input
    const input = {
        ...srOnly(),
        $nest: {
            "&:checked": {
                $nest: {
                    "& + .radioButton-disk, & + .checkbox-box": {
                        backgroundColor: vars.main.checked.bg.toString(),
                        borderColor: vars.main.checked.bg.toString(),
                        $nest: {
                            ".radioButton-diskIcon, .checkbox-checkIcon": {
                                display: "block",
                            },
                        },
                    },
                },
            },
            "&.isDisabled, &[disabled]": {
                $nest: {
                    "&:not(:checked)": {
                        $nest: {
                            "& + .radioButton-disk, & + .checkbox-box": {
                                backgroundColor: vars.main.bg.toString(),
                            },
                        },
                    },
                    "& ~ .radioButton-label, & ~ .checkbox-label, & + .radioButton-disk, & + .checkbox-box": {
                        ...disabledInput(),
                    },
                },
            },
        },
    };

    return {
        root,
        label,
        labelNote,
        iconContainer,
        radioIcon,
        checkBoxIcon,
        checkBoxBar,
        disk,
        state,
        diskIcon,
        input,
    };
}
