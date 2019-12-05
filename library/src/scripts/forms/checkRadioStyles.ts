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
    margins,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { em, important, percent, px } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const checkRadioVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("checkRadio");

    const border = themeVars("border", {
        width: formElementVars.border.width,
        radius: 2,
        color: globalVars.mixBgAndFg(0.5),
    });

    const main = themeVars("check", {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.bg,
        checked: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.primary,
        },
        hover: {
            border: {
                color: globalVars.mainColors.primary,
            },
            bg: globalVars.mainColors.primary.fade(0.1),
            opacity: 0.8,
        },
    });

    const checkBox = themeVars("checkBox", {
        check: {
            width: 10,
            height: 10,
        },
        disk: {
            width: 6,
            height: 6,
        },
    });

    const radioButton = themeVars("radioButton", {
        icon: {
            width: 6,
            height: 6,
        },
    });

    const labelNote = themeVars("labelNote", {
        fontSize: ".8em",
        opacity: 0.7,
    });

    const sizing = themeVars("sizing", {
        width: 16,
    });

    return {
        border,
        main,
        checkBox,
        radioButton,
        labelNote,
        sizing,
    };
});

export const checkRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = checkRadioVariables();
    const style = styleFactory("checkRadio");
    const flexes = flexHelper();

    const isDashboard = style("isDashboard", {});

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
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        color: vars.main.checked.fg.toString(),
    });

    const diskIcon = style({
        display: "none",
        width: vars.checkBox.disk.width,
        height: vars.checkBox.disk.height,
    });

    // .radioButton-input,
    // .checkbox-input
    const input = style("input", {
        ...srOnly(),
        $nest: {
            [`&:not([disabled]):focus, &:not([disabled]):active, &:not([disabled]).focus-visible`]: {
                $nest: {
                    [`& + .${checkIcon}`]: {
                        borderColor: vars.main.hover.border.color.toString(),
                        opacity: vars.main.hover.opacity,
                        backgroundColor: vars.main.hover.bg.toString(),
                    },
                },
            },
            [`&:checked + .${iconContainer}`]: {
                backgroundColor: important(vars.main.checked.bg.toString()),
                borderColor: vars.main.checked.fg.toString(),
                $nest: {
                    [`& .${checkIcon}`]: {
                        display: "block",
                    },
                    [`& .${diskIcon}`]: {
                        display: "block",
                    },
                },
            },
            [`&.isDisabled, &[disabled]`]: {
                $nest: {
                    "&:not(:checked)": {
                        $nest: {
                            [`& + .${checkIcon}`]: {
                                backgroundColor: vars.main.bg.toString(),
                            },
                        },
                    },
                    [`& ~ .${label}, & + .${checkIcon}`]: {
                        ...disabledInput(),
                    },
                },
            },
        },
    });

    //.radioButton,
    //.checkbox
    const root = style({
        display: important("flex"),
        flexWrap: "wrap",
        alignItems: "center",
        whiteSpace: "nowrap",
        outline: 0,
        cursor: "pointer",
        $nest: {
            [`&:hover .${input}:not([disabled]) + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.hover.border.color),
                backgroundColor: colorOut(vars.main.hover.bg),
            },
            [`&.focus-accessible .${input}:not([disabled]) + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.hover.border.color),
                backgroundColor: colorOut(vars.main.hover.bg),
            },
            [`&:focus .${input}:not([disabled]) + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.hover.border.color),
                backgroundColor: colorOut(vars.main.hover.bg),
            },
            [`.${input}:not([disabled]):hover + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.hover.border.color),
                backgroundColor: colorOut(vars.main.hover.bg),
            },
            [`.${input}:not([disabled]):focus + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.hover.border.color),
                backgroundColor: colorOut(vars.main.hover.bg),
            },
            [`& + &`]: {
                marginTop: px(globalVars.spacer.size / 2),
            },
            [`&.isHorizontal.isHorizontal.isHorizontal`]: margins({
                all: 0,
                right: px(globalVars.spacer.size / 2),
            }),
            [`&.${isDashboard} + .info`]: {
                ...margins({
                    top: unit(2),
                    bottom: unit(6),
                }),
            },
        },
    });

    const group = style("group", {
        marginTop: unit(globalVars.spacer.size / 2),
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
        group,
        isDashboard,
    };
});
