/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import {
    IFont,
    margins,
    srOnly,
    unit,
    colorOut,
    fonts,
    paddings,
    borderRadii,
    negative,
} from "@library/styles/styleHelpers";
import { calc, important, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const radioInputAsTabVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVars = variableFactory("radioInputAsTabs");

    const sizing = makeVars("sizing", {
        minWidth: 93,
        height: 24,
    });

    const colors = makeVars("colors", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        state: {
            border: {
                color: globalVars.mixPrimaryAndBg(0.5),
            },
            fg: globalVars.mainColors.primary,
        },
        selected: {
            bg: globalVars.mainColors.primary.desaturate(0.3).fade(0.05),
            fg: globalVars.mainColors.fg,
        },
    });

    const spacing = makeVars("spacing", {
        paddings: {
            horizontal: 8,
        },
    });

    const border = makeVars("border", {
        width: globalVars.border.width,
        color: globalVars.border.color,
        radius: 0,
        style: globalVars.border.style,
        active: {
            color: globalVars.mixPrimaryAndBg(0.5),
        },
    });

    const font: IFont = makeVars("font", {
        size: globalVars.fonts.size.small,
        align: "center",
        lineHeight: unit(sizing.height),
    });

    const leftTab = makeVars("leftTab", {
        radii: {
            left: 3,
            right: 0,
        },
    });

    const rightTab = makeVars("rightTab", {
        radii: {
            right: 3,
            left: 0,
        },
    });

    return {
        colors,
        sizing,
        font,
        spacing,
        border,
        leftTab,
        rightTab,
    };
});

export const radioInputAsTabClasses = useThemeCache(() => {
    const style = styleFactory("radioInputAsTab");
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = radioInputAsTabVariables();
    const formElementVariables = formElementsVariables();

    const root = style({
        display: "block",
    });

    const items = style("items", {
        display: "flex",
        position: "relative",
        alignItems: "center",
        justifyContent: "stretch",
    });

    const item = style(
        "item",
        {
            ...userSelect(),
            position: "relative",
            display: "inline-flex",
            flexGrow: 1,
            $nest: {
                "& + &": {
                    marginLeft: unit(negative(vars.border.width)),
                },
                "&:hover, &:focus, &:active": {
                    color: colorOut(vars.colors.state.fg),
                },
            },
        },
        mediaQueries.oneColumnDown({
            flexGrow: 0,
            $nest: {
                label: {
                    minHeight: unit(formElementVariables.sizing.height),
                    lineHeight: unit(formElementVariables.sizing.height),
                },
            },
        }),
    );

    const leftTab = style("leftTab", borderRadii(vars.leftTab.radii));
    const rightTab = style("rightTab", borderRadii(vars.rightTab.radii));

    const label = style("label", {
        ...userSelect(),
        display: "inline-flex",
        position: "relative",
        cursor: "pointer",
        textAlign: "center",
        justifyContent: "center",
        width: percent(100),
        minHeight: unit(vars.sizing.height),
        minWidth: unit(vars.sizing.minWidth),
        backgroundColor: colorOut(vars.colors.bg),
        ...fonts(vars.font),
        ...paddings(vars.spacing.paddings),
        borderColor: colorOut(vars.border.color),
        borderWidth: unit(vars.border.width),
        borderStyle: vars.border.style,
    });

    const input = style("input", {
        ...srOnly(),
        $nest: {
            [`
                &:not([disabled]):hover + .${label},
                &:not([disabled]):focus + .${label}
            `]: {
                borderColor: colorOut(vars.border.active.color),
                zIndex: 1,
                color: colorOut(vars.colors.state.fg),
            },
            [`&:not([disabled]):checked + .${label}`]: {
                backgroundColor: colorOut(vars.colors.selected.bg),
            },
            [`&:not([disabled]):checked:hover + .${label}`]: {
                color: colorOut(vars.colors.state.fg),
            },
            [`&:not([disabled]):checked:focus + .${label}`]: {
                color: colorOut(vars.colors.state.fg),
            },
            [`&.focus-visible:not([disabled]):checked + .${label}`]: {
                color: colorOut(vars.colors.state.fg),
            },
            [`&[disabled] + .${label}`]: {
                opacity: formElementVariables.disabled.opacity,
                cursor: important("default"),
            },
        },
    });

    return {
        root,
        items,
        item,
        input,
        label,
        leftTab,
        rightTab,
    } as IRadioInputAsButtonClasses;
});
