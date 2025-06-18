/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { buttonUtilityClasses } from "@library/forms/Button.styles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";

/**
 * Classes for the menubar.
 */
export const menuBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const buttonUtils = buttonUtilityClasses();
    const formVars = formElementsVariables();

    const listSpacer = 4;

    const root = css({
        marginTop: 4,
        position: "relative",
        display: "inline-flex",
        flexDirection: "column",
        alignItems: "stretch",
        background: ColorsUtils.colorOut(formVars.colors.bg),
        color: ColorsUtils.colorOut(formVars.colors.fg),
        ...Mixins.border(),
        ...shadowOrBorderBasedOnLightness(undefined, undefined, shadowHelper().toolbar()),
    });

    const menuItemsList = css({
        display: "flex",
        alignItems: "center",
        gap: 2,
        padding: 2,

        //a bit higher specificity so the legacy page styles don't override these
        "&&& li": {
            width: "auto",
        },
    });

    const menuItem = css({
        listStyle: "none",
        padding: 0,
        margin: 0,
        "&::before": {
            display: "none",
        },
    });

    const menuItemSeparator = (
        spacing: { leftSpace?: number; rightSpace?: number } = { leftSpace: 4, rightSpace: 4 },
    ) => {
        return css({
            display: "block",
            height: 16,
            width: 1,
            background: ColorsUtils.colorOut(globalVariables().border.color),
            marginLeft: spacing.leftSpace,
            marginRight: spacing.rightSpace,
        });
    };

    const menuItemIconContent = buttonUtils.buttonIconMenuBar;

    const floatingElementMenuButton = css({
        ...shadowOrBorderBasedOnLightness(globalVars.mainColors.bg, undefined, shadowHelper().embed()),
        background: `${ColorsUtils.colorOut(globalVars.mainColors.bg)} !important`,
        borderRadius: "100%",
        "&:hover, &:focus": {
            ...shadowHelper().embedHover(),
        },
        "&:focus-visible": {
            border: `1px solid ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
            ...shadowHelper().embedHover(),
        },
    });

    const menuItemTextContent = css({});

    const subMenuContainer = css({
        maxWidth: "100%",
    });

    const subMenuItemsList = css({
        overflow: "hidden",
        paddingBottom: listSpacer,
    });

    const subMenuGroup = css({
        borderTop: singleBorder(),
        borderBottom: singleBorder(),
        paddingTop: listSpacer,
        paddingBottom: listSpacer,
        marginTop: listSpacer,
        marginBottom: listSpacer,
        "&:first-child, & + .subMenuGroup": {
            borderTop: "none",
            marginTop: 0,
            paddingTop: 0,
        },
        "&:last-child": {
            borderBottom: "none",
            marginBottom: 0,
            paddingBottom: 0,
        },
    });

    const subMenuGroupTitle = css({
        paddingLeft: 34,
    });

    const subMenuItem = css({
        ...Mixins.padding({
            vertical: listSpacer,
            horizontal: 10,
        }),
        display: "flex",
        alignItems: "center",
        cursor: "pointer",
        lineHeight: globalVars.lineHeights.condensed,
        "&:disabled, &[aria-disabled='true']": {
            opacity: 0.5,
            cursor: "not-allowed",
        },
        "&:not(:disabled):not([aria-disabled='true'])": {
            "&:hover, &:focus": {
                background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
        "&.active": {
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
        "&&.focus-visible, &:focus-visible": {
            outline: "none",
            boxShadow: `inset 0 0 0 1px ${globalVars.mainColors.primary}`,
        },
        "&.isInline": {
            padding: 0,
            "&:not(:disabled):not([aria-disabled='true'])": {
                "&:hover, &:focus": {
                    background: "none",
                },
            },
            "&&.focus-visible, &:focus-visible": {
                background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
            },
        },
    });

    const subMenuItemIcon = css({
        marginLeft: -listSpacer,
        marginRight: listSpacer,
    });

    const inlineSubMenuItemsWrapper = css({
        paddingLeft: 6,
        paddingTop: listSpacer,
    });

    const subMenuItemText = css({
        paddingTop: listSpacer,
        paddingBottom: listSpacer,
    });

    const menuBarAsPopover = css({
        "&&": {
            top: 0,
            left: 0,
        },
    });

    return {
        root,
        menuItemsList,
        menuItem,
        menuItemSeparator,
        menuItemIconContent,
        floatingElementMenuButton,
        menuItemTextContent,
        subMenuContainer,
        subMenuItemsList,
        subMenuGroup,
        subMenuGroupTitle,
        subMenuItem,
        subMenuItemIcon,
        subMenuItemText,
        inlineSubMenuItemsWrapper,
        menuBarAsPopover,
    };
});
