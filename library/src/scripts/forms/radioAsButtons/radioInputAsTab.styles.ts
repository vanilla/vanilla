/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import { borderRadii, negative } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { important, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { CSSObject } from "@emotion/css";

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

    const font = makeVars(
        "font",
        Variables.font({
            size: globalVars.fonts.size.small,
            align: "center",
            lineHeight: styleUnit(sizing.height),
        }),
    );

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
            ...{
                "& + &": {
                    marginLeft: styleUnit(negative(vars.border.width)),
                },
                "&:hover, &:focus, &:active": {
                    color: ColorsUtils.colorOut(vars.colors.state.fg),
                },
            },
        },
        mediaQueries.oneColumnDown({
            flexGrow: 0,
            ...{
                label: {
                    minHeight: styleUnit(formElementVariables.sizing.height),
                    lineHeight: styleUnit(formElementVariables.sizing.height),
                },
            },
        }),
    );

    const leftTab = style("leftTab", borderRadii(vars.leftTab.radii) as CSSObject);
    const rightTab = style("rightTab", borderRadii(vars.rightTab.radii) as CSSObject);

    const label = style("label", {
        ...userSelect(),
        display: "inline-flex",
        position: "relative",
        cursor: "pointer",
        textAlign: "center",
        justifyContent: "center",
        width: percent(100),
        minHeight: styleUnit(vars.sizing.height),
        minWidth: styleUnit(vars.sizing.minWidth),
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        ...Mixins.padding(vars.spacing.paddings),
        ...Mixins.font(vars.font),
        borderColor: ColorsUtils.colorOut(vars.border.color),
        borderWidth: styleUnit(vars.border.width),
        borderStyle: vars.border.style,
    });

    const input = style("input", {
        ...Mixins.absolute.srOnly(),
        ...{
            [`
                &:not([disabled]):hover + .${label},
                &:not([disabled]):focus + .${label}
            `]: {
                borderColor: ColorsUtils.colorOut(vars.border.active.color),
                zIndex: 1,
                color: ColorsUtils.colorOut(vars.colors.state.fg),
            },
            [`&:not([disabled]):checked + .${label}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.colors.selected.bg),
            },
            [`&:not([disabled]):checked:hover + .${label}`]: {
                color: ColorsUtils.colorOut(vars.colors.state.fg),
            },
            [`&:not([disabled]):checked:focus + .${label}`]: {
                color: ColorsUtils.colorOut(vars.colors.state.fg),
            },
            [`&.focus-visible:not([disabled]):checked + .${label}`]: {
                color: ColorsUtils.colorOut(vars.colors.state.fg),
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
