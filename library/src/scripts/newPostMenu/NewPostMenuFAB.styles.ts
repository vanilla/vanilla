/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, injectGlobal } from "@emotion/css";
import { buttonVariables } from "@library/forms/Button.variables";
import { newPostMenuVariables } from "@library/newPostMenu/NewPostMenu.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { calc } from "csx";

export const newPostMenuFABClasses = useThemeCache(() => {
    const vars = newPostMenuVariables();
    const globalVars = globalVariables();
    const buttonVars = buttonVariables();

    //we use injectGlobal here cause [data-reach-menu-popover] is higher dom element and not accessible in NewPostMenuDropdown where we have the popover
    injectGlobal({
        "[data-reach-menu-popover]": {
            zIndex: 1050,
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
    };
});
