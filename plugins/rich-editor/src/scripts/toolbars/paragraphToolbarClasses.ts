/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "../../../../../library/src/scripts/forms/formElementStyles";
import { unit } from "../../../../../library/src/scripts/styles/styleHelpers";
import { useThemeCache, styleFactory } from "../../../../../library/src/scripts/styles/styleUtils";
import { richEditorVariables } from "../editor/richEditorVariables";
import { calc } from "csx";

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
