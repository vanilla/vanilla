/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { singleBorder, toStringColor, unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";

export function nubPositionClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const vars = richEditorVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const style = styleFactory("nubPosition");

    const root = style({
        position: "relative",
        display: "block",
        width: unit(vars.nub.width),
        height: unit(vars.nub.width),
        borderTop: singleBorder(),
        borderRight: singleBorder(),
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
        userSelect: "none",
        transform: `translateX(-50%)`,
        marginTop: unit(-vars.menu.borderWidth),
        pointerEvents: "none",
    });

    return { root, position };
}
