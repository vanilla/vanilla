/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { paddings, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { richEditorVariables } from "../../editor/richEditorVariables";

export const insertMediaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
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
