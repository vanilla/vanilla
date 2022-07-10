/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { translateX } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";

export const nubClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const style = styleFactory("nub");

    const root = style({
        position: "relative",
        display: "block",
        width: styleUnit(vars.nub.width),
        height: styleUnit(vars.nub.width),
        borderTop: singleBorder({
            width: vars.menu.borderWidth,
        }),
        borderRight: singleBorder({
            width: vars.menu.borderWidth,
        }),
        boxShadow: globalVars.overlay.dropShadow,
        background: ColorsUtils.colorOut(vars.colors.bg),
    });

    const position = style("position", {
        position: "absolute",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "center",
        overflow: "hidden",
        width: styleUnit(vars.nub.width * 2),
        height: styleUnit(vars.nub.width * 2),
        ...userSelect(),
        transform: translateX("-50%"),
        pointerEvents: "none",
    });

    return { root, position };
});
