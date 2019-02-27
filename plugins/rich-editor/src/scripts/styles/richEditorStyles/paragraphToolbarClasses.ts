/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { styleFactory } from "@library/styles/styleHelpers";
import { calc } from "csx";
export function paragraphToolbarContainerClasses(theme?: object) {
    const vars = richEditorVariables(theme);
    const formVars = formElementsVariables(theme);
    const style = styleFactory("paragraphToolbarContainer");

    const root = style({
        position: "absolute",
        left: calc(`50% - ${vars.spacing.paddingLeft / 2}`),
        $nest: {
            "&.isUp": {
                bottom: calc(`50% + ${vars.spacing.paddingRight / 2 - formVars.border.width}`),
            },
            "&.isDown": {
                top: calc(`50% + ${vars.spacing.paddingRight / 2 - formVars.border.width}`),
            },
        },
    });
    return { root };
}
