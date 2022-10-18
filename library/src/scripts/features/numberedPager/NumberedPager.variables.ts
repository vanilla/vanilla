/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IOverrides {}

export const numberedPagerVariables = useThemeCache((overrides?: IOverrides, forcedVars?: IThemeVariables) => {
    /**
     * @varGroup numberedPager
     * @description Variables for the numbered pager.
     */
    const makeThemeVars = variableFactory("numberedPager", forcedVars);
    const globalVars = globalVariables(forcedVars);

    /**
     * @varGroup numberedPager.background
     * @expand background
     * @description Set the background options for the pagination wrapper box.
     */
    const background = makeThemeVars("background", Variables.background({}));

    /**
     * @varGroup numberedPager.border
     * @expand border
     * @description Set the border options for the pagination wrapper box.
     */
    const border = makeThemeVars(
        "border",
        Variables.border({
            top: { width: 1, radius: 0 },
            right: { width: 0, radius: 0 },
            bottom: { width: 0, radius: 0 },
            left: { width: 0, radius: 0 },
        }),
    );

    /**
     * @varGroup numberedPager.font
     * @expand font
     * @description Set the font options for the pagination component.
     */
    const font = makeThemeVars(
        "font",
        Variables.font({
            size: globalVars.fonts.size.medium,
        }),
    );

    const iconButtonHoverOptions = {
        fonts: {
            size: globalVars.fonts.size.medium,
        },
        colors: {
            fg: globalVars.mainColors.primary,
            bg: globalVars.mainColors.primary.fade(0.1),
        },
        borders: {
            top: { width: 0, radius: 3 },
            right: { width: 0, radius: 3 },
            bottom: { width: 0, radius: 3 },
            left: { width: 0, radius: 3 },
        },
    };
    const nextPageButtonHoverOptions = {
        fonts: {
            size: globalVars.fonts.size.medium,
        },
        colors: {
            fg: globalVars.mainColors.primaryContrast,
            bg: globalVars.mainColors.primary,
        },
        borders: {
            all: {
                radius: 18,
            },
        },
    };
    const jumperGoButtonHoverOptions = {
        fonts: {
            size: globalVars.fonts.size.medium,
        },
        borders: {
            all: { radius: 3 },
        },
    };

    /**
     * @varGroup numberedPager.buttons
     * @description Customize the buttons displayed in the pager
     */
    const buttons = makeThemeVars("buttons", {
        /**
         * @varGroup numberedPager.buttons.iconButton
         * @description Customization options for the next, previous, and page jumper toggle icon buttons.
         */
        iconButton: makeThemeVars(
            "iconButton",
            Variables.button({
                name: ButtonTypes.ICON,
                ...iconButtonHoverOptions,
                padding: {
                    horizontal: 0,
                    top: 0,
                    bottom: 0,
                },
                sizing: {
                    minWidth: 32,
                    minHeight: 32,
                },
                colors: {
                    fg: globalVars.mainColors.fg.fade(0.75),
                    bg: globalVars.mainColors.bg.fade(0),
                },
                active: iconButtonHoverOptions,
                hover: iconButtonHoverOptions,
                focus: iconButtonHoverOptions,
                disabled: {
                    ...iconButtonHoverOptions,
                    colors: {
                        fg: globalVars.mainColors.fg.fade(0.75),
                        bg: globalVars.mainColors.bg.fade(0),
                    },
                    opacity: 0.5,
                },
            }),
        ),
        /**
         * @varGroup numberedPager.buttons.nextPage
         * @description Customization options for the 'Next Page' button.
         */
        nextPage: makeThemeVars(
            "nextPage",
            Variables.button({
                name: ButtonTypes.OUTLINE,
                ...nextPageButtonHoverOptions,
                sizing: {
                    minHeight: 36,
                },
                colors: {
                    fg: globalVars.mainColors.fg,
                    bg: globalVars.mainColors.bg,
                },
                padding: {
                    horizontal: 30,
                },
                hover: nextPageButtonHoverOptions,
                active: nextPageButtonHoverOptions,
                focus: nextPageButtonHoverOptions,
                disabled: {
                    ...nextPageButtonHoverOptions,
                    colors: {
                        fg: globalVars.mainColors.fg,
                        bg: globalVars.mainColors.bg,
                    },
                    opacity: 0.5,
                },
            }),
        ),
        /**
         * @varGroup numberedPager.buttons.jumperGo
         * @description Customization options for the 'Go' button displayed in the page jumper.
         */
        jumperGo: makeThemeVars(
            "jumperGo",
            Variables.button({
                name: ButtonTypes.PRIMARY,
                ...jumperGoButtonHoverOptions,
                sizing: {
                    minWidth: 1,
                    minHeight: 32,
                },
                padding: {
                    horizontal: 8,
                },
                hover: jumperGoButtonHoverOptions,
                active: jumperGoButtonHoverOptions,
                focus: jumperGoButtonHoverOptions,
                disabled: jumperGoButtonHoverOptions,
            }),
        ),
    });

    /**
     * @varGroup numberedPager.formatNumber
     * @description Enable compact formatting of the numbers displayed in the pager if the number goes over 999.
     */
    const formatNumber = makeThemeVars("formatNumber", {
        /**
         * @var numberedPager.formatNumber.resultRange
         * @type boolean
         * @description Enable compact formatting for the range numbers. ex: 1 - 10
         */
        resultRange: false,
        /**
         * @var numberedPager.formatNumber.rangePrecision
         * @type number
         * @description Number of decimals to display if the range is formatted.
         */
        rangePrecision: 1,
        /**
         * @var numberedPager.formatNumber.totalResults
         * @type boolean
         * @description Enable compact formatting for the total results number count.
         */
        totalResults: true,
        /**
         * @var numberedPager.formatNumber.totalPrecision
         * @type number
         * @description Number of decimals to display if the total is formatted.
         */
        totalPrecision: 1,
    });

    return {
        background,
        border,
        font,
        buttons,
        formatNumber,
    };
});
