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
import memoize from "lodash/memoize";

export const insertMediaClasses = memoize((theme?: object) => {
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
        position: "relative",
    });

    const footer = style("footer", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        padding: 0,
    });

    return { root, help, insert, footer };
});
