/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { absolutePosition, defaultTransition, disabledInput, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, em, important, percent, px } from "csx";
import { CSSObject } from "@emotion/css";

export const checkRadioVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("checkRadio");

    const border = themeVars(
        "border",
        Variables.border({
            ...formElementVars.border,
            radius: 2,
        }),
    );

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
        lineHeight: styleUnit(vars.sizing.width),
        paddingLeft: styleUnit(8),
        cursor: "pointer",
        ...userSelect(),
        width: calc(`100% - ${styleUnit(vars.sizing.width)}`),
    });

    const labelBold = style("labelBold", {
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
        }),
    });

    const labelNote = style("labelNote", {
        display: "inline-block",
        fontSize: styleUnit(vars.labelNote.fontSize),
        marginLeft: styleUnit(24),
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
        width: styleUnit(vars.sizing.width),
        height: styleUnit(vars.sizing.width),
        flexBasis: styleUnit(vars.sizing.width),
        verticalAlign: "middle",
        cursor: "pointer",
        backgroundColor: ColorsUtils.colorOut(vars.main.bg),
        ...Mixins.border(vars.border),
    });

    const radioIcon = style("radioIcon", {
        ...absolutePosition.middleLeftOfParent(),
        display: "none",
        width: styleUnit(vars.radioButton.icon.width),
        height: styleUnit(vars.radioButton.icon.height),
        margin: "auto",
    });

    const checkIcon = style("checkBoxIcon", {
        ...absolutePosition.middleOfParent(),
        display: "none",
        width: styleUnit(vars.checkBox.check.width),
        height: styleUnit(vars.checkBox.check.height),
        color: "inherit",
        margin: "auto",
    });

    const disk = style("disk", {
        borderRadius: percent(50),
    });

    const diskIcon = style("diskIcon", {
        display: "none",
        width: vars.checkBox.disk.width,
        height: vars.checkBox.disk.height,
    });

    const uncheckedStateStyles: CSSObject = {
        borderColor: ColorsUtils.colorOut(vars.main.hover.border.color),
        backgroundColor: ColorsUtils.colorOut(vars.main.hover.bg),
    };

    const checkedStateStyles: CSSObject = {
        backgroundColor: ColorsUtils.colorOut(vars.main.checkedHover.bg),
        color: ColorsUtils.colorOut(vars.main.checkedHover.fg),
    };

    // .radioButton-input,
    // .checkbox-input
    const input = style("input", {
        ...Mixins.absolute.srOnly(),
        ...{
            [`&:hover:not(:disabled) + .${iconContainer}`]: uncheckedStateStyles,
            [`&.focus-visible:not(:disabled) + .${iconContainer}`]: uncheckedStateStyles,
            [`&:checked + .${iconContainer}`]: {
                borderColor: ColorsUtils.colorOut(vars.main.checked.border),
                color: ColorsUtils.colorOut(vars.main.checked.fg),
                backgroundColor: ColorsUtils.colorOut(vars.main.checked.bg),
                ...{
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
        ...Mixins.padding(vars.spacing),
        ...{
            [`&&`]: {
                margin: 0,
            },
            [`&.isHorizontal.isHorizontal.isHorizontal`]: Mixins.margin({
                all: 0,
                right: px(globalVars.spacer.size / 2),
            }),
            [`&.${isDashboard} + .info`]: {
                ...Mixins.margin({
                    top: styleUnit(2),
                    bottom: styleUnit(6),
                }),
            },
        },
    });

    const grid = style("grid", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "strech",
        ...{
            [`.${root}`]: {
                flexBasis: "50%",
                display: "block !important",
                ...Mixins.margin({
                    top: 0,
                }),
            },
            [`.${root}:nth-child(n + 3)`]: {
                ...Mixins.margin({
                    top: styleUnit(globalVars.gutter.half),
                }),
            },
            [`.${root}:nth-child(odd)`]: {
                ...Mixins.padding({
                    right: styleUnit(globalVars.gutter.half),
                }),
            },
        },
    });

    return {
        root,
        label,
        labelBold,
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
