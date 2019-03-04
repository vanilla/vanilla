/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { unit } from "@library/styles/styleHelpers";
import { memoizeTheme, styleFactory } from "@library/styles/styleUtils";
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";

export const emojiGroupsClasses = memoizeTheme(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
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
});
