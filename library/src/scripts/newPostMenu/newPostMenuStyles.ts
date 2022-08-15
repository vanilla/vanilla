/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { calc } from "csx";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { css, injectGlobal } from "@emotion/css";
import { buttonVariables } from "@library/forms/Button.variables";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { DeepPartial } from "redux";
import { BorderType } from "@library/styles/styleHelpers";
import { twoColumnVariables } from "@library/layout/types/layout.twoColumns";

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

export const newPostMenuClasses = useThemeCache(
    (containerOptions?: DeepPartial<IHomeWidgetContainerOptions>, dropdownZIndex?: number) => {
        const vars = newPostMenuVariables();
        const globalVars = globalVariables();
        const buttonVars = buttonVariables();

        //we use injectGlobal here cause [data-reach-menu-popover] is higher dom element and not accessible in NewPostMenuDropdown where we have the popover
        injectGlobal({
            "[data-reach-menu-popover]": {
                zIndex: dropdownZIndex ?? 1050,
            },
        });

        const root = css({
            position: "fixed",
            bottom: styleUnit(vars.fab.position.bottom),
            right: styleUnit(vars.fab.position.right),
            textAlign: "right",
        });

        const fabItem = css({
            ...Mixins.margin({ top: 16, right: 6 }),
        });

        const focusStyles = {
            ...Mixins.absolute.fullSizeOfParent(),
            ...Mixins.margin({ all: 1 }),
            maxWidth: calc(`100% - 2px`),
            maxHeight: calc(`100% - 2px`),
        };

        const fabItemFocus = css({
            ...focusStyles,
            ...Mixins.border(vars.fabAction.border),
        });

        const fabAction = css({
            position: "relative",
            ...Mixins.border(vars.fabAction.border),
            ...shadowHelper().floatingButton(),
            minHeight: styleUnit(vars.fabAction.size.height),
            width: styleUnit(vars.fabAction.size.width),
            backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
            ...Mixins.padding({ horizontal: vars.fabAction.spacing.horizontal }),
            display: "inline-flex",
            alignItems: "center",
            ...Mixins.clickable.itemState({ default: globalVars.mainColors.fg }),
            ...{
                "&.focus-visible": {
                    outline: 0,
                },
                [`&.focus-visible .${fabItemFocus}`]: {
                    boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primary)} inset`,
                },
            },
            "& svg": {
                margin: "auto",
            },
        });

        const fabFocus = css({
            ...focusStyles,
            borderRadius: "50%",
        });

        const fab = css({
            display: "inline-flex",
            alignItems: "center",
            justifyItems: "center",
            height: styleUnit(vars.fab.size),
            width: styleUnit(vars.fab.size),
            backgroundColor: ColorsUtils.colorOut(buttonVars.primary.colors?.bg),
            borderRadius: "50%",
            border: 0,
            cursor: "pointer",
            ...{
                [`&.focus-visible`]: {
                    outline: 0,
                },
                [`&.focus-visible .${fabFocus}`]: {
                    boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primaryContrast)} inset`,
                },
            },
            "& svg": {
                color: ColorsUtils.colorOut(vars.button.font.color),
            },
        });

        const container = css({
            display: "flex",
            flexDirection: "column",
            ...Mixins.box({
                ...Variables.box({
                    borderType: containerOptions?.borderType as BorderType,
                }),
            }),
            backgroundColor: containerOptions?.outerBackground?.color
                ? ColorsUtils.colorOut(containerOptions?.outerBackground?.color)
                : undefined,
        });

        const newPostButtonBorderAndShadow = {
            ...Mixins.border(vars.button.border),
            ...shadowHelper().dropDown(),
        };

        const button = (borderRadius?: string | number) =>
            css({
                "&&": {
                    minWidth: buttonVars.primary.sizing?.minWidth ?? styleUnit(148),
                    maxWidth: "fit-content",
                    height: styleUnit(48),
                    ...newPostButtonBorderAndShadow,
                    borderRadius: borderRadius ? styleUnit(borderRadius) : undefined,
                    ...{
                        [`&:not([disabled]):focus-visible, &:not([disabled]):focus, &:not([disabled]):hover, &:not([disabled]):active`]:
                            {
                                ...newPostButtonBorderAndShadow,
                                borderRadius: borderRadius ? styleUnit(borderRadius) : undefined,
                            },
                    },
                },
            });

        const separateButton = css({
            "&:not(:first-child)": {
                marginTop: 16,
            },
        });

        const buttonContents = css({
            display: "flex",
            justifyContent: "space-around",
            alignItems: "center",
            width: "100%",
            "& svg": {
                margin: 0,
                color: ColorsUtils.colorOut(vars.button.font.color),
            },
        });

        const buttonIcon = css({
            display: "flex",
            marginRight: styleUnit(4),
        });

        const buttonLabel = css({
            ...Mixins.font(vars.button.font),
        });

        const dropdownWidth = twoColumnVariables().panel.paddedWidth - globalVars.gutter.size;

        const buttonDropdownContents = css({
            ...Mixins.padding({ vertical: globalVars.gutter.half }),
            ...Mixins.margin({ top: 4 }),

            //instead of importing "@reach/menu-button/styles.css", extracted some styles
            //from their github repo https://github.com/reach/reach-ui/blob/develop/packages/menu-button/styles.css
            "&[data-reach-menu-list]": {
                outline: "none",
            },

            "& [data-reach-menu-item][data-selected]": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
                color: globalVars.states.hover.contrast
                    ? ColorsUtils.colorOut(globalVars.states.hover.contrast)
                    : undefined,
            },

            "&&": {
                width: containerOptions?.borderType
                    ? styleUnit(dropdownWidth - globalVars.gutter.size)
                    : styleUnit(dropdownWidth),
            },
        });

        const fabLabel = css({
            ...Mixins.margin({ left: 10 }),
            display: "inline-block",
        });

        const fabWrap = css({
            display: "inline-flex",
            borderRadius: "50%",
            ...shadowHelper().dropDown(),
            height: styleUnit(vars.fab.size),
            width: styleUnit(vars.fab.size),
            ...Mixins.margin(vars.fab.spacing),
        });

        return {
            root,
            fabItem,
            fabItemFocus,
            fabAction,
            fab,
            fabLabel,
            fabWrap,
            fabFocus,
            container,
            separateButton,
            button,
            buttonContents,
            buttonIcon,
            buttonLabel,
            buttonDropdownContents,
        };
    },
);
