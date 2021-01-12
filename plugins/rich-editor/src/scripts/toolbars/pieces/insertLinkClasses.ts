/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const insertLinkClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("insertLink");

    const root = style({
        position: "relative",
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        maxWidth: styleUnit(vars.insertLink.width),
        width: percent(100),
        paddingLeft: 0,
        overflow: "hidden",
    });

    const input = style("input", {
        zIndex: 2,
        ...{
            "&, &.InputBox": {
                border: important("0"),
                marginBottom: important("0"),
                flexGrow: 1,
                maxWidth: calc(`100% - ${styleUnit(vars.menuButton.size - (vars.menuButton.size - 12) / 2)}`), // 12 is from the size set SCSS file.
            },
        },
    });

    return { root, input };
});
