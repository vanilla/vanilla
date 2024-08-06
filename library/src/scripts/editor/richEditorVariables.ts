/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { viewHeight } from "csx";

export const richEditorVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("richEditor"); //fixme: update namespace?

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
        outline: globalVars.mainColors.primary.fade(0.6),
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: 200,
        emojiSize: 40,
    });

    const menuButton = makeThemeVars("menuButton", {
        size: 36,
    });

    const flyout = makeThemeVars("flyout", {
        padding: {
            vertical: 12,
            horizontal: 12,
        },
        maxHeight: viewHeight(100),
        height: menuButton.size,
    });

    const title = makeThemeVars("titleInput", {
        height: globalVars.fonts.size.title + globalVars.gutter.half,
        fontSize: globalVars.fonts.size.title,
        placeholder: {
            color: globalVars.mixBgAndFg(0.5),
        },
    });

    const emojiBody = makeThemeVars("emojiBody", {
        height: 252,
        padding: {
            horizontal: 3,
            top: 3,
            bottom: 0,
        },
    });

    const emojiHeader = makeThemeVars("emojiHeader", {
        padding: {
            horizontal: 12,
            vertical: 4,
        },
    });

    const buttonContents = makeThemeVars("buttonContents", {
        state: {
            bg: globalVars.mainColors.primary.fade(0.1),
        },
    });

    const iconWrap = makeThemeVars("iconWrap", {
        width: 32,
        height: 32,
    });

    const richEditorWidth = 8 * sizing.emojiSize;

    const modernFrame = makeThemeVars("modernFrame", {
        padding: 16,
        margin: 16,
    });

    return {
        sizing,
        menuButton,
        flyout,
        colors,
        title,
        emojiBody,
        buttonContents,
        emojiHeader,
        iconWrap,
        richEditorWidth,
        modernFrame,
    };
});
