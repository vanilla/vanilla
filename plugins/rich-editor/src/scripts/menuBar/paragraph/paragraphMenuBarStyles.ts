/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library//forms/formElementStyles";
import { useThemeCache, styleFactory } from "@library//styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { percent } from "csx";

export const paragraphMenuCheckRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuCheckRadio");

    const group = style("group", {});
    const checkRadio = style("checkRadio", {
        display: "flex",
        width: percent(100),
    });
    const check = style("check", {});
    const radio = style("radio", {});
    const checked = style("checked", {});
    const separator = style("checked", {});
    const checkRadioWrap = style("checkRadioWrap", {});
    const checkRadioLabel = style("checkRadioLabel", {});

    return {
        group,
        checkRadio,
        check,
        radio,
        checked,
        separator,
        checkRadioWrap,
        checkRadioLabel,
    };
});

export const paragraphMenuTabsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuTabs");
    const root = style({});
    const content = style("content", {});
    const tabHandle = style("tabHandle", {});
    const activeTabHandle = style("activeTabHandle", {});
    const panel = style("panel", {});
    return { root, content, tabHandle, activeTabHandle, panel };
});

export const paragraphMenuGroupClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuGroup");
    const root = style({});

    return { root };
});

export const paragraphMenuBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuBar");
    const root = style({});

    return { root };
});
