/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { appearance, singleLineEllipsis, userSelect, pointerEvents } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent, quote } from "csx";
import { richEditorVariables } from "@library/editor/richEditorVariables";
import { css } from "@emotion/css";
import { buttonResetMixin } from "@library/forms/buttonMixins";

export const richEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("richEditor"); //fixme: update namespace?
    const vars = richEditorVariables();

    const wrapper = style("wrapper", {
        position: "relative",
        zIndex: 2,
    });

    const root = style({
        position: "relative",
        display: "block",
        ...{
            "&.isDisabled": {
                ...{
                    "&, &.richEditor-button": {
                        cursor: important("progress"),
                    },
                },
            },
            ".richEditor-textWrap, .richEditor-frame": {
                display: "flex",
                flexDirection: "column",
                flexGrow: 1,
                position: "relative",
            },

            ".richEditor-nextInput, .iconButton, .richEditor-button": {
                ...singleLineEllipsis(),
                ...appearance(),
                position: "relative",
                border: 0,
                padding: 0,
                background: "none",
                textAlign: "start",
            },
            ".Close-x": {
                display: "block",
                cursor: "pointer",
            },
            ".content-wrapper": {
                height: percent(100),
            },
            ".embedDialogue": {
                position: "relative",
            },
        },
    });

    const iconWrap = style("iconWrap", {
        ...pointerEvents(),
        content: quote(``),
        ...Mixins.absolute.middleOfParent(),
        width: styleUnit(vars.iconWrap.width),
        height: styleUnit(vars.iconWrap.width),
        ...Mixins.border({
            radius: 3,
            color: globalVars.elementaryColors.transparent,
        }),
    });

    const conversionNotice = css({
        marginTop: 16,
        marginBottom: 16,
        width: "auto",
    });

    const menuItem = style("menuItem", {
        overflow: "visible",
        // fix activity page old bad selector.
        "&&&": {
            background: "none",
            display: "block",
            padding: 0,
            paddingRight: 2,
            margin: 0,
            width: "auto",

            "&.richEditor-button": {
                paddingRight: 0,
            },
        },
        "&.isRightAligned": {
            marginLeft: "auto",
        },
    });

    const button = style("button", {
        ...buttonResetMixin(),
        display: "block",
        ...userSelect(),
        ...appearance(),
        cursor: "pointer",
        width: styleUnit(vars.menuButton.size),
        height: styleUnit(vars.menuButton.size),
        border: 0,
        padding: 0,
        overflow: "hidden",
        position: "relative",
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        outline: 0,
        ...{
            "&:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
            "&:active": {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
            [`&:hover .${iconWrap}`]: {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            [`&.isOpen .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            [`&.focus-visible .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            "&.richEditor-formatButton, &.richEditor-embedButton": {
                height: styleUnit(vars.menuButton.size),
            },
            "&.emojiGroup": {
                display: "block",
                width: styleUnit(vars.menuButton.size),
                height: styleUnit(vars.menuButton.size),
                textAlign: "center",
            },
            "&:not(:disabled)": {
                cursor: "pointer",
            },
            [`&.isActive .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const upload = style("upload", {
        display: important("none"),
    });

    const icon = style("icon", {
        display: "block",
        margin: "auto",
        height: styleUnit(globalVars.icon.sizes.default),
        width: styleUnit(globalVars.icon.sizes.default),
    });

    const close = style("close", {
        ...Mixins.absolute.middleRightOfParent(),
        ...userSelect(),
        ...appearance(),
        width: styleUnit(vars.menuButton.size),
        height: styleUnit(vars.menuButton.size),
        lineHeight: styleUnit(vars.menuButton.size),
        verticalAlign: "bottom",
        textAlign: "center",
        background: "transparent",
        cursor: "pointer",
        border: 0,
        outline: 0,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const emojiGroup = style("emojiGroup", {
        ...{
            [`&.isSelected .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const flyoutOffset = style("flyoutOffset", {
        marginTop: styleUnit((vars.menuButton.size - vars.iconWrap.width) / -2 + 1),
    });

    return {
        root,
        wrapper,
        upload,
        menuItem,
        button,
        icon,
        close,
        iconWrap,
        flyoutOffset,
        emojiGroup,
        conversionNotice,
    };
});
