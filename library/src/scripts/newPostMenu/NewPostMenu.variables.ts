/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";

export const newPostMenuVariables = useThemeCache(() => {
    const themeVars = variableFactory("newPostMenu");

    /**
     * @var newPostMenu.button
     * @title NewPostMenu button
     * @description To apply some border and color styling to NewPostMenu button
     */
    const button = themeVars("button", {
        /**
         * @varGroup newPostMenu.button.border
         * @title Border
         * @expand border
         */
        border: Variables.border({ radius: 24, width: 0 }),

        /**
         * @varGroup newPostMenu.button.font
         * @title Font
         * @expand font
         */
        font: Variables.font({ size: 16, weight: 700 }),
    });

    /**
     * @var newPostMenu.fab
     * @title Floating Action Button
     * @description On smaller views, NewPostMenu will be rendered as a FAB at bottom right section of the view
     */
    const fab = themeVars("fab", {
        size: 56,
        spacing: Variables.spacing({ top: 24 }),
        opacity: {
            open: 1,
            close: 0,
        },
        degree: {
            open: -135,
            close: 0,
        },
        /**
         * @var newPostMenu.fab.iconsOnly
         * @title FAB Icons Only
         * @description If true, labels won't be shown, only icons
         */
        iconsOnly: false,
        position: {
            bottom: 40,
            right: 24,
        },
    });

    /**
     * @var newPostMenu.fabItem
     * @title FAB Item
     * @description Apply some dynamic styles for fab item.
     */
    const fabItem = themeVars("fabItem", {
        opacity: {
            open: 1,
            close: 0,
        },
        transformY: {
            open: 0,
            close: 100,
        },
    });

    /**
     * @var newPostMenu.fabAction
     * @title FAB Action
     * @description Styles for fab actions (normally urls)
     */
    const fabAction = themeVars("fabAction", {
        border: Variables.border({ radius: fab.iconsOnly ? "50%" : 21.5, width: 0 }),
        spacing: Variables.spacing({
            horizontal: fab.iconsOnly ? 9 : 18,
        }),
        size: {
            height: 44,
            width: fab.iconsOnly ? 44 : undefined,
        },
    });

    /**
     * @var newPostMenu.fabMenu
     * @title FAB Menu
     * @description Some dynamic styles for fab menu
     */
    const fabMenu = themeVars("fabMenu", {
        display: {
            open: "block",
            close: "none",
        },
        opacity: {
            open: 1,
            close: 0,
        },
    });

    return {
        button,
        fabItem,
        fabAction,
        fab,
        fabMenu,
    };
});
