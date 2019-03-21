/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library//forms/formElementStyles";
import { useThemeCache, styleFactory } from "@library//styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";

export const paragraphMenuCheckRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const style = styleFactory("paragraphMenuCheckRadio");

    const group = style("group", {});
    const checkRadio = style("checkRadio", {});
    const check = style("check", {});
    const radio = style("radio", {});
    const checked = style("checked", {});
    const separator = style("checked", {});

    return { group, checkRadio, check, radio, checked };
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
    return { root, content, tabHandle, activeTabHandle };
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
