/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import { colorOut, fonts, negative, paddings, srOnly, unit } from "@library/styles/styleHelpers";
import { percent } from "csx";
import { radioTabsVariables } from "@library/forms/radioTabs/radioTabStyles";

export interface IRadioInputAsButtonClasses {
    root: string;
    items: string;
    item: string;
    label: string;
    input: string;
}

export const radioInputAsButtonClasses = useThemeCache(() => {
    const vars = radioTabsVariables();
    const style = styleFactory("radioTab");
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();

    const root = style({
        display: "block",
    });

    const items = style("items", {
        display: "flex",
        position: "relative",
        alignItems: "center",
        justifyContent: "center",
    });

    const item = style(
        "item",
        {
            ...userSelect(),
            position: "relative",
            display: "inline-block",
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
            "&:hover, &:focus + .radioButtonsAsTabs-label": {
                borderColor: colorOut(vars.border.active.color),
                zIndex: 1,
                color: colorOut(vars.colors.state.fg),
            },
            "&:checked": {
                $nest: {
                    "& + .radioButtonsAsTabs-label": {
                        backgroundColor: colorOut(vars.colors.selected.bg),
                    },
                    "&:hover, &:focus": {
                        color: colorOut(vars.colors.state.fg),
                    },
                },
            },
            "&[disabled] + .radioButtonsAsTabs-label": {
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
