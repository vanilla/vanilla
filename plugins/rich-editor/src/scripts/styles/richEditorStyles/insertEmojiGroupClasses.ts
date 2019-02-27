/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";

export function emojiGroupsClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const vars = richEditorVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const style = styleFactory("emojiGroups");

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "center",
    });

    const icon = style("icon", {
        display: "block",
        position: "relative",
        margin: "auto",
        padding: 0,
        width: unit(globalVars.icon.sizes.default),
        height: unit(globalVars.icon.sizes.default),
    });

    return { root, icon };
}
