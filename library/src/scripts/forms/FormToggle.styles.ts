/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { IThemeVariables } from "@library/theming/themeReducer";
import { important } from "csx";

export const formToggleVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const inputVars = inputVariables(forcedVars);
    const makeVars = variableFactory("formToggle", forcedVars);

    const options = makeVars("options", {
        slim: false,
    });

    const sizing = makeVars("sizing", {
        height: options.slim ? 30 : inputVars.sizing.height,
        gutter: 3,
    });

    const well = makeVars("well", {
        height: sizing.height,
        width: sizing.height * 2,
        border: {
            ...inputVars.border,
            radius: sizing.height,
        },
        color: globalVars.mainColors.bg,
        colorActive: globalVars.mainColors.primary,
        colorActiveState: globalVars.mainColors.secondary,
    });

    const slider = makeVars("sizing", {
        height: sizing.height - sizing.gutter * 2,
        width: sizing.height - sizing.gutter * 2,
        border: {
            ...inputVars.border,
            radius: sizing.height,
        },
        color: globalVars.mainColors.bg,
    });

    return { options, sizing, well, slider };
});

export const formToggleClasses = useThemeCache((forcedVars?: IThemeVariables) => {
    const vars = formToggleVariables(forcedVars);
    const style = styleFactory("formToggle");
    const globalVars = globalVariables(forcedVars);

    const well = style("well", {
        cursor: "pointer",
        display: "block",
        position: "relative",
        height: vars.well.height,
        width: vars.well.width,
        ...Mixins.border(vars.well.border),
        backgroundColor: ColorsUtils.colorOut(vars.well.color),
        transition: "0.3s linear background, 0.3s linear border",
    });

    const slider = style("slider", {
        cursor: "pointer",
        height: vars.slider.height,
        width: vars.slider.width,
        ...Mixins.border(vars.slider.border),
        position: "absolute",
        top: vars.sizing.gutter,
        bottom: vars.sizing.gutter,
        left: vars.sizing.gutter,
        backgroundColor: ColorsUtils.colorOut(vars.slider.color),
        transition: "0.3s linear border, 0.2s linear left",
    });

    const root = style({
        display: "block",
        position: "relative",
        height: vars.well.height,
        width: vars.well.width,
        ...{
            [`&.isOn .${slider}`]: {
                left: vars.well.width / 2 + vars.sizing.gutter,
                ...Mixins.border({ ...vars.slider.border, color: vars.slider.color }),
            },
            [`&.isOn .${well}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.well.colorActive),
                ...Mixins.border({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isIndeterminate .${slider}`]: {
                left: vars.slider.width / 2 + vars.sizing.gutter,
            },
            [`&.isFocused:not(.isDisabled):not(.isFocused) .${well}, &:not(.isDisabled):hover .${well}`]: {
                ...Mixins.border({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isFocused:not(.isDisabled):not(.isFocused) .${slider}, &:not(.isDisabled):hover .${slider}`]: {
                ...Mixins.border({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isOn.isFocused:not(.isDisabled):not(.isFocused) .${well}, &.isOn:not(.isDisabled):hover .${well}`]: {
                ...Mixins.border({ ...vars.well.border, color: vars.well.colorActiveState }),
                backgroundColor: ColorsUtils.colorOut(vars.well.colorActiveState),
            },
            [`&.isOn.isFocused:not(.isDisabled):not(.isFocused) .${slider}, &.isOn:not(.isDisabled):hover .${slider}`]: {
                ...Mixins.border({ ...vars.slider.border, color: vars.slider.color }),
            },
            [`&.isDisabled`]: {
                cursor: important("default"),
                opacity: 0.5,
            },
            [`&.isDisabled .${slider}`]: {
                cursor: important("default"),
            },
            [`&.isDisabled .${well}`]: {
                cursor: important("default"),
            },
        },
    });

    const visibleLabel = style("visibleLabel", {
        ...flexHelper().middleLeft(),
        fontWeight: globalVars.fonts.weights.bold,
        ...Mixins.padding({
            right: globalVars.gutter.half,
            vertical: globalVars.gutter.quarter,
        }),
        ...{
            "& svg": {
                marginLeft: 6,
            },
        },
    });
    const visibleLabelContainer = style("visibleLabelContainer", {
        ...flexHelper().middle(),
        justifyContent: "space-between",
        ...Mixins.margin({
            vertical: globalVars.gutter.half,
        }),
    });

    return { root, well, slider, visibleLabel, visibleLabelContainer };
});
