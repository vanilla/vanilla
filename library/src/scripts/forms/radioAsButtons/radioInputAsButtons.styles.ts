/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import {
    colorOut,
    fonts,
    IFont,
    margins,
    negative,
    paddings,
    srOnly,
    unit,
    negativeUnit,
} from "@library/styles/styleHelpers";
import { calc, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";

export interface IRadioInputAsButtonClasses {
    root: string;
    items: string;
    item: string;
    label: string;
    input: string;
}

export const radioInputAsButtonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVars = variableFactory("radioTabs");

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

    const sizing = makeVars("sizing", {
        minWidth: 93,
        height: 24,
    });

    const font: IFont = makeVars("font", {
        size: globalVars.fonts.size.small,
        align: "center",
        lineHeight: unit(sizing.height),
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

export const radioInputAsButtonClasses = useThemeCache(() => {
    const vars = radioInputAsButtonVariables();
    const style = styleFactory("radioTab");
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();
    const globalVars = globalVariables();

    const root = style({
        display: "block",
    });

    const items = style(
        "items",
        {
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "flex-start",
            ...margins({
                horizontal: negativeUnit(globalVars.gutter.half),
                vertical: negativeUnit(globalVars.gutter.half),
            }),
        },
        mediaQueries.xs({
            flexWrap: "wrap",
            justifyContent: "stretch",
            width: calc(`100% + ${unit(globalVars.gutter.size)}`),
        }),
    );
    // display: "flex",
    // position: "relative",
    // alignItems: "center",
    // justifyContent: "center",

    //     const item = style(
    //         "tab",
    //         {
    //             ...margins({
    //                 all: globalVars.gutter.half,
    //             }),
    //         },
    //         mediaQueries.xs({
    //             display: "flex",
    //             position: "relative",
    //             alignItems: "center",
    //             justifyContent: "stretch",
    //             flexGrow: 1,
    //         }),
    //     );
    //
    // const item = style(
    //     "item",
    //     {
    //         ...userSelect(),
    //         position: "relative",
    //         display: "inline-block",
    //         flexGrow: 1,
    //         $nest: {
    //             "& + &": {
    //                 marginLeft: unit(negative(vars.border.width)),
    //             },
    //             "&:hover, &:focus, &:active": {
    //                 color: colorOut(vars.colors.state.fg),
    //             },
    //         },
    //     },
    //     mediaQueries.oneColumnDown({
    //         flexGrow: 0,
    //         $nest: {
    //             label: {
    //                 minHeight: unit(formElementVariables.sizing.height),
    //                 lineHeight: unit(formElementVariables.sizing.height),
    //             },
    //         },
    //     }),
    // );

    const item = style(
        "item",
        {
            ...margins({
                all: globalVars.gutter.half,
            }),
        },
        mediaQueries.xs({
            display: "flex",
            position: "relative",
            alignItems: "center",
            justifyContent: "stretch",
            flexGrow: 1,
        }),
    );

    const label = style("label", {
        ...userSelect(),
        display: "inline-block",
        position: "relative",
        cursor: "pointer",
        textAlign: "center",
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
            [`&:hover, &:focus + ${label}`]: {
                borderColor: colorOut(vars.border.active.color),
                zIndex: 1,
                color: colorOut(vars.colors.state.fg),
            },
            "&:checked": {
                $nest: {
                    [`& + .${label}`]: {
                        backgroundColor: colorOut(vars.colors.selected.bg),
                    },
                    "&:hover, &:focus": {
                        color: colorOut(vars.colors.state.fg),
                    },
                },
            },
            [`&[disabled] + .${label}`]: {
                opacity: formElementVariables.disabled.opacity,
            },
        },
    });

    return {
        root,
        items,
        item,
        label,
        input,
    } as IRadioInputAsButtonClasses;
});
