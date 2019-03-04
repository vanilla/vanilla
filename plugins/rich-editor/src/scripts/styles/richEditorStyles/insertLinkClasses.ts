/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { toStringColor, unit } from "@library/styles/styleHelpers";
import { calc, important, percent } from "csx";
import styleFactory from "@library/styles/styleFactory";
import memoize from "lodash/memoize";
import { globalVariables } from "@library/styles/globalStyleVars";

export const insertLinkClasses = memoize((theme?: object) => {
    const vars = richEditorVariables(theme);
    const globalVars = globalVariables(theme);
    const style = styleFactory("insertLink");

    const root = style({
        position: "relative",
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        maxWidth: unit(vars.insertLink.width),
        width: percent(100),
        paddingLeft: unit(vars.insertLink.leftPadding),
    });

    const input = style("input", {
        zIndex: 2,
        $nest: {
            "&, &.InputBox": {
                border: important("0"),
                marginBottom: important("0"),
                flexGrow: 1,
                maxWidth: calc(`100% - ${unit(vars.menuButton.size)}`),
            },
        },
    });

    return { root, input };
});
