/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc } from "csx";
import styleFactory from "@library/styles/styleFactory";
import { unit } from "@library/styles/styleHelpers";
import memoize from "lodash/memoize";

export const paragraphToolbarContainerClasses = memoize(() => {
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
