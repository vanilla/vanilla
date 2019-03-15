/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library//forms/formElementStyles";
import { useThemeCache, styleFactory } from "@library//styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc } from "csx";

export const paragraphMenuBarToggleClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuBarToggle");

    const root = style({
        position: "absolute",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        top: unit(vars.pilcrow.offset),
        left: 0,
        marginLeft: unit(-globalVars.gutter.quarter + (!legacyMode ? -(globalVars.gutter.size + 6) : 0)),
        transform: `translateX(-100%)`,
        height: unit(vars.paragraphMenuHandle.size),
        width: unit(globalVars.icon.sizes.default),
        animationName: vars.pilcrow.animation.name,
        animationDuration: vars.pilcrow.animation.duration,
        animationTimingFunction: vars.pilcrow.animation.timing,
        animationIterationCount: vars.pilcrow.animation.iterationCount,
        zIndex: 1,
        $nest: {
            ".richEditor-button&.isActive:hover": {
                cursor: "default",
            },
            "&.isMenuInset": {
                transform: "none",
            },
        },
    });
    return { root };
});

export const paragraphToolbarContainerClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphToolbarContainer");

    const root = style({
        position: "absolute",
        left: calc(`50% - ${unit(vars.spacing.paddingLeft / 2)}`),
        $nest: {
            "&.isUp": {
                bottom: calc(`50% + ${unit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
            "&.isDown": {
                top: calc(`50% + ${unit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
        },
    });
    return { root };
});

export const paragraphMenuDropDownClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuDropDown");

    const root = style({});
    return { root };
});

export const paragraphMenuMultiItemsClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuMultiItems");

    const root = style({});
    return { root };
});

export const paragraphMenuItemClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuItem");

    const root = style({});
    return { root };
});

export const paragraphMenuRadioButtonClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuRadioButton");

    const root = style({});
    return { root };
});

export const paragraphMenuCheckBoxClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuCheckBox");

    const root = style({});
    return { root };
});
