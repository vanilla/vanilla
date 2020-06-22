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
    srOnly,
    unit,
    userSelect,
    margins,
    paddings,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import {calc, em, important, percent, px} from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const checkRadioVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("checkRadio");

    const border = themeVars("border", {
        ...formElementVars.border,
        radius: 2,
    });

    const main = themeVars("check", {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.bg,
        checked: {
            fg: globalVars.elementaryColors.white,
            bg: globalVars.mainColors.primary,
            border: globalVars.mainColors.primary,
        },
        checkedHover: {
            fg: globalVars.mainColors.primary,
            bg: globalVars.mainColors.primary.fade(0.15),
        },
        hover: {
            border: {
                color: globalVars.mainColors.primary,
            },
            fg: globalVars.mainColors.primary,
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
        width: 18,
    });

    const spacing = themeVars("spacing", {
        horizontal: 6,
        vertical: 9,
    });

    return {
        border,
        main,
        checkBox,
        radioButton,
        labelNote,
        sizing,
        spacing,
    };
});

export const checkRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = checkRadioVariables();
    const style = styleFactory("checkRadio");

    const isDashboard = style("isDashboard", {});

    //.radioButton-label,
    // .checkbox-label
    const label = style("label", {
        lineHeight: unit(vars.sizing.width),
        paddingLeft: unit(8),
        cursor: "pointer",
        ...userSelect(),
        width: calc(`100% - ${unit(vars.sizing.width)}`),
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
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        width: unit(vars.sizing.width),
        height: unit(vars.sizing.width),
        flexBasis: unit(vars.sizing.width),
        verticalAlign: "middle",
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
        color: "inherit",
        margin: "auto",
    });

    const disk = style("disk", {
        borderRadius: percent(50),
    });

    const diskIcon = style("diskIcon",{
        display: "none",
        width: vars.checkBox.disk.width,
        height: vars.checkBox.disk.height,
    });

    const uncheckedStateStyles: NestedCSSProperties = {
        borderColor: colorOut(vars.main.hover.border.color),
        backgroundColor: colorOut(vars.main.hover.bg),
    };

    const checkedStateStyles: NestedCSSProperties = {
        backgroundColor: colorOut(vars.main.checkedHover.bg),
        color: colorOut(vars.main.checkedHover.fg),
    };

    // .radioButton-input,
    // .checkbox-input
    const input = style("input", {
        ...srOnly(),
        $nest: {
            [`&:hover:not(:disabled) + .${iconContainer}`]: uncheckedStateStyles,
            [`&.focus-visible:not(:disabled) + .${iconContainer}`]: uncheckedStateStyles,
            [`&:checked + .${iconContainer}`]: {
                borderColor: colorOut(vars.main.checked.border),
                color: colorOut(vars.main.checked.fg),
                backgroundColor: colorOut(vars.main.checked.bg),
                $nest: {
                    "& svg": {
                        display: "block",
                    },
                },
            },
            [`&:hover:checked:not(:disabled) + .${iconContainer}`]: checkedStateStyles,
            [`&.focus-visible:checked:not(:disabled) + .${iconContainer}`]: checkedStateStyles,
            [`&:disabled ~ .${label}`]: disabledInput(),
            [`&:disabled + .${iconContainer}`]: disabledInput(),
        },
    });

    //.radioButton,
    //.checkbox
    const root = style({
        display: important("flex"),
        flexWrap: "wrap",
        alignItems: "center",
        outline: 0,
        ...paddings(vars.spacing),
        $nest: {
            [`&&`]: {
                margin: 0,
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
    } as NestedCSSProperties);

    const grid = style("grid", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "strech",
        $nest: {
            [`.${root}`]: {
                flexBasis: "50%",
                display: "block !important",
                ...margins({
                    top: 0,
                }),
            },
            [`.${root}:nth-child(n + 3)`]: {
                ...margins({
                    top: unit(globalVars.gutter.half),
                }),
            },
            [`.${root}:nth-child(odd)`]: {
                ...paddings({
                    right: unit(globalVars.gutter.half),
                }),
            },
        },
    } as NestedCSSProperties);

    return {
        root,
        label,
        labelNote,
        iconContainer,
        radioIcon,
        checkIcon,
        disk,
        diskIcon,
        input,
        grid,
        isDashboard,
    };
});
