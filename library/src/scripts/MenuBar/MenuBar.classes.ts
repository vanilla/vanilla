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
    });

    const menuItem = css({
        listStyle: "none",
        padding: 0,
        margin: 0,
        "&::before": {
            display: "none",
        },
    });

    const menuItemIconContent = buttonUtils.buttonIconMenuBar;

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
    });

    const subMenuItemIcon = css({
        marginLeft: -listSpacer,
        marginRight: listSpacer,
    });
    const subMenuItemText = css({
        paddingTop: listSpacer,
        paddingBottom: listSpacer,
    });

    return {
        root,
        menuItemsList,
        menuItem,
        menuItemIconContent,
        menuItemTextContent,
        subMenuContainer,
        subMenuItemsList,
        subMenuGroup,
        subMenuItem,
        subMenuItemIcon,
        subMenuItemText,
    };
});
