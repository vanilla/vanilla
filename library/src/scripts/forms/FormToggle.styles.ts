/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { borders, colorOut, defaultTransition, timedTransition } from "@library/styles/styleHelpers";
import { IThemeVariables } from "@library/theming/themeReducer";

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

    const well = style("well", {
        cursor: "pointer",
        display: "block",
        position: "relative",
        height: vars.well.height,
        width: vars.well.width,
        ...borders(vars.well.border),
        backgroundColor: colorOut(vars.well.color),
        transition: "0.3s linear background, 0.3s linear border",
    });

    const slider = style("slider", {
        cursor: "pointer",
        height: vars.slider.height,
        width: vars.slider.width,
        ...borders(vars.slider.border),
        position: "absolute",
        top: vars.sizing.gutter,
        bottom: vars.sizing.gutter,
        left: vars.sizing.gutter,
        backgroundColor: colorOut(vars.slider.color),
        transition: "0.3s linear border, 0.2s linear left",
    });

    const root = style({
        display: "block",
        position: "relative",
        height: vars.well.height,
        width: vars.well.width,
        $nest: {
            [`&.isEnabled .${slider}`]: {
                left: vars.well.width / 2 + vars.sizing.gutter,
                ...borders({ ...vars.slider.border, color: vars.slider.color }),
            },
            [`&.isEnabled .${well}`]: {
                backgroundColor: colorOut(vars.well.colorActive),
                ...borders({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isIndeterminate .${slider}`]: {
                left: vars.slider.width / 2 + vars.sizing.gutter,
            },
            [`&.isFocused .${well}, &:hover .${well}`]: {
                ...borders({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isFocused .${slider}, &:hover .${slider}`]: {
                ...borders({ ...vars.slider.border, color: vars.well.colorActive }),
            },
            [`&.isEnabled.isFocused .${well}, &.isEnabled:hover .${well}`]: {
                ...borders({ ...vars.well.border, color: vars.well.colorActiveState }),
                backgroundColor: colorOut(vars.well.colorActiveState),
            },
            [`&.isEnabled.isFocused .${slider}, &.isEnabled:hover .${slider}`]: {
                ...borders({ ...vars.slider.border, color: vars.slider.color }),
            },
        },
    });

    return { root, well, slider };
});
