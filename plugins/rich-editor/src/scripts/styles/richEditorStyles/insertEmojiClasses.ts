/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { appearance, unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { viewHeight } from "csx";

export function insertEmojiClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const vars = richEditorVariables(theme);
    const style = styleFactory("insertEmoji");

    const root = style({
        ...appearance(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        fontSize: unit(globalVars.icon.sizes.default),
        textAlign: "center",
        overflow: "hidden",
        opacity: globalVars.states.text.opacity,
        $nest: {
            ".fallBackEmoji": {
                display: "block",
                margin: "auto",
            },
            "&:hover, &:focus, &:active, &.focus-visible": {
                opacity: 1,
            },
        },
    });

    const body = style("body", {
        height: unit(vars.emojiBody.height),
        maxHeight: viewHeight(80),
    });

    const popoverDescription = style("popoverDescription", {
        marginBottom: ".5em",
    });

    return { root, body, popoverDescription };
}
