/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { percent, px } from "csx";

export const insertMediaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const style = styleFactory("insertMedia");

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        ...paddings({
            left: vars.flyout.padding.horizontal,
            right: vars.flyout.padding.horizontal,
            bottom: vars.flyout.padding.vertical,
        }),
    });

    const help = style("help", {
        marginRight: "auto",
        fontSize: unit(globalVars.fonts.size.small),
    });

    const insert = style("insert", {
        $nest: {
            "&&": {
                // Nest deeper to override margins from the forum.
                width: percent(100),
                position: "relative",
                marginBottom: px(10),
            },
        },
    });
    const button = style("button", {
        position: "relative",
        marginLeft: "auto",
    });

    const footer = style("footer", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        padding: 0,
    });

    return { root, help, insert, footer, button };
});
