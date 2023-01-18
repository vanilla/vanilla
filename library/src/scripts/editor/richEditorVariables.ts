/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { standardAnimations } from "@library/styles/animationHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { viewHeight } from "csx";

export const richEditorVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();
    const makeThemeVars = variableFactory("richEditor"); //fixme: update namespace?
    const animations = standardAnimations();

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
        outline: globalVars.mainColors.primary.fade(0.6),
    });

    const spacing = makeThemeVars("spacing", {
        paddingLeft: 36,
        paddingRight: 36,
        paddingTop: 12,
        paddingBottom: 12,
        embedMenu: 0,
    });

    const sizing = makeThemeVars("sizing", {
        minHeight: 200,
        emojiSize: 40,
    });

    const menuButton = makeThemeVars("menuButton", {
        size: 36,
    });

    const paragraphMenuHandle = makeThemeVars("paragraphMenuHandle", {
        size: 28,
        offset: -varsFormElements.border.width + 1,
    });

    const insertLink = makeThemeVars("insertLink", {
        width: 287,
    });

    const flyout = makeThemeVars("flyout", {
        padding: {
            vertical: 12,
            horizontal: 12,
        },
        maxHeight: viewHeight(100),
        height: menuButton.size,
    });

    const pilcrow = makeThemeVars("pilcrow", {
        offset: 0,
        fontSize: 14,
        animation: {
            duration: ".3s",
            name: animations.fadeIn,
            timing: "ease-out",
            iterationCount: 1,
        },
    });

    const embedMenu = makeThemeVars("embedMenu", {
        padding: 0,
        mobile: {
            border: {
                color: globalVars.mainColors.primary,
            },
            transition: {
                duration: ".15s",
                timing: "ease-out",
            },
        },
    });

    const text = makeThemeVars("text", {
        offset: 0,
        placeholder: {
            color: globalVars.mixBgAndFg(0.5),
        },
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
        spacing,
        sizing,
        menuButton,
        paragraphMenuHandle,
        insertLink,
        flyout,
        pilcrow,
        colors,
        text,
        title,
        embedMenu,
        emojiBody,
        buttonContents,
        emojiHeader,

        iconWrap,
        richEditorWidth,
        modernFrame,
    };
});
