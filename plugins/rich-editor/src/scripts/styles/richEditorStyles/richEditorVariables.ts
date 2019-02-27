/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { viewHeight } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables } from "@library/styles/styleHelpers";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { standardAnimations } from "@library/styles/animationHelpers";

export function richEditorVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const varsFormElements = formElementsVariables(theme);
    const themeVars = componentThemeVariables(theme, "richEditor");
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

    const paragraphMenuHandle = {
        size: 28,
        offset: -varsFormElements.border.width + 1,
        ...themeVars.subComponentStyles("paragraphMenuHandle"),
    };

    const insertLink = {
        width: 287,
        leftPadding: 9,
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
        offset: nub.width,
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

    return {
        colors,
        spacing,
        sizing,
        menuButton,
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
    };
}
