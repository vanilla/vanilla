/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import { IFont, margins, srOnly, unit, negativeUnit } from "@library/styles/styleHelpers";
import { calc, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { nestedWorkaround } from "@dashboard/compatibilityStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { buttonVariables } from "@library/forms/buttonStyles";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";

export const radioInputAsButtonVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeVars = variableFactory("radioInputAsButton");

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
    const style = styleFactory("radioInputAsButton");
    const mediaQueries = layoutVariables().mediaQueries();
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
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
    });

    const labelStateStyles = generateButtonStyleProperties(buttonVariables().primary);
    nestedWorkaround(`.${label}`, labelStateStyles.$nest);

    const hiddenInputStates = generateButtonStyleProperties(buttonVariables().primary, false, ` + .${label}`);
    const input = style("input", {
        ...srOnly(),
    });
    nestedWorkaround(`.${input}`, hiddenInputStates.$nest);

    return {
        root,
        items,
        item,
        label,
        input,
    } as IRadioInputAsButtonClasses;
});
