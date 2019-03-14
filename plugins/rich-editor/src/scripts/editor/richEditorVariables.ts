/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "../../../../../library/src/scripts/forms/formElementStyles";
import { standardAnimations } from "../../../../../library/src/scripts/styles/animationHelpers";
import { globalVariables } from "../../../../../library/src/scripts/styles/globalStyleVars";
import { componentThemeVariables } from "../../../../../library/src/scripts/styles/styleHelpers";
import { useThemeCache } from "../../../../../library/src/scripts/styles/styleUtils";
import { viewHeight } from "csx";

export const richEditorVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const varsFormElements = formElementsVariables();
    const themeVars = componentThemeVariables("richEditor");
    const animations = standardAnimations();

    const colors = {
        bg: globalVars.mainColors.bg,
        outline: globalVars.mainColors.primary.fade(0.6),
        ...themeVars.subComponentStyles("colors"),
    };

    const spacing = {
        paddingLeft: 36,
        paddingRight: 36,
        paddingTop: 12,
        paddingBottom: 12,
        embedMenu: 0,
        ...themeVars.subComponentStyles("spacing"),
    };

    const sizing = {
        minHeight: 200,
        ...themeVars.subComponentStyles("sizing"),
    };

    const menuButton = {
        size: varsFormElements.sizing.height,
        ...themeVars.subComponentStyles("menuButton"),
    };

    const paragraphMenu = {
        ...themeVars.subComponentStyles("paragraphMenu"),
    };
    const paragraphMenuHandle = {
        size: 28,
        offset: -varsFormElements.border.width + 1,
        ...themeVars.subComponentStyles("paragraphMenuHandle"),
    };

    const insertLink = {
        width: 287,
        ...themeVars.subComponentStyles("insertLink"),
    };

    const flyout = {
        padding: {
            top: 12,
            right: 12,
            bottom: 12,
            left: 12,
        },
        maxHeight: viewHeight(100),
        height: menuButton.size,
        ...themeVars.subComponentStyles("flyout"),
    };

    const nub = {
        width: 12,
        ...themeVars.subComponentStyles("nub"),
    };

    const menu = {
        borderWidth: 1,
        offset: nub.width * 2,
        ...themeVars.subComponentStyles("menu"),
    };

    const pilcrow = {
        offset: 9,
        fontSize: 14,
        animation: {
            duration: ".3s",
            name: animations.fadeIn,
            timing: "ease-out",
            iterationCount: 1,
        },
        ...themeVars.subComponentStyles("pilcrow"),
    };

    const emojiGroup = {
        paddingLeft: 3,
        offset: -(varsFormElements.border.width + menu.borderWidth * 2),
        ...themeVars.subComponentStyles("emojiGroup"),
    };

    const embedMenu = {
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
        ...themeVars.subComponentStyles("embedMenu"),
    };

    const embedButton = {
        offset: -varsFormElements.border.width,
        ...themeVars.subComponentStyles("embedButton"),
    };

    const text = {
        offset: 0,
        ...themeVars.subComponentStyles("text"),
    };

    const title = {
        height: globalVars.fonts.size.title + globalVars.gutter.half,
        fontSize: globalVars.fonts.size.title,
        placeholder: {
            color: globalVars.mixBgAndFg(0.5),
        },
        ...themeVars.subComponentStyles("titleInput"),
    };

    const scrollContainer = {
        overshoot: 48,
        ...themeVars.subComponentStyles("scrollContainer"),
    };

    const emojiBody = {
        height: 252,
    };

    return {
        colors,
        spacing,
        sizing,
        menuButton,
        paragraphMenu,
        paragraphMenuHandle,
        insertLink,
        flyout,
        nub,
        menu,
        pilcrow,
        emojiGroup,
        embedButton,
        text,
        title,
        embedMenu,
        scrollContainer,
        emojiBody,
    };
});
