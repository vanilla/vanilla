/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { singleBorder, toStringColor, unit, userSelect } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { translateX } from "csx";
import memoize from "lodash/memoize";

export const nubClasses = memoize((theme?: object) => {
    const globalVars = globalVariables(theme);
    const vars = richEditorVariables(theme);
    const style = styleFactory("nub");

    const root = style({
        position: "relative",
        display: "block",
        width: unit(vars.nub.width),
        height: unit(vars.nub.width),
        borderTop: singleBorder({
            width: vars.menu.borderWidth,
        }),
        borderRight: singleBorder({
            width: vars.menu.borderWidth,
        }),
        boxShadow: globalVars.overlay.dropShadow,
        background: toStringColor(vars.colors.bg),
    });

    const position = style("position", {
        position: "absolute",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "center",
        overflow: "hidden",
        width: unit(vars.nub.width * 2),
        height: unit(vars.nub.width * 2),
        ...userSelect(),
        transform: translateX("-50%"),
        pointerEvents: "none",
    });

    return { root, position };
});
