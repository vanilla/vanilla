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

export const paragraphMenuBarClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuBar");

    const toggle = style("toggle", {
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

    const position = style("position", {
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

    const menuBar = style("menuBar", {
        display: "flex",
    });

    const separator = style("separator", {});

    return { toggle, position, menuBar, separator };
});

export const paragraphMenuCheckRadioClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuRadioButtons");

    const group = style("group", {});
    const checkRadio = style("checkRadio", {});
    const check = style("check", {});
    const radio = style("radio", {});
    const checked = style("checked", {});
    const separator = style("checked", {});

    return { group, checkRadio, check, radio, checked };
});

export const paragraphMenuTabsClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuTabContent");
    const root = style({});
    const content = style("content", {});
    return { root, content };
});

export const paragraphMenuGroupClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuTabContent");
    const root = style({});

    return { root };
});
