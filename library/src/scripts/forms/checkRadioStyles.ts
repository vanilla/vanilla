/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { CSSObject } from "@emotion/css/types/create-instance";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { defaultTransition, disabledInput, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { metasVariables } from "@library/metas/Metas.variables";
import { important, percent, px } from "csx";

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
        width: 16,
    });

    const spacing = themeVars("spacing", {
        horizontal: 6,
        vertical: 9,
        left: 0,
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

    const dashboardRadioButton = css({
        ...Mixins.padding({
            vertical: 5,
            horizontal: 6,
            left: 0,
        }),
    });

    //.radioButton-label,
    // .checkbox-label
    const label = css({
        lineHeight: 1,
        paddingLeft: styleUnit(8),
        cursor: "pointer",
        ...Mixins.font({
            weight: globalVars.fonts.weights.normal,
        }),
        ...userSelect(),
    });

    const labelBold = css({
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
        }),
    });

    const labelNote = css({
        display: "inline-block",
        fontSize: styleUnit(vars.labelNote.fontSize),
        marginLeft: styleUnit(24),
        opacity: vars.labelNote.opacity,
        verticalAlign: "middle",
    });

    // .radioButton-disk,
    // .checkbox-box
    const iconContainer = css({
        ...defaultTransition("border", "background", "opacity"),
        position: "relative",
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        width: styleUnit(vars.sizing.width),
        height: styleUnit(vars.sizing.width),
        flexBasis: styleUnit(vars.sizing.width),
        minWidth: styleUnit(vars.sizing.width),
        verticalAlign: "middle",
        cursor: "pointer",
        backgroundColor: ColorsUtils.colorOut(vars.main.bg),
        ...Mixins.border(vars.border),
    });

    const radioIcon = css({
        ...Mixins.absolute.middleLeftOfParent(),
        display: "none",
        width: styleUnit(vars.radioButton.icon.width),
        height: styleUnit(vars.radioButton.icon.height),
        margin: "auto",
    });

    const checkIcon = css({
        ...Mixins.absolute.middleOfParent(),
        display: "none",
        width: styleUnit(vars.checkBox.check.width),
        height: styleUnit(vars.checkBox.check.height),
        color: "inherit",
        margin: "auto",
    });

    const tooltipIcon = css({
        ...Mixins.verticallyAlignInContainer(24, 1),
    });
    const tooltipIconContainer = css({
        marginLeft: 4,
        maxHeight: "1em",
    });

    const disk = css({
        borderRadius: percent(50),
        aspectRatio: "1 / 1",
    });

    const diskIcon = css({
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
    const input = css({
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
    const root = css({
        display: important("flex"),
        alignItems: "center",
        outline: 0,
        ...Mixins.padding(vars.spacing),
        [`&&`]: {
            margin: 0,
        },
        [`&.isHorizontal.isHorizontal.isHorizontal`]: Mixins.margin({
            all: 0,
            right: px(globalVars.spacer.size / 2),
        }),
        [`&.${dashboardRadioButton} + .info`]: {
            ...Mixins.margin({
                top: styleUnit(2),
                bottom: styleUnit(6),
            }),
        },
        "&.minContent": {
            width: "min-content",
        },
    });

    const fullWidth = css({
        width: "100%",
    });

    const grid = css({
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

    const checkBoxDescription = css({
        marginLeft: 25,
        marginTop: -5,
        ...Mixins.font(metasVariables().font),
    });

    const tooltipPerOption = css({
        marginLeft: 8,
        marginBottom: -4,
    });

    const radioNote = css({
        marginLeft: 30,
    });

    return {
        root,
        label,
        labelBold,
        labelNote,
        iconContainer,
        radioIcon,
        checkIcon,
        tooltipIcon,
        tooltipIconContainer,
        tooltipPerOption,
        fullWidth,
        disk,
        diskIcon,
        input,
        grid,
        dashboardRadioButton,
        checkBoxDescription,
        radioNote,
    };
});
