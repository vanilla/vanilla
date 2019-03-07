/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit } from "@library/styles/styleHelpers";

export const formElementsVariables = useThemeCache(() => {
    const vars = globalVariables();
    const varsLayouts = layoutVariables();
    const mixBgAndFg = vars.mixBgAndFg;
    const makeThemeVars = variableFactory("formElements");

    const sizing = makeThemeVars("sizing", {
        height: 36,
        halfHeight: 18,
        maxWidth: 528,
    });

    const spacing = makeThemeVars("spacing", {
        margin: 12,
        horizontalPadding: 12,
        verticalPadding: 6,
    });

    const border = makeThemeVars("border", {
        width: 1,
        fullWidth: 2,
        color: vars.border.color,
        style: "solid",
        radius: vars.border.radius,
    });

    const giantInput = makeThemeVars("giantInput", {
        height: 82,
        fontSize: 24,
    });

    const largeInput = makeThemeVars("largeInput", {
        height: 48,
        fontSize: 16,
    });

    const miniInput = makeThemeVars("miniInput", {
        width: 100,
    });

    const colors = makeThemeVars("colors", {
        fg: vars.mainColors.fg,
        bg: vars.mainColors.bg,
    });

    const errorSpacing = makeThemeVars("errorSpacing", {
        horizontalPadding: varsLayouts.gutter.size,
        verticalPadding: varsLayouts.gutter.size,
        verticalMargin: varsLayouts.gutter.halfSize,
    });

    const placeholder = makeThemeVars("placeholder", {
        color: mixBgAndFg(0.5),
    });

    const disabled = makeThemeVars("disabled", {
        opacity: 0.5,
    });

    return {
        sizing,
        errorSpacing,
        spacing,
        border,
        giantInput,
        largeInput,
        miniInput,
        colors,
        placeholder,
        disabled,
    };
});

export const formErrorClasses = useThemeCache(() => {
    const style = styleFactory("formError");
    const varsGlobal = globalVariables();
    const vars = formElementsVariables();

    const root = style({
        backgroundColor: varsGlobal.feedbackColors.error.bg.toString(),
        color: varsGlobal.feedbackColors.error.fg.toString(),
        marginBottom: unit(16),
        paddingLeft: vars.errorSpacing.horizontalPadding,
        paddingRight: vars.errorSpacing.horizontalPadding,
        paddingTop: vars.errorSpacing.verticalPadding,
        paddingBottom: vars.errorSpacing.verticalPadding,
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
    });
    const actions = style("actions", {
        display: "flex",
        alignItems: "center",
    });

    const actionButton = style("button", {
        marginLeft: unit(12),
    });
    const activeButton = style("activeButton", {
        fontWeight: "bold",
    });

    return {
        actionButton,
        root,
        activeButton,
        actions,
    };
});
