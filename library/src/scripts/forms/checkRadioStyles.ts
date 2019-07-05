/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import {
    absolutePosition,
    borders,
    colorOut,
    defaultTransition,
    disabledInput,
    flexHelper,
    srOnly,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { em, important, percent, px } from "csx";
import {NestedCSSProperties} from "typestyle/lib/types";

export const checkRadioVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = componentThemeVariables("checkRadio");

    const border = {
        width: formElementVars.border.width,
        radius: 2,
        color: globalVars.mixBgAndFg(0.5),
        ...themeVars.subComponentStyles("border"),
    };

    const main = {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.bg,
        checked: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.primary,
        },
        hover: {
            border: {
                color: globalVars.mixPrimaryAndBg(0.682),
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
});

export const checkRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = checkRadioVariables();
    const style = styleFactory("checkRadio");
    const flexes = flexHelper();

    //.radioButton,
    //.checkbox
    const root = style({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        whiteSpace: "nowrap",
        outline: 0,
        $nest: {
            "&.focus-accessible": {},
            "&:hover": {
                $nest: {
                    "& .radioButton-input:not([disabled]), & .checkbox-input:not([disabled])": {
                        $nest: {
                            "& + .radioButton-disk, & + .checkbox-box": {
                                borderColor: vars.main.hover.border.color.toString(),
                                opacity: vars.main.hover.opacity,
                                backgroundColor: vars.main.hover.bg.toString(),
                            },
                        },
                    },
                    "& .radioButton-disk, & .checkbox-box": {
                        backgroundColor: vars.main.hover.bg.toString(),
                    },
                },
            },
            "& + .radioButton, & + .checkbox": {
                marginTop: px(12),
            },
        },
    });

    //.radioButton-label,
    // .checkbox-label
    const label = style("label", {
        lineHeight: unit(vars.sizing.width),
        marginLeft: unit(8),
        cursor: "pointer",
        ...userSelect(),
    });

    const labelNote = style("labelNote", {
        display: "inline-block",
        fontSize: unit(vars.labelNote.fontSize),
        marginLeft: unit(24),
        opacity: vars.labelNote.opacity,
        verticalAlign: "middle",
    });

    // .radioButton-disk,
    // .checkbox-box
    const iconContainer = style("iconContainer", {
        ...defaultTransition("border", "background", "opacity"),
        position: "relative",
        display: "inline-block",
        width: unit(vars.sizing.width),
        height: unit(vars.sizing.width),
        verticalAlign: em(-0.18),
        cursor: "pointer",
        backgroundColor: colorOut(vars.main.bg),
        ...borders(vars.border),
    } as NestedCSSProperties);

    const radioIcon = style("radioIcon", {
        ...absolutePosition.middleLeftOfParent(),
        display: "none",
        width: unit(vars.radioButton.icon.width),
        height: unit(vars.radioButton.icon.height),
        margin: "auto",
    });

    const checkIcon = style("checkBoxIcon", {
        ...absolutePosition.middleOfParent(),
        display: "none",
        width: unit(vars.checkBox.check.width),
        height: unit(vars.checkBox.check.height),
        color: vars.main.fg.toString(),
        margin: "auto",
    });

    const disk = style("disk", {
        borderRadius: percent(50),
    });

    // .radioButton-state,
    // .checkbox-state
    const state = style("state", {
        ...absolutePosition.fullSizeOfParent(),
        color: vars.main.checked.fg.toString(),
    });

    const diskIcon = style({
        width: vars.checkBox.disk.width,
        height: vars.checkBox.disk.height,
    });

    // .radioButton-input,
    // .checkbox-input
    const input = style("input", {
        ...srOnly(),
        $nest: {
            "&:not([disabled]):focus, &:not([disabled]):active, &:not([disabled]).focus-visible ": {
                $nest: {
                    "& + .radioButton-disk, & + .checkbox-box": {
                        borderColor: vars.main.hover.border.color.toString(),
                        opacity: vars.main.hover.opacity,
                        backgroundColor: vars.main.hover.bg.toString(),
                    },
                },
            },
            "&:checked + .radioButton-disk, &:checked + .checkbox-box": {
                backgroundColor: important(vars.main.checked.bg.toString()),
                borderColor: vars.main.checked.fg.toString(),
                $nest: {
                    "& .radioButton-diskIcon, & .checkbox-checkIcon": {
                        display: "block",
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
    });

    return {
        root,
        label,
        labelNote,
        iconContainer,
        radioIcon,
        checkIcon,
        disk,
        state,
        diskIcon,
        input,
    };
});
