/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { appearance, buttonStates } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { viewHeight } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";

export const insertEmojiClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const style = styleFactory("insertEmoji");

    const root = style({
        ...appearance(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        fontSize: styleUnit(globalVars.icon.sizes.default),
        textAlign: "center",
        overflow: "hidden",
        border: 0,
        opacity: globalVars.states.text.opacity,
        cursor: "pointer",
        borderRadius: styleUnit(3),
        background: "transparent",
        ...buttonStates(
            {
                allStates: {
                    outline: 0,
                },
                hover: {
                    opacity: 1,
                },
                focus: {
                    opacity: 1,
                },
                active: {
                    opacity: 1,
                },
                keyboardFocus: {
                    backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
                },
            },
            {
                ".fallBackEmoji": {
                    display: "block",
                    margin: "auto",
                },
                ".safeEmoji": {
                    display: "block",
                    height: styleUnit(globalVars.icon.sizes.default),
                    width: styleUnit(globalVars.icon.sizes.default),
                    margin: "auto",
                },
            },
        ),
    });

    const body = style("body", {
        height: styleUnit(vars.emojiBody.height),
        maxHeight: viewHeight(80),
    });

    const popoverDescription = style("popoverDescription", {
        marginBottom: ".5em",
    });

    return { root, body, popoverDescription };
});
