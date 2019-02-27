/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { layoutVariables } from "@library/styles/layoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { paddings, unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";

export function insertMediaClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const vars = richEditorVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const style = styleFactory("insertMedia");

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        ...paddings({
            left: vars.flyout.padding.left,
            right: vars.flyout.padding.left,
            bottom: vars.flyout.padding.bottom,
        }),
    });

    const help = style("help", {
        marginRight: "auto",
        fontSize: unit(globalVars.fonts.size.small),
    });

    const insert = style("insert", {
        width: "auto",
    });

    const footer = style("footer", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        ...paddings(vars.flyout.padding),
    });

    return { root, help, insert };
}
