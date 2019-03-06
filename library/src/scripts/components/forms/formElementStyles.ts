/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, styleFactory } from "@library/styles/styleHelpers";
import { color } from "csx/lib/color";
import { px } from "csx";
import { layoutVariables } from "@library/styles/layoutStyles";

export function formElementsVariables(theme?: object) {
    const vars = globalVariables(theme);
    const varsLayouts = layoutVariables(theme);
    const mixBgAndFg = vars.mixBgAndFg;
    const themeVars = componentThemeVariables(theme, "formElements");

    const sizing = {
        height: 36,
        halfHeight: 18,
        maxWidth: 528,
        ...themeVars.subComponentStyles("sizing"),
    };

    const spacing = {
        margin: 12,
        horizontalPadding: 12,
        verticalPadding: 6,
        ...themeVars.subComponentStyles("spacing"),
    };

    const border = {
        width: 1,
        fullWidth: 2,
        color: vars.border.color,
        style: "solid",
        radius: vars.border.radius,
        ...themeVars.subComponentStyles("border"),
    };

    const giantInput = {
        height: 82,
        fontSize: 24,
        ...themeVars.subComponentStyles("giantInput"),
    };

    const largeInput = {
        height: 48,
        fontSize: 16,
        ...themeVars.subComponentStyles("largeInput"),
    };

    const miniInput = {
        width: 100,
        ...themeVars.subComponentStyles("miniInput"),
    };

    const colors = {
        fg: mixBgAndFg(0.8),
        bg: vars.mainColors.bg,
        ...themeVars.subComponentStyles("colors"),
    };

    const errorSpacing = {
        horizontalPadding: varsLayouts.gutter.size,
        verticalPadding: varsLayouts.gutter.size,
        verticalMargin: varsLayouts.gutter.halfSize,
        ...themeVars.subComponentStyles("errorSpacing"),
    };

    const placeholder = {
        color: mixBgAndFg(0.5),
        ...themeVars.subComponentStyles("placeholder"),
    };

    const disabled = {
        opacity: 0.5,
    };

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
}

export function formErrorClasses(theme?: object) {
    const style = styleFactory("formError");
    const varsGlobal = globalVariables(theme);
    const vars = formElementsVariables(theme);

    const root = style({
        backgroundColor: varsGlobal.feedbackColors.error.bg.toString(),
        color: varsGlobal.feedbackColors.error.fg.toString(),
        marginBottom: px(16),
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
        marginLeft: px(12),
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
}
