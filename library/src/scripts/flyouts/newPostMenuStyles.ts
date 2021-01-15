/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { absolutePosition } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { calc, color } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const newPostMenuVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("newPostMenu");

    const position = themeVars("position", {
        bottom: 40,
        right: 24,
    });

    const item = themeVars("item", {
        position: {
            top: 16,
            right: 6,
        },
        opacity: {
            open: 1,
            close: 0,
        },
        transformY: {
            open: 0,
            close: 100,
        },
    });

    const action = themeVars("action", {
        borderRadius: 21.5,
        padding: {
            horizontal: 18,
        },
        size: {
            height: 44,
        },
    });

    const toggle = themeVars("toggle", {
        size: 56,
        margin: {
            top: 24,
        },
        opacity: {
            open: 1,
            close: 0,
        },
        degree: {
            open: -135,
            close: 0,
        },
        scale: {
            open: 0.9,
            close: 1,
        },
    });

    const label = themeVars("label", {
        margin: {
            left: 10,
        },
    });

    const menu = themeVars("menu", {
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
        position,
        item,
        action,
        toggle,
        label,
        menu,
    };
});

export const newPostMenuClasses = useThemeCache(() => {
    const vars = newPostMenuVariables();
    const globalVars = globalVariables();
    const style = styleFactory("newPostMenu");

    const root = style("root", {
        position: "fixed",
        bottom: styleUnit(vars.position.bottom),
        right: styleUnit(vars.position.right),
        textAlign: "right",
    });

    const item = style("item", {
        marginTop: styleUnit(vars.item.position.top),
        marginRight: styleUnit(vars.item.position.right),
    });

    const itemFocus = style("itemFocus", {
        ...absolutePosition.fullSizeOfParent(),
        margin: styleUnit(1),
        maxWidth: calc(`100% - 2px`),
        maxHeight: calc(`100% - 2px`),
        borderRadius: styleUnit(vars.action.borderRadius),
    });

    const action = style("action", {
        position: "relative",
        borderRadius: styleUnit(vars.action.borderRadius),
        ...shadowHelper().floatingButton(),
        minHeight: styleUnit(vars.action.size.height),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        paddingLeft: styleUnit(vars.action.padding.horizontal),
        paddingRight: styleUnit(vars.action.padding.horizontal),
        display: "inline-flex",
        alignItems: "center",
        ...Mixins.clickable.itemState({ default: globalVars.mainColors.fg }),
        ...{
            "&.focus-visible": {
                outline: 0,
            },
            [`&.focus-visible .${itemFocus}`]: {
                boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primary)} inset`,
            },
        },
    });

    const toggleFocus = style("toggleFocus", {
        ...absolutePosition.fullSizeOfParent(),
        margin: styleUnit(1),
        maxWidth: calc(`100% - 2px`),
        maxHeight: calc(`100% - 2px`),
        borderRadius: "50%",
    });

    const toggle = style("toggle", {
        display: "inline-flex",
        alignItems: "center",
        justifyItems: "center",
        height: styleUnit(vars.toggle.size),
        width: styleUnit(vars.toggle.size),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        borderRadius: "50%",
        ...{
            [`&.focus-visible`]: {
                outline: 0,
            },
            [`&.focus-visible .${toggleFocus}`]: {
                boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primaryContrast)} inset`,
            },
        },
    });

    const label = style("label", {
        marginLeft: styleUnit(vars.label.margin.left),
        display: "inline-block",
    });

    const toggleWrap = style("toggleShadow", {
        display: "inline-flex",
        borderRadius: "50%",
        ...shadowHelper().floatingButton(),
        height: styleUnit(vars.toggle.size),
        width: styleUnit(vars.toggle.size),
        ...Mixins.margin(vars.toggle.margin),
    });

    return {
        root,
        item,
        itemFocus,
        action,
        toggle,
        label,
        toggleWrap,
        toggleFocus,
    };
});
