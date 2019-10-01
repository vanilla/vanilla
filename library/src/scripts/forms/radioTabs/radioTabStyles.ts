/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { cssRule } from "typestyle";
import {
    colorOut,
    unit,
    fonts,
    paddings,
    borders,
    negative,
    srOnly,
    IFont,
    borderRadii,
} from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent } from "csx";

export const radioTabsVariables = useThemeCache(() => {
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

export const radioTabClasses = useThemeCache(() => {
    const vars = radioTabsVariables();
    const style = styleFactory("radioTab");
    const mediaQueries = layoutVariables().mediaQueries();
    const formElementVariables = formElementsVariables();

    const root = style({
        display: "block",
    });

    const tabs = style("tabs", {
        display: "flex",
        position: "relative",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "center",
    });

    const tab = style(
        "tab",
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

    const leftTab = style("leftTab", borderRadii(vars.leftTab.radii));
    const rightTab = style("rightTab", borderRadii(vars.rightTab.radii));

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
        },
    });

    return {
        root,
        tabs,
        tab,
        label,
        input,
        leftTab,
        rightTab,
    };
});
