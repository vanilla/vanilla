/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";

export function selectBoxClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const debug = debugHelper("selectBox");

    const toggle = style({
        ...debug.name("toggle"),
    });

    const buttonItem = style({
        ...debug.name("buttonItem"),
    });

    const buttonIcon = style({
        ...debug.name("buttonIcon"),
    });

    const outdated = style({
        ...debug.name("outdated"),
    });

    const dropDownContents = style({
        ...debug.name("dropDownContents"),
    });

    const checkContainer = style({
        ...debug.name("checkContainer"),
    });

    const spacer = style({
        ...debug.name("spacer"),
    });

    const itemLabel = style({
        ...debug.name("itemLabel"),
    });

    return {
        toggle,
        buttonItem,
        buttonIcon,
        outdated,
        dropDownContents,
        checkContainer,
        spacer,
        itemLabel,
    };
}
