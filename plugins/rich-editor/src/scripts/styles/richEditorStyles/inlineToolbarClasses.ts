/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { important, percent, px, translateY } from "csx";

export function inlineToolbarClasses(theme?: object) {
    const vars = richEditorVariables(theme);
    const style = styleFactory("inlineToolbar");

    const root = style({
        $nest: {
            "&.isUp": {
                transform: `translateY(${unit(-vars.menu.offset)})`,
                $nest: {
                    ".richEditor-nubPosition": {
                        transform: `translateY(-1px) translateX(-50%)`,
                        alignItems: "flex-end",
                        bottom: percent(100),
                    },
                    ".richEditor-nub": {
                        transform: `translateY(-100%) rotate(135deg)`,
                        marginBottom: unit(vars.nub.width / 2),
                    },
                },
            },
            "&.isDown": {
                transform: `translateY(${unit(vars.menu.offset)})`,
                $nest: {
                    ".richEditor-nub": {
                        transform: `translateY(100%) rotate(-45deg)`,
                        marginTop: unit(vars.nub.width / 2),
                    },
                },
            },
        },
    });
    return { root };
}
