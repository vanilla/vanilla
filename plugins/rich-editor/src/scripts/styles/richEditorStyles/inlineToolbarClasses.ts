/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { percent } from "csx";

export function inlineToolbarClasses(theme?: object) {
    const vars = richEditorVariables(theme);
    const style = styleFactory("inlineToolbar");

    const up = style("up", {
        transform: `translateY(${-vars.menu.offset})`,
        $nest: {
            ".richEditor-nubPosition": {
                top: percent(100),
            },
            ".richEditor-nub": {
                transform: `translateY(-50%) rotate(135deg)`,
            },
        },
    });

    const down = style("down", {
        transform: `translateY(${vars.menu.offset})`,
        $nest: {
            ".richEditor-nubPosition": {
                bottom: percent(100),
                alignItems: "flex-end",
                transform: `translateY(-50%) translateX(-50%)`,
                marginTop: unit(vars.menu.borderWidth),
            },
            ".richEditor-nub": {
                transform: `translateY(-50%) rotate(135deg)`,
            },
        },
    });
    return { up, down };
}
